<?php defined('SYSPATH') or die('No direct script access.');

class Migrate_Mysql_Driver extends Migrate_Driver {
	public function drop_columns($table, $columns) {
		if(is_string($columns)) $columns = split(',',$columns);
		foreach($columns as $k=>$v)
			$columns[$k] = "DROP `{$v}`";
		$sql = "ALTER TABLE `{$table}` ".implode(', ',$columns);
		return $this->db->query($sql);
	}
}