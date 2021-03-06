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
	 * 
	 * %prog% install
	 * 
	 * @param array $args Program arguments
	 */
	protected function cmd_install($args) {
		$db = Database::instance();
		$table = Config::item('migrate.table');
		if($db->table_exists($table))
			throw new Migrate_Error('migrate.config_table_exists',$table);
			
		$migration = Kohana::find_file('db','migration.sql',TRUE,TRUE);
		
		$sql = file_get_contents($migration);
		$sql = str_replace('`migration_table`',"`{$table}`",$sql);
		
		println("Creating table {$table}");
		$db->query($sql);
	}
	/**
	 * Create a new migration script
	 * 
	 * %prog% create <tag name> - Create a new migration
	 * 
	 * @param array $args Program arguments
	 */
	protected function cmd_create($args) {
		$template = Kohana::find_file('db','migration.tmpl',TRUE,TRUE);
		
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
	 * %prog% up - Run all migrations
	 * %prog% up [name] - Revert single migration
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
	 * %prog% down - Revert all migrations
	 * %prog% down [name] - Revert single migration
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
		$file = Kohana::find_file('db',$args[2]);
		if($file===FALSE)
			throw new Migrate_Error('migrate.missing',$args[2]);
		
		println('Running migration '.$args[2]);
		Migrate::run($file, $method);
	}
	/**
	 * List available migrations
	 * 
	 * %prog% list - List all migrations
	 * 
	 * @param unknown_type $args
	 * @todo Reduce the number of queries Migrate::status() has to run
	 */
	protected function cmd_list($args) {
		printf("| %-4s | %-4s | %s\n",'Up','Down','Name');
	    foreach(Migrate::migrations() as $file) {
	    	$status = Migrate::status($file);
	    	$up = (is_object($status) && $status->status=='up')?'X':'';
	    	$down = (is_object($status) && $status->status=='down')?'X':'';
	    	$name = basename($file,'.php');
	    	printf("| %-4s | %-4s | %s\n",$up,$down,$name);
	    }
	}
	
	/**
	 * Run only the next migration
	 * 
	 * %prog% forward - Run only the next migration
	 * 
	 * @param mixed $args
	 */
	protected function cmd_forward($args) {
		$files = Migrate::migrations();
		foreach($files as $file) {
			$status = Migrate::status($file);
			if($status!==NULL && $status->status=='up') continue; // Skip ones already up
			
			// If we found one
			Migrate::run($file,'up');
			break;
		}
	}
	
	/**
	 * Revert the previously run command
	 * 
	 * %prog% revert - Revert the previously run command
	 * 
	 * @param mixed $args
	 */
	protected function cmd_revert($args) {
		$files = Migrate::migrations();
		$files = array_reverse($files); // Reverse order
		foreach($files as $file) {
			$status = Migrate::status($file);
			if($status===NULL) continue;
			if($status->status=='down') continue; // Skip ones already down
			
			// If we found one
			Migrate::run($file,'down');
			break;
		}
	}
	
	/**
	 * Print out help
	 * 
	 * %prog% help - Print this help
	 * 
	 * @param mixed $args Program Arguments
	 */
	public function cmd_help($args) {
		$margin = 30;
		$matches = array();
		$file = file_get_contents(__FILE__);
		// Look for all comments that have %prog% at the beginning
		preg_match_all('/\* \%prog\%(.*?)( - .*?)?$/ism', $file, $matches, PREG_SET_ORDER);
		println('Kohana Migration Script Commands');
		foreach($matches as $line) {
			// Find command and help from regex but ignore missing help msg
			@list(,$cmd,$help) = $line;
			
			$tab = ($margin-strlen($cmd));
			$tab = str_repeat(' ', ($tab>1)?$tab:5);
			println("    {$cmd}{$tab}{$help}");
		}
	}
	/**
	 * Mark a migration as already run
	 * 
	 * %prog% mark <migration> [(up|down)] - Mark a migration as already run (DEVELOPER)
	 * 
	 * @param unknown_type $args
	 * @throws Migrate_Error
	 */
	protected function cmd_mark($args) {
		$file = Kohana::find_file('db',$args[2]);
		if($file===FALSE)
			throw new Migrate_Error('migrate.missing',$args[2]);
			
		$method = isset($args[3])?
			$args[3]:'up';
		
		list($time,$migration) = Migrate::load($file);
		$class = get_class($migration);
		Migrate::mark($time,$class,$method);
	}
}

try {
	$app = new Console_Migrator();
	$app->run($argv);
} catch(Migrate_Error $e) {
	errorln($e);
}

Console_Migrator::query_log();
