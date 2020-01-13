<?php

namespace Core\Database;

use PDOException;
/**
 * Description of QueryException
 *
 * @author Usuario
 */
class QueryException extends PDOException
{
	use SqlParams;
	
	private $sql;
	
	public function __construct(PDOException $e, $sql, $params)
	{
		$this->sql = $this->aplicarParametrosSql($sql, $params);
		$this->message = $e->getMessage() . "\n Query: " . $this->sql;
		$this->code = $e->getCode();
		$this->file = $e->getFile();
		$this->line = $e->getLine();
	}
	
	public function getSql()
	{
		return $this->sql;
	}
	
}
