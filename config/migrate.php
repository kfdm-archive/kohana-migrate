<?php defined('SYSPATH') or die('No direct script access.');

/**
 * This is where migrations will be stored
 * @var string
 */
$config['path'] = APPPATH."db";

$config['help'] = MIGRATE_MODULE_PATH.'USAGE.md';

$config['template'] = MIGRATE_MODULE_PATH.'bin/_template.php';

$config['table'] = 'migrations';

$config['setup'] = MIGRATE_MODULE_PATH.'bin/_migration.sql';