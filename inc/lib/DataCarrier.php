<?php
/**
 * We'd like to move all the getters and setters to one place
 * in order to store shared code within one place.
 */
abstract class DataCarrier
{
	private		$data	= array();

	protected function __isset($key) {
		return array_key_exists($key, $this->data);
	}

	/**
	 * @throw	Exception	If no value for $key has yet been set.
	 */
	protected function __get($key) {
		if(array_key_exists($key, $this->data)) {
			return $this->data[$key];
		} else {
			throw new Exception('Variable does not exist or has not been set.');
		}
	}

	protected function __set($key, $value) {
		if(is_null($value)) {
			if(array_key_exists($key, $this->data)) {
				unset($this->data[$key]);
			}
		} else {
			$this->data[$key] = $value;
		}
		return true;
	}

	/**
	 * Use this to avoid calling setter several times.
	 */
	protected function become(array $data) {
		$this->data	= $data;
	}

	/**
	 * @return	Array with all keys and their values.
	 */
	protected function confess() {
		return $this->data;
	}

}
?>