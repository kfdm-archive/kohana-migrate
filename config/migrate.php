<?php defined('SYSPATH') or die('No direct script access.');

if(!defined('MIGRATE_MODULE_PATH')) {
	define('MIGRATE_MODULE_PATH',realpath(dirname(__FILE__).'/..').'/');
}

/**
 * This is where migrations will be stored
 * @var string
 */
$config['path'] = APPPATH."db";

$config['table'] = 'migrations';

$config['driver'] = 'mysql';
