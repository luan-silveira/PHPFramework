<?php

namespace Core\Views;

class View
{
	private $nome;
	private $dados;
	
	public function __construct($strNome, $arrDados = [])
	{
		$this->nome = $strNome;
		$this->dados = $arrDados;
	}

	public function show()
	{
		$filename = realpath(__DIR__) . "/{$this->nome}.php";
		header('Content-Type: text/html;charset-UTF-8');
		extract($this->dados);
		
		require_once $filename;
	}
	
	public function with($strKeyOuArray, $strValue)
	{
		if (is_array($strKeyOuArray)){
			$this->dados = array_merge($this->dados, $strKeyOuArray);
		} else {
			$this->dados[] = [$strKeyOuArray => $strValue];
		}
		
		return $this;
	}
	
	public static function make($strNome, $arrDados = [])
	{
		return (new self($strNome, $arrDados));
	}
	
	public function __toString()
	{
		ob_start();
		$this->show();
		$ret = ob_get_contents();
		ob_end_clean();
		
		return $ret;
	}
}
