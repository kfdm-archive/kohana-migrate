<?php
require(realpath(dirname(__FILE__)).'/_console.php');

function println($string) {
	fwrite(STDOUT, "{$string}\n");
}

function errorln($string) {
	fwrite(STDERR, "{$string}\n");
}

class Console_Migrator {
	public function __construct() {
		$path = Config::item('migrate.path');
		if(!file_exists($path)) {
			echo "There was a problem finding the migration directory {$path}\n";
			echo "Please check your configuration and try again\n";
			die();
		}
		$this->db = Database::instance();
	}
	/**
	 * Print out the list of Database queries
	 */
	public static function query_log() {
		foreach(Database::$benchmarks as $b)
			errorln(str_replace(array("\n","\t"),' ',$b['query']));
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
	 * Install Migration System
	 * @param array $args Program arguments
	 */
	protected function cmd_install($args) {
		$db = Database::instance();
		$table = Config::item('migrate.table');
		if($db->table_exists($table))
			throw new Console_Error("Migration table '{$table}' already exists");
		$migration = Config::item('migrate.setup');
		
		$sql = file_get_contents($migration);
		$sql = str_replace('`migration_table`',"`{$table}`",$sql);
		
		println("Creating table {$table}");
		$db->query($sql);
	}
	/**
	 * Create a new migration script
	 * @param array $args Program arguments
	 */
	protected function cmd_create($args) {
		$template = Config::item('migrate.template');
		
		if(!isset($args[2]))
			throw new Console_Error('Missing tag name');
		
		$tag = implode(' ',array_slice($args,2));
		$date = Migrate::date();
		$class = Migrate::classname($date,$tag);
		$file = Migrate::filename($date,$tag);
		
		$body = file_get_contents($template);
		if($body===FALSE)
			throw new Console_Error('Error reading template');
		
		$body = str_replace('Template_Migration',$class,$body);
			
		if(file_put_contents($file, $body)===FALSE)
			throw new Console_Error('Error writing template');
		
		echo "Created migration [{$class}] in {$file}\n";
	}
	/**
	 * Print out help
	 * @param mixed $args Program Arguments
	 */
	public function cmd_help($args) {
		echo str_replace('%prog%',$args[0],file_get_contents(Config::item('migrate.help')));
	}
}

try {
	$app = new Console_Migrator();
	$app->run($argv);
} catch(Console_Error $e) {
	errorln($e);
}

Console_Migrator::query_log();
