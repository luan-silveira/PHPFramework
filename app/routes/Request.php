<?php

namespace App\Routes;

class Request
{
	use \MetodoGetSet;

	private $serverData = [];
	private $bodyData = [];

    public function __construct()
    {
		$this->parseServerData();
		$this->parseBodyData();
	}
	
	public function __call($name, $args)
	{
		if (!$this->manipularMetodosGetSetDados($this->serverData, $name, $args[0])){
			throw new BadMethodCallException("A função $name não existe na classe " . __CLASS__);
		}
	}

	public function __get($name)
	{
		return isset($this->bodyData[$name]) ? $this->bodyData[$name] : null;
	}

    private function parseServerData()
    {
        foreach ($_SERVER as $key => $value) {
            $this->serverData[$this->toCamelCase($key)] = $value;
        }
	}
	
	private function parseBodyData()
	{
		if ($this->getRequestMethod() == "GET"){
			$request = $_GET;
			$filterInput = INPUT_GET;
		} elseif ($this->getRequestMethod() == "POST") {
			$request = $_POST;
			$filterInput = INPUT_POST;
		}

		foreach ($request as $key => $value) {
			$this->bodyData[$key] = filter_input($filterInput, $key, FILTER_SANITIZE_SPECIAL_CHARS);
		}
	}

    private function toCamelCase($string)
    {
        $result = strtolower($string);

        preg_match_all('/_[a-z]/', $result, $matches);
        foreach ($matches[0] as $match) {
            $c = str_replace('_', '', strtoupper($match));
            $result = str_replace($match, $c, $result);
        }

        return $result;
    }

}
