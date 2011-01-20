<?php

//Make it look like a web server
isset($_SERVER['SERVER_NAME'])		or $_SERVER['SERVER_NAME'] = 'localhost';
isset($_SERVER['HTTP_USER_AGENT'])	or $_SERVER['HTTP_USER_AGENT'] = 'console';

//From index.php Driver
error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', TRUE);
define('EXT', '.php');

define('MIGRATE_MODULE_PATH',realpath(dirname(__FILE__).'/..').'/');
define('DOCROOT', realpath(MIGRATE_MODULE_PATH.'/../..').'/');
#define('KOHANA',  DOCROOT.'public/index.php');
define('APPPATH', str_replace('\\', '/', realpath(DOCROOT.'application')).'/');
define('SYSPATH', str_replace('\\', '/', realpath(DOCROOT.'system')).'/');

//From Bootstrap.php
define('KOHANA_VERSION',  '2.1.1');
define('KOHANA_CODENAME', 'Schneefeier');
define('SYSTEM_BENCHMARK', uniqid());
require SYSPATH.'core/Benchmark'.EXT;
defined('E_KOHANA') or define('E_KOHANA', 42);
defined('E_PAGE_NOT_FOUND') or define('E_PAGE_NOT_FOUND', 43);
defined('E_DATABASE_ERROR') or define('E_DATABASE_ERROR', 44);
defined('E_RECOVERABLE_ERROR') or define('E_RECOVERABLE_ERROR', 4096);

require SYSPATH.'core/utf8'.EXT;
require SYSPATH.'core/Config'.EXT;
require SYSPATH.'core/Log'.EXT;
require SYSPATH.'core/Event'.EXT;
require SYSPATH.'core/Kohana'.EXT;

restore_error_handler();
restore_exception_handler();

class Console_Controller extends Controller {}
Kohana::$instance = new Console_Controller();
ob_end_flush();
