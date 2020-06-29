<?php

namespace Core\Views;

/**
 * Description of ViewEngine
 *
 * @author Usuario
 */
class ViewCompiler
{

	private $cacheDir = __DIR__ . '/cache';
	private $file;
	private $diretivasComando = ['if', 'elseif', 'else', 'switch'];
	private $diretivasEnd = ['endif', 'endfor', 'endforeach', 'endswitch', 'endwhile', 'break'];
	private $echoTipo = '<?= ';
	private $diretivasCustom = [];

	public function diretiva($nome, $callback)
	{
		$this->diretivasCustom[$nome] = $callback;
	}

	private function php($str)
	{
		return "<?php $str ?>";
	}

	private function compilarDiretivasPadrao($nome, $expressao = '')
	{
		
	}

	private function compilarDiretiva($nome, $expressao = '')
	{
		if (in_array($nome, $this->diretivasComando)) {
			return $this->php("{$nome}{$expressao} :");
		}

		if (in_array($nome, $this->diretivasEnd)) {
			return $this->php("$nome;");
		}

		if (in_array($nome, array_keys($this->diretivasCustom))) {
			return $this->compilarDiretivasCustom($nome, $expressao);
		}
		
		if ($nome == 'case'){
			return $this->php("$nome" . trim($expressao, '()') . ':');
		}
	}

	private function compilarDiretivasCustom($nome, $expressao = '')
	{
		$callback = $this->diretivasCustom[$nome];
		return $callback(trim($expressao, '()'));
	}

	private function compilarEchos($content)
	{
		//-- Compila os echos com chaves 
		$content = preg_replace_callback('/{{((?:.|(?:[\r\n\t]|\r\n)*)+)}}/', function($match) {
			return $this->echoTipo . "__esc('{$match[1]}')" . '?>';
		}, $content);
		
		//-- Compila os echos sem escapar os caracteres
		$content = preg_replace_callback('/{{((?:.|(?:[\r\n\t]|\r\n)*)+)}}/', function($match) {
			return $this->echoTipo . $match[1] . '?>';
		}, $content);
	}

	public function compilar($file)
	{
		if (!$content = file_get_contents($file)) {
			throw new \Exception("Erro ao compilar o arquivo $file: Arquivo não encontrado");
		}

		$content = preg_replace_callback('/\B@(\w+)[ \t]*(\([^\r\n]+\))?/', function($match) {
			return $this->compilarDiretiva($match[1], (isset($match[2]) ? $match[2] : ''));
		}, $content);

		$cacheFile = $this->cacheDir . '/' . base64_encode(uniqid()) . '.php';
		if (!file_put_contents($cacheFile, $content)) {
			throw new \Exception("Erro ao compilar o arquivo $file. Não foi possível gerar arquivo de cache");
		}

		return $cacheFile;
	}

}
