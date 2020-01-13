<?php

/*
 * Funções globais úteis do sistema.
 */

/**
 * Busca o valor de uma variável de ambiente
 * 
 * @param string $name  Nome da variável.
 * @param type $default Valor retornado caso a variável não exista.
 * 
 * @return string Retorna o valor da variável especificada.
 */
function env($name, $default = null)
{
	if (!$_ENV) {
		$_ENV = parse_ini_file(".env");
	}
	return isset($_ENV[$name]) ? $_ENV[$name] : $default;
}

/**
 * Obtém um atributo de configuração de um aquivo contido na pasta config.
 * 
 * @param string $attr Nome do atributo de configuração. Deve estar no formato 'nome_do_arquivo.atributo.valor', com os subvalores pontuados.
 * 
 * @return string Retorna o valor do stributo solicitado.
 */
function config($attr)
{
	$arrAtributos = explode('.', rtrim($attr, '.'));
	$filename = 'config/' . array_shift($arrAtributos) . '.php';
	if (!file_exists($filename)) {
		throw new Exception("O arquivo de configuração '$filename' não existe!");
	}


	$arrDados = require $filename;
	$valor = null;
	foreach ($arrAtributos as $strAtributo) {
		if ((!is_array($arrDados)) || (!isset($arrDados[$strAtributo]))) {
			throw new Exception("O atributo '$strAtributo' não existe em $filename");
		}

		$valor = $arrDados[$strAtributo];
		$arrDados = $valor;
	}

	return $valor;
}

/**
 * Retorna os dados da conexão do banco de dados definida em config/database.php
 * 
 * @param string $conn Nome da conexão
 * 
 * @return array
 */
function database($conn)
{
	return config("database.conexoes.$conn");
}

/**
 * Gera um dump da variável e interrompe o script atual
 * 
 * 
 * @param mixed $obj
 */
function dump($obj)
{
	header('Content-Type: text/html');
	var_dump($obj);
	exit();
}

function camelCaseToUnderline($string)
{
	preg_match_all('/[a-z][A-Z]/', $string, $matches);
	foreach ($matches[0] as $match) {
		$c = $match[0] . '_' . strtolower($match[1]);
		$string = str_replace($match, $c, $string);
	}

	return $string;
}
