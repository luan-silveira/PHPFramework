<?php

namespace Database;

use Arr;

/**
 * Classe construtora de consultas SQL.
 */
class QueryBuilder
{
	use SqlParams;

	const TIPO_SELECT = 'select';
	const TIPO_INSERT = 'insert';
	const TIPO_UPDATE = 'update';
	const TIPO_DELETE = 'delete';

	/**
	 * Define o tipo de comando SQL a ser construído.
	 * 
	 * @var int 
	 */
	private $tipoComando = self::TIPO_SELECT;

	/**
	 * Array de colunas Select, Insert, Update
	 * 
	 * @var array
	 */
	private $colunas;

	/**
	 * Nome da tabela a ser consultada/modificada (comandos SELECT, INSERT, UPDATE, DELETE)
	 * 
	 * @var string 
	 */
	private $tabela;

	/**
	 * Array de dados p/ comandos INSERT e UPDATE ou callback contendo a consulta SELECT para o comando INSERT
	 * 
	 * @var array|\Closure|callable
	 */
	private $dados;

	/**
	 * Array contendo as colunas para o comando INSERT com SELECT.
	 * Válido somente se o parâmetro $dados for um callback.
	 * 
	 * @var array 
	 */
	private $colunasInsert;

	/**
	 * Array de condições da cláusula Where.
	 * Cada registro pode ser um objeto do tipo Expression (para expressões SQL literais) ou um array associativo subdivido da seguinte forma:
	 * 		- [coluna]    - Nome da coluna
	 * 		- [operador]  - operador de comparação ('=', '<', '>', etc.)
	 * 		- [valor] 	  - Valor da coluna
	 * 		- [logico]    - Operador lógico booleano (AND/OR) 
	 * 		- [expressao] - (Opcional) Expressão literal Where (se informado, desconsidera os parâmetros da coluna, do operador de comparação e do valor)
	 * 
	 * 		WHERE <coluna> <op. comparação> <valor> {<op. lógico> <coluna> <op. comparação> <valor> ...}
	 * 
	 * @var array
	 */
	private $wheres;

	/**
	 * Array de registros de junções (JOIN).
	 * Cada registro pode ser um objeto do tipo Expression (para expressões SQL literais) ou um array associativo subdivido da seguinte forma:
	 * 		- tabela de junção
	 * 		- coluna 1
	 * 		- operador de comparação ('=', '<', '>', etc.)
	 * 		- coluna 2
	 * 		- tipo (LEFT/RIGHT/INNER)
	 * 
	 * 	JOIN <tabela> ON <op. comparação> <valor> {<op. lógico> <coluna> <op. comparação> <valor> ...}
	 * 
	 * @var array
	 */
	private $joins;

	/**
	 * Array de colunas Group By
	 * 
	 * @var array
	 */
	private $groupBy;

	/**
	 * Array de registros da cláusula Having.
	 * Cada registro pode ser um objeto do tipo Expression (para expressões SQL literais) ou um array associativo subdivido da seguinte forma:
	 * 		- coluna
	 * 		- operador de comparação ('=', '<', '>', etc.)
	 * 		- valor
	 * 		- operador lógico booleano (AND/OR) 
	 * 	
	 *  HAVING <coluna> <op. comparação> <valor> {<op. lógico> <coluna> <op. comparação> <valor> ...}
	 * 
	 * @var array
	 */
	private $having;

	/**
	 * Array de colunas de ordernação Order By:
	 * 		- [coluna]  - Nome da coluna
	 * 		- [direcao] - Direção de ordenação (ASC/DESC)
	 * 
	 * @var array
	 */
	private $orderBy;

	/**
	 * Array de consultas UNION
	 * 	- [query] => Instância de QueryBuilder contendo a query para unir
	 *  - [all] => Informa se a query será executada como UNION ALL
	 */
	private $unions;

	/**
	 * Operadores de comparação permitidos
	 * 
	 * @var array
	 */
	private $operadores = ['=', '<', '<=', '>', '>=', '<>', '!=',
		'LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE'];

	/**
	 * Limite de registros
	 * 
	 * @var int
	 */
	private $limit;

	/**
	 * Posição do limite do registro (paginação)
	 * 
	 * @var int
	 */
	private $offset;

	/**
	 * Informa se a consulta deve ter os resultados distintos (SELECT DISTINCT)
	 * 
	 * @var bool
	 */
	private $distinct = false;

	/**
	 * Array de parâmetros SQL (bindings)
	 * 
	 * @var array
	 */
	private $params = [
		'insert' => [],
		'update' => [],
		'where'  => [],
	];

	private $funcaoAgregacao;

	/**
	 * Instância da classe manipuladora de consultas de banco de dados
	 * 
	 * @var DB
	 */
	private $db;

	/**
	 * 
	 * @var QueryParser
	 */
	private $parser;

	/**
	 * Inicia uma nova query
	 * 
	 * @param DB $db Instãncia da classe manipuladora de consultas de banco de dados
	 */
	public function __construct($db = null)
	{
		$this->db = $db;
		$this->parser = new QueryParser($this);
	}

	public function setDB($db)
	{
		$this->db = $db;
	}

	/**
	 * Cria uma nova query a partir deste objeto.
	 * 
	 * @return QueryBuilder
	 */
	public function newQuery()
	{
		return new self($this->db);
	}


	/**
	 * Retorna os parâmetros do comando SQL.
	 * 
	 */
	public function getParams($strTipo = null)
	{
		return $strTipo ? $this->params[$strTipo] : Arr::achatar($this->params);
	}

	public function addParam($valor, $strTipo = 'where')
	{
		if (is_array($valor)){
			$this->params[$strTipo] = array_merge(array_values($this->params[$strTipo]), $valor);
		} else{
			$this->params[$strTipo][] = $valor;
		} 
		return $this;
	}


	/**
	 * Informa as colunas para a consulta SELECT
	 *
	 * @param string $colunas (Parâmetro variável) Nome das colunas a serem selecionadas.
	 *
	 * @return QueryBuilder;
	 */
	public function select(...$colunas)
	{
		$this->colunas = empty($colunas) ? ['*'] : $colunas;
		return $this;
	}

	/**
	 * Informa a tabela para a consulta
	 *
	 * @param  string $strTabela Nome da tabela. Podem ser informadas também várias tabelas separadas por vírgula.
	 *
	 * @return QueryBuilder
	 */
	public function table($strTabela)
	{
		$this->tabela = $this->from = $strTabela;
		return $this;
	}

	/**
	 * Informa a tabela para a consulta SELECT (exclusivo cláusula FROM, para comandos UPDATE)
	 *
	 * @param  string $strTabela Nome da tabela. Podem ser informadas também várias tabelas separadas por vírgula.
	 *
	 * @return QueryBuilder
	 */
	public function from($strTabela)
	{
		$this->tabela = $strTabela;
		return $this;
	}

	/**
	 * Informa se a consulta será feita de maneira distinta (SELECT DISTINCT)
	 *
	 * @param bool $distinct
	 *
	 * @return QueryBuilder
	 */
	public function distinct($distinct = true)
	{
		$this->distinct = $distinct;
		return $this;
	}

	/**
	 * Informa as colunas para a consulta SELECT de maneira distinta.
	 *
	 * @param string $colunas (Parâmetro variável) Nome das colunas a serem selecionadas.
	 *
	 * @return QueryBuilder
	 */
	public function selectDistinct(...$colunas)
	{
		$this->colunas = empty($colunas) ? ['*'] : $colunas;
		$this->distinct();
		return $this;
	}

	public function isDistinct()
	{
		return $this->distinct;
	}

	/**
	 * Adiciona uma condição Where à consulta.
	 *
	 * @param  string $strColuna 		  Nome da coluna
	 * @param  string $strOperadorOuValor Operador de comparação ('=', '<', etc.). 
	 * 									  Pode ser informado também o valor. Neste caso o operador assumido é o operador Igual (=).
	 * @param  string $strValor			  Valor
	 * @param  string $strOpLogico		  Operador lógico (AND/OR). O padrão é AND.
	 * 
	 * @return QueryBuilder
	 */
	public function where($strColuna, $strOperadorOuValor, $strValor = null, $strOpLogico = 'AND')
	{
		return $this->adicionarWhere(func_num_args(), $strColuna, $strOperadorOuValor, $strValor, $strOpLogico);
	}

	public function whereRaw($strExpr, $strOpLogico = 'AND')
	{
		$this->wheres[] = [
			'expressao' => $strExpr,
			'logico' => $strOpLogico
		];

		return $this;
	}

	public function orWhereRaw($strExpr)
	{
		return $this->whereRaw($strExpr, 'OR');
	}

	public function orWhere($strColuna, $strOperadorOuValor, $strValor = null)
	{
		return $this->adicionarWhere(func_num_args(), $strColuna, $strOperadorOuValor, $strValor, 'OR');
	}

	public function whereIn($strColuna, $valores, $strOpLogico = 'AND', $boolNotIn = false)
	{
		$this->wheres[] = [
			'coluna' => $strColuna,
			'operador' => ($boolNotIn ? 'NOT ' : '') . 'IN',
			'valor' => $valores,
			'logico' => $strOpLogico,
		];

		$this->addParam($valores);

		return $this;
	}

	public function whereNotIn($strColuna, $valores)
	{
		return $this->whereIn($strColuna, $valores, 'AND', true);
	}

	public function orWhereIn($strColuna, $valores)
	{
		return $this->whereIn($strColuna, $valores, 'OR');
	}

	public function orWhereNotIn($strColuna, $valores)
	{
		return $this->whereIn($strColuna, $valores, 'OR', true);
	}

	public function whereBetween($strColuna, $valor1, $valor2, $strOpLogico = 'AND')
	{
		$arrValores = [$valor1, $valor2];
		$this->wheres[] = [
			'coluna' => $strColuna,
			'operador' => 'BETWEEN',
			'valor' => $arrValores,
			'logico' => $strOpLogico,
		];

		$this->addParam($arrValores);

		return $this;
	}

	public function orWhereBetween($strColuna, $valor1, $valor2)
	{
		return $this->whereBetween($strColuna, $valor1, $valor2, 'OR');
	}

	/**
	 * Adiciona um item na clausula where e resolve os parâmetros variáveis do operador de comparaçao, valor e operador lógico do Where.
	 * Utilizada nas funções Where.
	 * 
	 * @param int	 $intArgs 	   	 	 Total de parâmetros na função Where.
	 * @param string $strColuna			 Nome da coluna
	 * 									 Se a função tiver menos que três parâmetros, significa que o parâmetro do operador de comparação será omitido. 
	 * 									 Neste caso, os próximos parâmetros assumem valores diferentes e o último é desconsiderado, e o operador de comparação é 
	 * 									 considerado como o operador padrão Igual '='.
	 * @param string $strOpCompOuValor   Operador de comparação / Valor
	 * @param string $strValorOuOpLogico Valor / Operador lógico
	 * @param string $strOpLogico	     Operador lógico
	 */
	private function adicionarWhere($intArgs, $strColuna, $strOpCompOuValor, $strValor, $strOpLogico)
	{
		if ($intArgs == 2) {
			$arrWhere = [
				'operador' => '=',
				'valor' => $strOpCompOuValor,
			];
		} else {
			$arrWhere = [
				'operador' => $strOpCompOuValor,
				'valor' => $strValor,
			];
		}

		$arrWhere['coluna'] = $strColuna;
		$arrWhere['logico'] = $strOpLogico;

		$this->addParam($arrWhere['valor']);
		$this->wheres[] = $arrWhere;

		return $this;
	}

	public function join($strTabela, $strColuna1OuArr, $strOperadorOuColuna2 = null, $strColuna2 = null, $strTipo = '')
	{
		$arrJoin = [
			'tabela' => $strTabela,
			'coluna1' => $strColuna1OuArr,
			'tipo' => $strTipo,
			'operador' => '=',
		];

		//-- Se o parâmetro da coluna 2 for nulo, considera-se que o operador está sendo omitido e será considerado o operador Igual ('=').
		//-- Neste caso, o segundo parâmetro é o nome da coluna, e o terceiro torna-se o valor, ao invés do operador.
		if ($strColuna2 == null) {
			$arrJoin['coluna2'] = $strOperadorOuColuna2;
		} else {
			$arrJoin['operador'] = $strOperadorOuColuna2;
			$arrJoin['coluna2'] = $strColuna2;
		}

		$this->joins[] = $arrJoin;

		return $this;
	}

	public function leftJoin($strTabela, $strColuna1, $strOperadorOuColuna2, $strColuna2 = null)
	{
		return $this->join($strTabela, $strColuna1, $strOperadorOuColuna2, $strColuna2, 'LEFT');
	}

	public function rightJoin($strTabela, $strColuna1, $strOperadorOuColuna2, $strColuna2 = null)
	{
		return $this->join($strTabela, $strColuna1, $strOperadorOuColuna2, $strColuna2, 'RIGHT');
	}

	public function joinRaw($strExpr, $strTipo = '')
	{
		$this->joins[] = [
			'expressao' => $strExpr,
			'tipo' => $strTipo,
		];

		return $this;
	}

	public function leftJoinRaw($strExpr)
	{
		return $this->joinRaw($strExpr, 'LEFT');
	}

	public function rightJoinRaw($strExpr)
	{
		return $this->joinRaw($strExpr, 'RIGHT');
	}

	public function groupBy(...$colunas)
	{
		$this->groupBy = $colunas;

		return $this;
	}

	public function orderBy($strColuna, $strDirecao = 'ASC')
	{
		$this->orderBy[] = [
			'coluna' => $strColuna,
			'direcao' => $strDirecao
		];

		return $this;
	}

	public function limit($intLimit, $intOffset = null)
	{
		$this->limit = $intLimit;
		$this->offset = $intOffset;

		return $this;
	}

	/**
	 * Retorna uma lista de registros de acordo com a consulta construída.
	 * 
	 * @param string|bool $strClassOrBoolArray Nome da classe ou o valor booleano True para indicar que o tipo de registro retornado é um array.
	 * 
	 * @return array
	 */
	public function get($strClassOrBoolArray = false)
	{
		if (is_bool($strClassOrBoolArray)) {
			return $this->db->fetchAll($this->toSql(), $this->getParams(), $strClassOrBoolArray);
		}

		return $this->db->fetchAll($this->toSql(), $this->getParams(), false, $strClassOrBoolArray);
	}

	public function first($strClassOrBoolArray = false)
	{
		if (is_bool($strClassOrBoolArray)) {
			return $this->db->fetch($this->toSql(), $this->getParams(), $strClassOrBoolArray);
		}

		return $this->db->fetch($this->toSql(), $this->getParams(), false, $strClassOrBoolArray);
	}

	public function find($strColuna, $strValor, $class = 'stdClass')
	{
		return $this->where($strColuna, $strValor)->first($class);
	}

	/**
	 * Monta um comando INSERT com os dados a serem inseridos e executa o comando na conexão do banco de dados
	 * 
	 * @param array|\Closure|callable $arrDadosOuCallback Array associativo com os dados ou um callback para comando INSERT a partir de uma consulta SELECT
	 * @param array $arrColunas (Opcional) Array de colunas para a consulta. Válido apenas se o primeiro parâmetro for um callback
	 * 
	 * @return int|bool Retorna o ID inserido, ou 0 caso não seja gerado um ID, ou False em caso de falha.
	 */
	public function insert($arrDadosOuCallback, $arrColunas = null, $toSql = false)
	{
		return $this->execInsertUpdateDelete(self::TIPO_INSERT, $arrDadosOuCallback, $arrColunas, $toSql);
	}

	/**
	 * Monta um comando UPDATE com os dados a serem inseridos e executa o comando na conexão do banco de dados
	 * 
	 * @param type $arrDados Array associativo com os dados
	 * 
	 * @return int|bool Retorna o ID inserido, ou 0 caso não seja gerado um ID, ou False em caso de falha.
	 */
	public function update($arrDados, $toSql = false)
	{
		return $this->execInsertUpdateDelete(self::TIPO_UPDATE, $arrDados, null, $toSql);
	}

	/**
	 * Monta um comando DELETE e executa o comando na conexão do banco de dados
	 * 
	 * @return int|bool Retorna o ID inserido, ou 0 caso não seja gerado um ID, ou False em caso de falha.
	 */
	public function delete($toSql = false)
	{
		return $this->execInsertUpdateDelete(self::TIPO_DELETE, null, null, $toSql);
	}
	
	
	/**
	 * Busca o registro pelos dados informados e o atualiza. Caso não seja encontrado, insere um novo com a junção dos dados de busca e atualização.
	 * 
	 * @param array $arrDadosBusca Dados de busca do registro
	 * @param array $arrDadosUpdate Dados para serem atualizados
	 * 
	 * @return int|bool Retorna o último ID inserido caso seja inserido um novo registro ou True/False caso seja atualizado.
	 */
	public function updateOrInsert($arrDadosBusca, $arrDadosUpdate)
	{
		foreach($arrDadosBusca as $strColuna => $strValor){
			$this->where($strColuna, $strValor);
		}
		
		$obj = $this->first();
		if ($obj){
			return $this->update($arrDadosUpdate);
		} else {
			$query = new self($this->db);
			return $query->table($this->tabela)->insert(array_merge($arrDadosBusca, $arrDadosUpdate));
		}
	}

	/**
	 * Executa comandos INSERT, UPDATE e DELETE, de acordo com o tipo especificado.
	 * 
	 * @param int $tipo Tipo de comando (INSERT/UPDATE/DELETE)
	 * @param array|\Closure|callable $dados Array associativo com os dados ou um callback para comando INSERT a partir de uma consulta SELECT 
	 * @param type $arrColunasInsert Array de colunas INSERT para o callback.
	 * 
	 * @return int|bool
	 */
	private function execInsertUpdateDelete($tipo, $dados = null, $arrColunasInsert = null, $toSql = false)
	{
		$boolInsert = $tipo == self::TIPO_INSERT;
		if ($boolInsert) $this->colunasInsert = $arrColunasInsert;
		$this->tipoComando = $tipo;
        if (self::TIPO_DELETE != $tipo) {
			$this->dados = $dados;
			$this->addParam(Arr::achatar($dados), $tipo);
        }

		$sql = $this->toSql();
		if ($toSql) return $sql;
		
		$exec = $this->db->comandoSQL($sql, $this->getParams(), !$boolInsert);
		if ($boolInsert && $exec)
			return $this->db->getPDO()->lastInsertId();

		return $exec;
	}

	public function count($strColuna = '*', $toSql = false)
	{
		return $this->execFuncaoAgregacao(__FUNCTION__, $strColuna, $toSql);
	}

	public function sum($strColuna = '*', $toSql = false)
	{
		return $this->execFuncaoAgregacao(__FUNCTION__, $strColuna, $toSql);
	}

	public function avg($strColuna = '*', $toSql = false)
	{
		return $this->execFuncaoAgregacao(__FUNCTION__, $strColuna, $toSql);
	}

	public function min($strColuna = '*', $toSql = false)
	{
		return $this->execFuncaoAgregacao(__FUNCTION__, $strColuna, $toSql);
	}

	public function max($strColuna = '*', $toSql = false)
	{
		return $this->execFuncaoAgregacao(__FUNCTION__, $strColuna, $toSql);
	}
	
	/**
	 * Executa uma função de agregação nesta consulta SQL.
	 *
	 * @param  string $tipo Tipo de função (COUNT/SUM/AVG/MIN/MAX)
	 * @param  string $strColuna (Opcional) Coluna
	 * @param  bool $toSql
	 *
	 * @return void
	 */
	private function execFuncaoAgregacao($tipo, $strColuna = '*', $toSql = false)
	{
		$this->funcaoAgregacao = [
			'funcao' => strtoupper($tipo),
			'coluna' => $strColuna
		];

		$sql = $this->toSql();
		if ($toSql) return $sql;

		if(!$res = $this->db->fetch($sql, $this->getParams(), true)) return null;

		return $res['valor'];
	}

	public function clearParams()
	{
		foreach ($this->params as $tipo => $arr)
		{
			$this->params[$tipo] = [];
		}
	}

	public function toSql($boolAplicarParams = false)
	{
		return $this->parser->parseSql($boolAplicarParams);
	}


	/**
	 * Função utilizada para obter as propriedades inacessíveis desta classe (wheres, joins, selects, etc.)
	 * Utilizar o prefixo get*
	 * 
	 * @param $strNome Nome
	 * 
	 * @return mixed
	 */
	public function __call($strNome, $strArgs)
	{
		if (strpos($strNome, 'get') !== false){
			$strProp = lcfirst(substr($strNome, 3));
			if (property_exists($this, $strProp)) return $this->{$strProp};
		}

		throw new \BadMethodCallException("Função {$strNome}() não existe na classe ". __CLASS__);
	}

}
