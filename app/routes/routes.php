<?php

use Core\Views\View;
use Core\HTTP\Request;
use Core\HTTP\Router;

Router::get('/', 'TesteController@teste');