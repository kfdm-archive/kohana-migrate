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
		list($date,$class) = self::decode($file);
		
		println($class);
		
		require_once($file);
		
		$migration = new $class();
		return array($date,$migration);
	}
}
