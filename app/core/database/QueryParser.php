<?php

namespace Core\Database;

class QueryParser
{
	/**
	 * 
	 * @var QueryBuilder
	 */
	private $builder;


	public function __construct(QueryBuilder $builder)
	{
		$this->builder = $builder;
	}


	/**
	 * Verifica se o valor é uma expressão e retorna o valor literal. 
	 * Caso contrário, retorna o placeholder '?'.
	 *
	 * @param  mixed $valor
	 *
	 * @return string Retorna o valor literal ou o nome do parâmetro;
	 */
	private function param($valor)
	{
		return $valor instanceof Expression ? (string) $valor : '?';
	}

	/**
	 * Retorna uma string com os itens separados por vírgula.
	 * 
	 * @param array $arr Array contendo os valores
	 *
	 * @return string
	 */
	private function serializarItens($arr)
	{
		return implode(', ', $arr);
	}

	private function parseSelect()
	{
		if ($aggr = $this->builder->getFuncaoAgregacao()){
			$strDistinct = (($this->builder->isDistinct() && $aggr['coluna'] != '*') ? 'DISTINCT ' : '');
			$strSelect = "{$aggr['funcao']}($strDistinct{$aggr['coluna']}) AS valor"; 
		} else{
			$strDistinct = ($this->builder->isDistinct() ? 'DISTINCT ' : '');
			$strSelect = $strDistinct . $this->serializarItens($this->builder->getColunas() ?: ['*']);
		}

		$strFrom = $this->builder->getTabela() ?: '';
		$strJoins = $this->parseJoins();
		$strWhere = $this->parseWhere();
		$strGroup = $this->parseGroupBy();
		$strOrder = $this->parseOrderBy();

		$sql = "SELECT $strDistinct$strSelect FROM $strFrom $strJoins $strWhere $strGroup $strOrder";
		if ($this->limit !== null)
			$sql .= " LIMIT {$this->limit}";
		if ($this->offset !== null)
			$sql .= " OFFSET {$this->offset}";

		return $sql;
	}

	private function parseInsert()
	{
		if (empty($dados = $this->builder->getDados()))
			return '';

		$strInsert = "INSERT INTO {$this->tabela}";
		
		//-- Se o parâmetro dados for um callback, considera-se que o comando é um INSERT a psrtir de uma consulta SELECT.
		//-- Neste caso, é necessário montar uma nova query SELECT para concatenar com o comando.
		if (is_callable($dados)) {
			$strColunas = $this->serializarItens($this->colunasInsert);
			$query = $this->builder->newQuery();
			call_user_func($dados, $query);
			$strInsert .= "($strColunas) " . $query->toSql();
		} else {
			//-- Se as chaves do array forem numéricas, considera-se que foi informado um array de registros aninhados.
			if (is_numeric(array_key_first($dados))) {
				$strColunas = '(' . $this->serializarItens(array_keys($dados[0])) . ')';
				$strValores = $this->serializarItens(array_map(function($arrRegistro) {
							return '(' . $this->serializarItens(array_map([$this, 'param'], array_values($arrRegistro))) . ')';
						}, $dados));
			} else {
				$strColunas = $this->serializarItens(array_keys($dados));
				$strValores = '(' . $this->serializarItens(array_map([$this, 'param'], array_values($dados))) . ')';
			}
			$strInsert .= "($strColunas) VALUES $strValores";
		}
		return $strInsert;
	}

	private function parseUpdate()
	{
		if (empty($dados = $this->builder->getDados()))
			return '';

		$strWhere = $this->parseWhere();
		$strJoins = $this->parseJoins();

		$strCamposSet = '';
		foreach ($dados as $strColuna => $strValor) {
			if ($strCamposSet) $strCamposSet .= ', ';
			$strCamposSet .= "$strColuna = " . $this->param($strValor);
		}

		$strUpdate = "UPDATE {$this->builder->getTabela()}" . ($strJoins ? " $strJoins" : '') . " SET $strCamposSet $strWhere";
		return $strUpdate;
	}

	private function parseDelete()
	{
		$strWhere = $this->parseWhere();
		$strJoins = $this->parseJoins();

		$strDelete = "DELETE FROM {$this->builder->getTabela()} $strJoins $strWhere";
		return $strDelete;
	}

	private function parseWhere()
	{
		if (empty($wheres = $this->builder->getWheres()))
			return '';

		$strWhere = '';
		foreach ($wheres as $arrWhere) {
			$strWhere .= (!$strWhere ? 'WHERE ' : " {$arrWhere['logico']} ");
			if (isset($arrWhere['expressao'])) {
				$strWhere .= $arrWhere['expressao'];
				continue;
			}

			$valor = $arrWhere['valor'];
			$strColuna = $arrWhere['coluna'];
			$strOper = trim(strtoupper($arrWhere['operador']));
			if ($strOper == 'IN') {
				if (!is_array($valor)) {
					$valor = $this->paramWhere($valor);
					$strWhere .= "$strColuna IN ($valor)";
				} else {
					$strParams = $this->serializarItens(array_map([$this, 'param'], $valor));
					$strWhere .= "$strColuna IN ($strParams)";
				}
			} elseif ($strOper == 'BETWEEN') {
				$strValor1 = $this->param($valor[0]);
				$strValor2 = $this->param($valor[1]);
				$strWhere .= "$strColuna $strOper $strValor1 AND $strValor2";
			} else {
				$strWhere .= "$strColuna $strOper " . $this->param($valor);
			}
		}

		return $strWhere;
	}

	private function parseJoins()
	{
		if (empty($joins = $this->builder->getJoins()))
			return '';

		$arrJoins = array_map(function($arr) {
			$strJoin = trim($arr['tipo'] . ' JOIN');
			if (isset($arr['expressao'])) {
				return "$strJoin {$arr['expressao']}";
			}

			return "$strJoin {$arr['tabela']} ON {$arr['coluna1']} {$arr['operador']} {$arr['coluna2']}";
		}, $joins);

		return $this->serializarItens($arrJoins);
	}

	private function parseGroupBy()
	{
		if (empty($groupBy = $this->builder->getGroupBy()))
			return '';

		return 'GROUP BY ' . $this->serializarItens($groupBy);
	}

	private function parseOrderBy()
	{
		if (empty($orderBy = $this->builder->getOrderBy()))
			return '';

		$arrOrderBy = array_map(function($arr) {
			return "{$arr['coluna']} {$arr['direcao']}";
		}, $orderBy);

		return 'ORDER BY ' . $this->serializarItens($arrOrderBy);
	}

	/**
	 * Retorna o comando SQL gerado neste objeto.
	 * 
	 * @param bool $boolAplicarParams Informa se irá retornar o SQL com os parâmetros já aplicados.
	 * 
	 * @return string
	 */
	public function parseSql($boolAplicarParams = false)
	{
		switch ($this->builder->getTipoComando()) {
			case QueryBuilder::TIPO_SELECT:
				$sql = $this->parseSelect();
				break;
			case QueryBuilder::TIPO_INSERT:
				$sql = $this->parseInsert();
				break;
			case QueryBuilder::TIPO_UPDATE:
				$sql = $this->parseUpdate();
				break;
			case QueryBuilder::TIPO_DELETE:
				$sql = $this->parseDelete();
				break;
		}

		return $boolAplicarParams ? $this->aplicarParametrosSql($sql, $this->params) : $sql;
	}
}