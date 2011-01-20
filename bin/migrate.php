<?php
require(realpath(dirname(__FILE__)).'/_console.php');

class Console_Migrator {
	public function __construct() {
		$path = Config::item('migrate.path');
		if(!file_exists($path)) {
			echo "There was a problem finding the migration directory {$path}\n";
			echo "Please check your configuration and try again\n";
			die();
		}
	}
	/**
	 * Main starting point
	 * @param mixed $argv Commandline arguments
	 */
	public function run($argv) {
		// If there are no arguments we're assuming they want help
		if(!isset($argv[1])) $argv[1] = 'help';
		
		$method = "cmd_{$argv[1]}";
		if(!method_exists($this,$method))
			throw new Console_Error("Could not find method {$method}");
		
		$this->$method($argv);
	}
	/**
	 * Create a new migration script
	 * @param unknown_type $args
	 */
	protected function cmd_create($args) {
		var_dump($args);
	}
	/**
	 * Print out help
	 * @param mixed $args Program Arguments
	 */
	protected function cmd_help($args) {
		echo str_replace('%prog%',$args[0],file_get_contents(Config::item('migrate.help')));
	}
}

try {
	$app = new Console_Migrator();
	$app->run($argv);
} catch(Console_Error $e) {
	die($e);
}
