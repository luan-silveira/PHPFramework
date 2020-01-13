<?php

return [
	/*
	 * Banco de dados padrão
	 */
	'padrao' => 'mysql',
	
	/*
	 *  Lista de conexões do banco de dados
	 */
	'conexoes' => [
		'mysql' => [
			'host' => env('DB_HOST', 'localhost'),
			'porta' => env('DB_PORT', 3306),
			'banco' => env('DB_DATABASE'),
			'usuario' => env('DB_USERNAME', 'root'),
			'senha' => env('DB_PASSWORD', ''),
		],
		
		'publico' => [
			'host' => env('DB_HOST', 'localhost'),
			'porta' => env('DB_PORT', 3306),
			'banco' => 'db_publico',
			'usuario' => env('DB_USERNAME', 'root'),
			'senha' => env('DB_PASSWORD', ''),
		],
	],
];
