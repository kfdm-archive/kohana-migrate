<?php defined('SYSPATH') or die('No direct script access.');

if(!defined('MIGRATE_MODEL_PATH')) {
	define('MIGRATE_MODULE_PATH',realpath(dirname(__FILE__).'/..').'/');
}

/**
 * This is where migrations will be stored
 * @var string
 */
$config['path'] = APPPATH."db";

$config['template'] = MIGRATE_MODULE_PATH.'bin/_template.php';

$config['table'] = 'migrations';

$config['setup'] = MIGRATE_MODULE_PATH.'bin/_migration.sql';

$config['driver'] = 'mysql';
