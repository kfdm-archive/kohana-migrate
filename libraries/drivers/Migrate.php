<?php defined('SYSPATH') or die('No direct script access.');

abstract class Migrate_Driver {
	protected $db;
	/**
	 * @param Database $db
	 */
	public function __construct($db) {
		$this->db = $db;
	}
	/**
	 * Drop columns from a table
	 * @param string $table Table Name
	 * @param array|string $columns Array of column names or comma separated list
	 * @return  object  Database_Result
	 * @see Database_Core->query()
	 */
	abstract public function drop_columns($table, $columns);
	
	/**
	 * Refresh a table's columns after update
	 * @param string $table
	 */
	public function refresh_table($table) { }
	
	/**
	 * Run a raw SQL Query
	 * @param string $sql
	 * @return  object  Database_Result
	 * @see Database_Core->query()
	 */
	public function query($sql) {
		return $this->db->query($sql);
	}
}