<?php

namespace App\Routes;

use App\Routes\Request;

class Router
{
    private $request;
    private $supportedHttpMethods = [
		'GET',
		'POST',
	];

	private static $instance;

    public function __construct(Request $request)
    {
        $this->request = $request;
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self(new Request);
		}

		return self::$instance;
	} 

    public static function __callStatic($name, $args)
    {
		$router = self::getInstance();

        list($route, $method) = $args;
        if (!in_array(strtoupper($name), $router->supportedHttpMethods)) {
            $router->invalidMethodHandler();
        }
        $router->{strtolower($name)}[$router->formatRoute($route)] = $method;
	}

    /**
     * Formata a rota removendo a barra no final. Se a rota for a rota principal (index), retorna a barra '/';
     *
     * @param string String representando a rota.
     * 
     * @return string
     */
    private function formatRoute($route)
    {
        $result = trim($route, '/');
        if ('' === $result) {
            return '/';
        }

        return $result;
    }

    private function invalidMethodHandler()
    {
        header("{$this->request->getServerProtocol()} 405 Method Not Allowed");
    }

    private function defaultRequestHandler()
    {
        header("{$this->request->getServerProtocol()} 404 Not Found");
    }

    /**
     * Busca o m�todo de acordo com o a rota formatada.
     * Se o URL tiver par�metros, retorna tamb�m os par�metros.
     * 
     * @param string $route Rota formatada.
     * @param array &$params Array com os valores dos par�metros que foram informados na rota ($route) 
     * 
     * @return mixed Retorna o m�todo cadastrado na rota, podendo ser uma string ou uma fun��o lambda.
     */
    public function getRouteMethod($route, &$params)
    {
        $methodDictionary = $this->{strtolower($this->request->getRequestMethod())};
        foreach ($methodDictionary as $routeName => $method) {
            $regexRoute = str_replace('/', '\/', $routeName);
            $regexRoute = preg_replace('/{[A-Za-z]\w*}/', '(\w+)', "/^$regexRoute$/");

            if (preg_match($regexRoute, $route, $matches)){
                $params = array_slice($matches, 1);
                return $method;
            }
        }

        return null;
    }

    /**
     * Resolves a route.
     */
    public function resolve()
    {
        $route = str_replace(env('APP_HOME'), "", $this->request->getRequestUri()) ;
        $formatedRoute = $this->formatRoute($route);
        $arrParams = [];
        $method = $this->getRouteMethod($formatedRoute, $arrParams);
        if (is_null($method)) {
            $this->defaultRequestHandler();

            return;
		}
		
		if (is_string($method) && strpos($method, '@') !== false){
            $method = explode('@', $method, 2);
            $method[0] = new $method[0];
		}

        echo call_user_func($method, $this->request, ...$arrParams);
    }

    public function __destruct()
    {
        $this->resolve();
    }
}
