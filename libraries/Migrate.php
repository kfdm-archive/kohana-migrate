<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Primary migration 'controller' class
 * This class contains helper functions and methods related
 * to managing which migrations have run or haven't run and
 * calling individual (or groups) of migrations
 */
class Migrate_Core {
	public static function date() {
		return strftime('%Y%m%d.%H%M%S');
	}
	/**
	 * Hash our date string to use in the class name
	 * @param string $date
	 * @see self::date()
	 */
	public static function hash($date) {
		return strtoupper(substr(md5($date),0,5));
	}
	/**
	 * Generate migration classname
	 * @param string $date Date in the format from self::date()
	 * @param string $tag Tags with whitespace in the form "word word word"
	 * @see self::date()
	 * @return string Classname
	 */
	public static function classname($date,$tag) {
		$hash = self::hash($date);
		$class = str_replace(' ','_',ucwords($tag));
		return "{$class}_{$hash}_Migration";
	}
	/**
	 * Generate migration filename
	 * @param string $date Date in the format from self::date()
	 * @param string $tag Tags with whitespace in the form "word word word"
	 * @see self::date()
	 * @return string Filename
	 */
	public static function filename($date,$tag) {
		$path = Config::item('migrate.path');
		$tag = url::title($tag,'_');
		return  "{$path}/{$date}-{$tag}.php";
	}
}
