<?php


namespace Database;

/**
 * Esta classe representa uma expressão do banco de dados que nÃ£o deve ser escapada com aspas.
 */
class Expression
{
	/**
	 * Expressão de banco de dados
	 * 
	 * @var string 
	 */
	protected $expression;
	
	
	public function __construct($expression)
	{
	$this->expression = $expression;
	}    
	
	public function get()
	{
	return $this->expression;
	}
	
	public function __toString()
	{
	return (string) $this->expression;
	}

}
