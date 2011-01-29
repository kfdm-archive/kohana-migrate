<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Handles running a single migration
 * This class contains most of the methods required to run a
 * single migration
 */
abstract class Migration_Core {
	protected $db = NULL;
	protected $time = NULL;
	/**
	 * @param integer $time Unix timestamp
	 */
	public function __construct($time) {
		$this->db = Database::instance();
		$this->time = $time;
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
