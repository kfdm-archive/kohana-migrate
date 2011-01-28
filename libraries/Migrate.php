<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Primary migration 'controller' class
 * This class contains helper functions and methods related
 * to managing which migrations have run or haven't run and
 * calling individual (or groups) of migrations
 */
class Migrate_Core {
	/**
	 * Generate migration classname
	 * @param integer $time Unix timestamp
	 * @param string $tag Tags with whitespace in the form "word word word"
	 * @return string Classname
	 */
	public static function classname($time,$tag) {
		$hash = strtoupper(dechex($time));
		$class = str_replace(' ','_',ucwords($tag));
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
		
		$date = strftime('%F', $time);
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
		
		$r = $db->query('SELECT * FROM `migrations` WHERE `created_on` = FROM_UNIXTIME(?) /* IGNORE */',array($time));
		if(count($r)>0 && $r->current()->status==$method)
			return; // Already Run 
		
		// Start Marker
		Database::$benchmarks[] = array(
			'query'=>"/* Running {$class}->{$method} */",
			'time'=>0,
			'rows'=>0,
		);
			
		$start = microtime(TRUE);
		$migration->$method();
		$stop = microtime(TRUE);
		
		// End Marker
		Database::$benchmarks[] = array(
			'query'=>"/* Ran {$class}->{$method} */",
			'time'=>($stop - $start),
			'rows'=>0,
		);
		
		$sql = 'INSERT INTO `migrations` (`created_on`,`migrated_on`,`status`) VALUES (FROM_UNIXTIME(?),FROM_UNIXTIME(?),?)';
		$sql .= ' ON DUPLICATE KEY UPDATE `migrated_on` = VALUES(`migrated_on`), `status` = VALUES(`status`) /* IGNORE */';
		$db->query($sql,array($time,time(),$method));
		
		
	}
}
