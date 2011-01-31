<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Handles running a single migration
 * This class contains most of the methods required to run a
 * single migration
 */
abstract class Migration_Core {
	protected $driver;
	protected $time;
	/**
	 * @param integer $time Unix timestamp
	 */
	public function __construct($time) {
		$this->driver = self::driver();
		$this->time = $time;
	}
	/**
	 * Load Migration Driver
	 */
	protected static function driver() {
		$driver = Config::item('migrate.driver');
		$driver = "Migrate_{$driver}_Driver";
		return new $driver(Database::instance());
	}
	/**
	 * Catch 'other' method calls
	 * Any additional method calls that we get, we attempt to send to the 
	 * migration driver to deal with.  This should handle the various helper
	 * method calls that we might end up using.
	 * @param string $method
	 * @param array $args
	 */
	public function __call($method,$args) {
		return call_user_func_array(array($this->driver,$method),$args);
	}
	public function up() {
		throw new Migrate_Error('migrate.not_implemented');
	}
	public function down() {
		throw new Migrate_Error('migrate.not_implemented');
	}
	public function __toString() {
		return getclass($this);
	}
}

class Irreversible_Error extends Migrate_Error {
	public function __construct() {
		parent::__construct('migrate.irreversible');
	}
}
