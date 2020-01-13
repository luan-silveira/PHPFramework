<?php

namespace App\Model;

use Database\DB;

/**
 * Description of Model
 *
 * @author Usuario
 */
abstract class Model
{

	use \MetodoGetSet;

	protected $conexao;
	protected $tabela;
	protected $primaryKey = 'id';
	protected $dados = [];
	private $novo = false;
	private $colunasModificadas = [];
	
	private $db;

	public function __construct()
	{
		if (!$this->conexao)
			$this->conexao = database('padrao');
		if (!$this->tabela)
			$this->tabela = (new \ReflectionClass($this))->getShortName();

		$this->novo = true;
	}

	public function __call($name, $arguments)
	{
		if (!$this->manipularMetodosGetSetDados($this->dados, $name, $arguments[0])) {
			return call_user_func_array([DB::class, $name], $arguments);
		}
		throw new BadMethodCallException("A função '$name' não existe na classe " . static::class);
	}

	public function __callStatic($name, $arguments)
	{
		return call_user_func_array([DB::class, $name], $arguments);
	}

	public function __get($name)
	{
		return $this->dados[$name] ?? null;
	}

	public function __set($name, $value)
	{
		$this->dados[$name] = $value;
		if (!$this->novo) {
			$this->colunasModificadas[] = $name;
		}
	}

	public function __isset($name)
	{
		return isset($this->dados[$name]);
	}

	public function fill($arrDados)
	{
		$this->dados = array_merge($this->dados, $arrDados);
		if (!$this->novo) {
			$this->colunasModificadas = array_merge($this->colunasModificadas, array_keys($arrDados));
		}
	}

	public function store()
	{
		$db = $this->getDB();
		if ($this->novo) {
			if (!empty($this->dados)) {
				$id = $db->insert($this->dados);
				if ($id !== 0) {
					$this->{$this->primaryKey} = $id;
				}
			}
			return true;
		} else {
			if ($this->isModificado()) {
				return $db->update(\Arr::somente($this->dados, $this->colunasModificadas));
			}
		}

		return false;
	}

	private function isModificado()
	{
		return !empty($this->colunasModificadas);
	}

	/**
	 * 
	 * @return DB;
	 */
	private function getDB()
	{
		return DB::conexao($this->conexao)->table($this->tabela);
	}

}
