<?php

namespace Database;

use PDO,
	PDOStatement,
	PDOException;

class DB
{

	/**
	 * Array de instâncias de conexões
	 * 
	 * @var array
	 */
	private static $arrConexoes;

	/**
	 * Instância da conexão de banco de dados.
	 * 
	 * @var PDO
	 */
	private $pdo;

	/**
	 * Nome da conexão atual.
	 * 
	 * @var string
	 */
	private static $strConexaoAtual;

	/**
	 * Cria uma nova conexão de banco de dados.
	 * 
	 * @param array $arrConfig
	 */
	private function __construct($arrConfig = [])
	{
		extract($arrConfig);

		$this->pdo = new PDO("mysql:dbname=$banco;host=$host;port=$porta", $usuario, $senha);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Retorna uma nova conexão com o nome informado.
	 * O nome da conexão deve ser um dos que estão especificados no arquivo config/database.php;
	 * 
	 * @param type $database
	 */
	public static function conexao($database, $boolNovaInstancia = false)
	{
		if ((!isset(self::$arrConexoes[$database])) || $boolNovaInstancia) {
			$db = new self(database($database));
			self::$arrConexoes[$database] = $db;
			return $db;
		}

		return self::$arrConexoes[$database];
	}

	public static function getDefault()
	{
		return self::conexao(config('database.padrao'));
	}

	public static function get()
	{
		if (!self::$strConexaoAtual)
			self::$strConexaoAtual = config('database.padrao');
		return self::conexao(self::$strConexaoAtual);
	}

	public static function setConexao($strConexao)
	{
		self::$strConexaoAtual = $strConexao;
	}

	/**
	 * Define a conexão PDO.
	 * 
	 * @param PDO $pdo Objeto de conexão PDO
	 * 
	 * @return Connection
	 */
	public function setPDO($pdo)
	{
		$this->pdo = $pdo;
		return $this;
	}
	
	/**
	 * Retorna a instância da conexão PDO.
	 * 
	 * @return PDO
	 */
	public function getPDO()
	{
		return $this->pdo;
	}

	/**
	 * Executa um comando SQL com um callback para cada tipo de operação a ser executada.
	 * 
	 * @param string $strSQL Comando SQL
	 * @param array $arrParams Array contendo os parâmetros a serem passados ao SQL.
	 * @param \Closure|callback $callback Função de callback
	 * 
	 * 
	 * @return mixed Retorna o retorno da função callback. Caso o callback não seja informado, retorna True em caso de sucesso ou False em caso de falha.
	 * 
	 * @throws PDOException
	 */
	public function exec($strSQL, $arrParams, $callback = null)
	{
		$pdoStmt = $this->aplicarParametrosPdoPstatement($this->pdo->prepare($strSQL, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]), $arrParams);
		try {
			if (!$pdoStmt->execute()) return false;
			return $callback ? $callback($pdoStmt) : true;
		} catch (PDOException $e){
			throw new QueryException($e, $strSQL, $arrParams);
		}		
	}

	/**
	 * Aplica os parâmetros ao comando SQL preparado.
	 * 
	 * @param PDOStatement $pdoStmt    Objeto PDOStatement do comando preparado para execução.
	 * @param array		    $arrParams Array de parâmetros a serem passados para o comando SQL preparado.
	 * 
	 * @return PDOStatement Retorna o objeto PDOStatement com os parâmetros aplicados.
	 * 
	 * @throws PDOException
	 */
	public function aplicarParametrosPdoPstatement($pdoStmt, $arrParams)
	{
		
		foreach ($arrParams as $i => $strValor) {
			$pdoStmt->bindValue($i + 1, $strValor, (is_int($strValor) ? PDO::PARAM_INT : PDO::PARAM_STR));
		}
		
		return $pdoStmt;
	}

	/**
	 * Executa um comando SQL preparado do tipo INSERT, UPDATE ou DELETE.
	 * 
	 * @param string $strSQL Comando SQL
	 * @param type $arrParams Array de parâmetros a serem aplicados
	 * @param bool $boolLinhasAfetadas Define se a função irá retornar o número de linhas afetadas.
	 * 
	 * @return int|bool Retorna o número de linhas afetadas, caso o parâmetro $boolLinhasAfetadas seja informado como True, ou True caso
	 * 					o comando seja executado com êxito. Retorna False em caso de falha.
	 */
	public function comandoSQL($strSQL, $arrParams, $boolLinhasAfetadas = false)
	{
		$callback = function(PDOStatement $stmt) {
			return $stmt->rowCount();
		};

		return $this->exec($strSQL, $arrParams, $boolLinhasAfetadas ? $callback : null);
	}

	/**
	 * Executa um comando SQL não preparado.
	 * 
	 * @param string $sql Comando SQL
	 * 
	 * @return int|bool Retorna o número de linhas afetadas, ou False em caso de falha.
	 */
	public function rawSQL($sql)
	{
		return $this->pdo->exec($sql);
	}

	/**
	 * Executa o comando LOCK TABLES para travar as tabelas em modo de escrita (WRITE).
	 * 
	 * @param string $tabelas Tabelas a serem travadas no modo de escrita.
	 * 
	 * @return bool Retorna True caso o comando seja executado com sucesso ou False caso contrário.
	 */
	public function lockTables(...$tabelas)
	{
		if (empty($tabelas))
			return false;

		$sql = 'LOCK TABLES ' . implode(', ', array_map(function($tbl) {
							return "$tbl WRITE";
						}, $tabelas));
		return $this->rawSQL($sql) !== false ? true : false;
	}

	public function unlockTables()
	{
		return $this->rawSQL('UNLOCK TABLES') !== false ? true : false;
	}

	/**
	 * Inicia uma transação no banco de dados e trava as tabelas informadas.
	 * 
	 * @param string $lockTables Tabelas a serem travadas no modo de escrita.
	 */
	public function beginTransaction(...$lockTables)
	{
		if (!empty($lockTables)) {
			$this->lockTables(...$lockTables);
		}
		return $this->pdo->beginTransaction();
	}

	public function commit()
	{
		return $this->pdo->commit() && $this->unlockTables();
	}

	public function rollback()
	{
		return $this->pdo->rollBack() && $this->unlockTables();
	}

	public function inTransaction()
	{
		return $this->pdo->inTransaction();
	}

	/**
	 * Executa um comando SQL preparado e retorna a primeira linha do registro.
	 * 
	 * @param string $strSQL	Comando SQL preparado (com placeholders '?')
	 * @param array  $arrParams Array de parâmetros
	 * @param bool   $boolArray Define se os registros retornados serão objetos ou array.
	 * @param string $class (Opcional) Classe do objeto no qual será retornado o registro.
	 * @param int $intPosition (Opcional) Posição do cursor
	 * 
	 * @return object|array Se o parâmetro $boolArray for informado como True, retorna um array. Caso contrário, retorna um objeto correspondente
	 *							 ao registro. Se nenhum registro for encontrado, retorna Null.
	 */
	public function fetch($strSQL, $arrParams, $boolArray, $class = 'stdClass', $intPosition = PDO::FETCH_ORI_NEXT)
	{
		return $this->exec($strSQL, $arrParams, function(PDOStatement $stmt) use ($boolArray, $class, $intPosition) {					
					if ($boolArray) {
						return $stmt->fetch(PDO::FETCH_ASSOC, $intPosition) ?: null;
					} else {
						$stmt->setFetchMode(PDO::FETCH_CLASS, $class);
						return $stmt->fetch(PDO::FETCH_CLASS, $intPosition) ?: null;
					}
				});
	}

	/**
	 * Executa um comando SQL preparado e retorna uma lista de registros.
	 * 
	 * @param string $strSQL	Comando SQL preparado (com placeholders '?')
	 * @param array  $arrParams Array de parâmetros
	 * @param bool   $boolArray Define se os registros retornados serão objetos ou array.
	 * @param string $class		(Opcional) Classe do objeto no qual será retornado o registro.
	 * 
	 * @return array|bool Retorna um array representando uma lista de registros.
	 *					  Se o parâmetro $boolArray for informado como True, os registros serão arrays associstivos. Caso contrário,
	 *					  serão instâncias da classe especificada.
	 *					  Se nenhum registro for encontrado, retorna um array vazio.
	 *					  Retorna False em caso de falha.
	 */
	public function fetchAll($strSQL, $arrParams, $boolArray = false, $class = 'stdClass')
	{
		return $this->exec($strSQL, $arrParams, function(PDOStatement $stmt) use ($boolArray, $class) {
					return $boolArray ? $stmt->fetchAll(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_CLASS, $class);
				});
	}

	/**
	 * Retorna um novo objeto Expression para encapsular expressões SQL que não devem ser escapadas com aspas 
	 * e devem ser executadas assim como estão.
	 * 
	 * @param type $strValue Expressão SQL.
	 * @return Expression Objeto Expression contendo o valor da expressão.
	 */
	public static function raw($strValue)
	{
		return new Expression($strValue);
	}

	/**
	 * Inicia uma nova consulta SQL.
	 * 
	 * @return \Database\QueryBuilder Retorna um novo objeto QueryBuilder para montar uma consulta.
	 */
	public function query()
	{
		return new QueryBuilder($this);
	}

	/**
	 * Define a tabela na qual será realizada a consulta SELECT FROM.
	 * 
	 * @param string $strTabela Nome da tabela
	 * 
	 * @return QueryBuilder
	 */
	public static function table($strTabela)
	{
		return self::get()->query()->table($strTabela);
	}

	/**
	 * Encaminha as chamadas das funções estáticas inacessíveis para a instância atual da classe.
	 * 
	 * @param string $name Método
	 * @param string $arguments Argumentos
	 * 
	 * @return DB
	 */
	public static function __callStatic($name, $arguments)
	{
		return self::get()->{$name}(...$arguments);
	}

	/**
	 * Encaminha as chamadas das funções inacessíveis para os métodos da classe QueryBuilder.
	 * 
	 * @param string $name Método
	 * @param string $arguments Argumentos
	 * 
	 * @return QueryBuilder
	 */
	public function __call($name, $arguments)
	{
		return $this->query()->{$name}(...$arguments);
	}

}
