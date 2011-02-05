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
		if(!file_exists($path))
			throw new Migrate_Error('migrate.config_directory',$path);
		$this->db = Database::instance();
	}
	/**
	 * Print out the list of Database queries
	 * 
	 * Queries that contain the IGNORE comment will not
	 * be printed so that we have an easy way to ignore 
	 * 'housekeeping' queries from the output
	 */
	public static function query_log() {
		foreach(Database::$benchmarks as $b) {
			if(strrpos($b['query'],'/* IGNORE */')!==FALSE) continue;
			$sql = str_replace(array("\n","\t"),' ',$b['query']);
			errorln("{$sql}\n\t#Time: {$b['time']}  Rows: {$b['rows']}");
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
			throw new Migrate_Error('migrate.invalid_command',$argv[1]);
		
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
			throw new Migrate_Error('migrate.config_table_exists',$table);
		$migration = Config::item('migrate.setup');
		
		$sql = file_get_contents($migration);
		$sql = str_replace('`migration_table`',"`{$table}`",$sql);
		
		println("Creating table {$table}");
		$db->query($sql);
	}
	/**
	 * Create a new migration script
	 * 
	 * <code>
	 * %prog% create <tag name>
	 * </code>
	 * 
	 * @param array $args Program arguments
	 */
	protected function cmd_create($args) {
		$template = Config::item('migrate.template');
		
		if(!isset($args[2]))
			throw new Migrate_Error('migrate.args_tag');
		
		$tag = implode(' ',array_slice($args,2));
		list($class, $file) = Migrate::encode(time(), $tag);
		
		$body = file_get_contents($template);
		if($body===FALSE)
			throw new Migrate_Error('migrate.config_template',$template);
		
		$body = str_replace('Template_Migration',$class,$body);
			
		if(file_put_contents($file, $body)===FALSE)
			throw new Migrate_Error('migrate.write_migration',$file);
		
		echo "Created migration [{$class}] in {$file}\n";
	}
	/**
	 * Apply a migration
	 * 
	 * <code>
	 * # Run all migrations
	 * %prog% up
	 * # Run single migration with [name]
	 * %prog% up [name]
	 * </code>
	 * 
	 * @param mixed $args
	 */
	protected function cmd_up($args) {
		return isset($args[2])?
			$this->_cmd_run_one($args,'up'):
			$this->_cmd_go($args,'up');
	}
	/**
	 * Revert a migration
	 * 
	 * <code>
	 * # Revert all migrations
	 * %prog% down
	 * # Revert single migration with [name]
	 * %prog% down [name]
	 * </code>
	 * 
	 * @param mixed $args
	 */
	protected function cmd_down($args) {
		return isset($args[2])?
			$this->_cmd_run_one($args,'down'):
			$this->_cmd_go($args,'down');
	}
	/**
	 * Helper function to run all migrations
	 * @param mixed $args
	 * @param string $method up|down
	 */
	protected function _cmd_go($args,$method) {
		println('Running migrations...');
		
		$files = Migrate::migrations();
		
		if($method==='down') $files = array_reverse($files);
		
		foreach($files as $file)
			Migrate::run($file, $method);
	}
	/**
	 * Helper function to run single migration
	 * @param mixed $args
	 * @param string $method up|down
	 * @throws Migrate_Error
	 */
	protected function _cmd_run_one($args,$method) {
		foreach(Migrate::migrations() as $file)
			if(basename($file,'.php')==$args[2])
				$run = $file;
		if(!isset($run))
			throw new Migrate_Error('migrate.missing',$args[2]);
		
		println('Running migration '.$args[2]);
		Migrate::run($run, $method);
	}
	/**
	 * List available migrations
	 * 
	 * <code>
	 * %prog% list
	 * </code>
	 * 
	 * @param unknown_type $args
	 */
	protected function cmd_list($args) {
	    foreach(Migrate::migrations() as $file)
	        println(basename($file,'.php'));
	}
	/**
	 * Print out help
	 * 
	 * <code>
	 * %prog% help
	 * </code>
	 * 
	 * @param mixed $args Program Arguments
	 */
	public function cmd_help($args) {
		echo str_replace('%prog%',$args[0],file_get_contents(Config::item('migrate.help')));
	}
}

try {
	$app = new Console_Migrator();
	$app->run($argv);
} catch(Migrate_Error $e) {
	errorln($e);
}

Console_Migrator::query_log();
