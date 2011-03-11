<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Primary migration 'controller' class
 * This class contains helper functions and methods related
 * to managing which migrations have run or haven't run and
 * calling individual (or groups) of migrations
 */
class Migrate_Core {
	/**
	 * Simple logger for Migration
	 * 
	 * While running in a console it will print to STDOUT with \n
	 * and while running in a page will print ending with a <br />
	 * @param string $string
	 */
	public static function log($string) {
		if(defined('STDOUT'))
			fwrite(STDOUT, "{$string}\n");
		else
			echo "{$string}<br />\n";
	}
	/**
	 * Generate migration classname
	 * @param integer $time Unix timestamp
	 * @param string $tag Tags with whitespace in the form "word word word"
	 * @return string Classname
	 */
	public static function classname($time,$tag) {
		$hash = strtoupper(dechex($time));
		
		// Convert everything extra to spaces
		$tag = preg_replace('/[^a-z0-9]+/', ' ', strtolower($tag));
		// Uppercase words and then replace spaces with _
		$class = preg_replace('/\s+/', '_', ucwords($tag));
		
		return "{$class}_{$hash}_Migration";
	}
	/**
	 * Encode a filename and class
	 * @param integer $time Unix timestamp
	 * @param string $tag Tag string
	 * @return array array($class, $file)
	 */
	public static function encode($time,$tag) {
		$path = Config::item('migrate.path');
		
		$date = strftime('%Y-%m-%d', $time);
		$hash = strtoupper(dechex($time));
		$class = self::classname($time,$tag);
		$tag = url::title($tag,'-');
		
		$file = "{$path}/{$date}.{$tag}.{$hash}.php";
		
		return array($class, $file);
	}
	/**
	 * Decode a file name into the parts we need
	 * @param string $file
	 * @return array array($date, $class)
	 */
	public static function decode($file) {
		list($date,$tag,$hash,$ext) = explode('.',basename($file));
		$time = hexdec($hash); // Extract Unix timestamp
		$class = self::classname($time,str_replace('-',' ',$tag));
		return array($time,$class);
	}
	
	public static function load($file) {
		list($time,$class) = self::decode($file);
		
		require_once($file);
		
		$migration = new $class($time);
		return array($time,$migration);
	}
	/**
	 * Get a list of migration files
	 * 
	 * Uses the Kohana::list_files() function but skips
	 * files that don't end in .php
	 * 
	 * Since the time part we store in a file is in hex
	 * to reduce the length, we have to decode that when
	 * we read the file, and use that as an array key to
	 * sort with.
	 * 
	 * @return array List of migration folders
	 */
	public static function migrations() {
		$files = array();
		foreach(Kohana::list_files('db') as $file) {
			if(substr_compare($file, '.php', -4, 4) === 0) {
				list($time,$class) = self::decode($file);
				$files[$time] = $file;
			}
		}
		ksort($files,SORT_NUMERIC);
		return $files;
	}
	/**
	 * Query the status of a migration by file
	 * @param string $file Migration file
	 * @return NULL|Database_Result
	 */
	public static function status($file) {
		$db = Database::instance();
		list($time,$class) = self::decode($file);
		$r = $db->query('SELECT * FROM `migrations` WHERE `created_on` = FROM_UNIXTIME(?) /* IGNORE */',array($time));
		return count($r)?
			$r->current():
			NULL;
	}
	
	/**
	 * Run a migration
	 * This function is responsible for running a migration and
	 * keeping track of migrations to prevent them running again
	 * 
	 * 'Housekeeping' queries will run with a comment IGNORE appended
	 * to the end so that the query_log() function can ignore printing
	 * those out
	 * 
	 * @param string $file Path to migration file
	 * @param string $method (up|down)
	 * @see Console_Migrator::query_log()
	 */
	public static function run($file,$method) {
		$db = Database::instance();
		list($time,$migration) = self::load($file);
		$class = get_class($migration);
		
		// Check to see if this migration has already been run
		$r = $db->query('SELECT * FROM `migrations` WHERE `created_on` = FROM_UNIXTIME(?) /* IGNORE */',array($time));
		if(count($r)>0 && $r->current()->status==$method)
			return;
			
		// If we are attempting to run the 'down' migration but the corrisponding
		// 'up' migration has never been run, then skip
		if(count($r)==0 && $method=='down')
			return;
			
		// Start Marker
		Database::$benchmarks[] = array(
			'query'=>"/* Running {$class}->{$method} */",
			'time'=>0,
			'rows'=>0,
		);
		
		self::log("Running {$class}->{$method}()");
		
		$start = microtime(TRUE);
		$migration->$method();
		$stop = microtime(TRUE);
		
		// End Marker
		Database::$benchmarks[] = array(
			'query'=>"/* Ran {$class}->{$method} */",
			'time'=>($stop - $start),
			'rows'=>0,
		);
		
		self::mark($time, $class, $method);
	}
	/**
	 * Mark a migration's state
	 * 
	 * Partly for debugging/testing purposes we keep the code to 
	 * actually mark a migration as run, in it's own function
	 * 
	 * @param integer $time Unix timestamp used as migration identifier
	 * @param string $class Migration class name to store as a human readable reference
	 * @param string $method (up|down)
	 */
	public static function mark($time,$class,$method) {
		$db = Database::instance();
		$sql = 'INSERT INTO `migrations` (`created_on`,`migrated_on`,`class`,`status`) VALUES (FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,?)';
		$sql .= ' ON DUPLICATE KEY UPDATE `migrated_on` = VALUES(`migrated_on`), `status` = VALUES(`status`) /* IGNORE */';
		$db->query($sql,array($time,time(),$class,$method));
	}
}
