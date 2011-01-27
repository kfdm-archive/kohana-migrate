<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Handles running a single migration
 * This class contains most of the methods required to run a
 * single migration
 */
abstract class Migration_Core {
	protected $db = NULL;
	public function __construct() {
		$this->db = Database::instance();
	}
	public function up() {
		throw new Exception('NOT IMPLEMENTED');
	}
	public function down() {
		throw new Exception('NOT IMPLEMENTED');
	}
	public function __toString() {
		return getclass($this);
	}
}
