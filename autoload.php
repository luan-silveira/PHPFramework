<?php

 
/**
 * Procura o caminho do arquivo pelo nome da classe.
 * A busca é feita recursivamente dentro da pasta do projeto. Caso o caminho do arquivo não seja encontrado, tenta encontrar nos
 * respectivos subdiretórios.
 * 
 * Obs.: Os arquivos devem ter O MESMO NOME da classe, e deve ter somente uma declaração de classe por arquivo. 
 * Ou seja, devem ser excluisivamente arquivos de classe. 
 * Podem conter as extensões .php ou .class.php.
 *
 * @param  string $classname Nome da classe.
 * @param  string $dir (Opcional) Diretório de busca. Utilizado apenas na busca recursiva nas subpastas.
 *
 * @return string|bool Retorna o caminho completo do arquivo da classe especificada caso seja encontrado, ou False caso contrário.
 */
function procurarCaminhoArquivoClasse($classname, $dir = __DIR__)
{
	foreach (['php', 'class.php'] as $ext){
		$filename = "$dir/$classname.$ext";
		if (file_exists($filename)) return $filename;
	}

	$dirs = array_filter(scandir($dir), function($d) use ($dir){
		return is_dir("$dir/$d") && !in_array($d, ['.', '..']);
	});
	foreach ($dirs as $d){
		$filename = procurarCaminhoArquivoClasse($classname, "$dir/$d");
		if ($filename) return $filename;
	}

	return false;
}

//-- Registra a função de autocarregamento das classes.
//-- Busca o arquivo pelo nome da classe. Caso seja encontrado, é feito o require do arquivo de classe.
spl_autoload_extensions('.php, .class.php');
spl_autoload_register(function($classname){
	$file = procurarCaminhoArquivoClasse($classname);
	if ($file) {
		require_once $file;
		return true;
	}

	return false;
});