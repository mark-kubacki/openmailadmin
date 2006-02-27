<?php
/**
 * Provides some primitive functions for error handling each class has to implement.
 * You are free to still use exceptions, where applicable, for critical/blocking errors.
 */
class ErrorHandler
{
	private	$error	= array();		// This will store any errors.
	private	$info	= array();		// Array for informations.
	private static $instance;

	private function __construct() {
	}

	/**
	 * For singleton pattern.
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			$classname = __CLASS__;
			self::$instance = new $classname;
		}
		return self::$instance;
	}

	public function __clone() {
		trigger_error('Only on instance allowed.', E_USER_ERROR);
	}

	/**
	 * Sets $errors to 'no errors occured' and $info to 'no info'.
	 */
	public function status_reset() {
		$this->error	= array();
		$this->info	= array();
	}

	public function errors_occured() {
		return (count($this->error) > 0);
	}

	public function info_occured() {
		return (count($this->info) > 0);
	}

	/**
	 * Concatenates every error message.
	 */
	public function errors_get() {
		$err	= implode(' ', $this->error);
		return $err;
	}

	/**
	 * Concatenates every information message.
	 */
	public function info_get() {
		$err	= implode(' ', $this->info);
		return $err;
	}

	/**
	 * @access	friend
	 */
	public function add_info($text) {
		$this->info[]	= $text;
	}

	/**
	 * @access	friend
	 */
	public function add_error($text) {
		$this->error[]	= $text;
	}

}
?>