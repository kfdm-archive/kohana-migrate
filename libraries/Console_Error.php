<?php defined('SYSPATH') or die('No direct script access.');

class Console_Error extends Exception {
	public function __toString() {
		return "{$this->message}\n";
	}
};