<?php

use Core\Views\View;
use Core\HTTP\Request;
use Core\HTTP\Router;

Router::get('/', function(Request $request){
	return View::make('home', ['teste' => 'Teste! dfgdfgdfgdfg']);
});

Router::get('teste1', function(Request $request){
	return 'Rota 1';
});

Router::get('teste2', function(Request $request){
	return 'Rota 2';
});

Router::get('param/{param}/{param2}', function(Request $request, $param, $param2){
	return 'Rota com 2 parametros: ' . "$param, $param2";
});