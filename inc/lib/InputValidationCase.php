<?php
class InputValidationCase
{
	protected	$checks		= array();
	protected	$field_caption;
	protected	$field_value;
	protected	$default_value;

	public function __construct(&$field_value, $field_caption, $default_value = null) {
		$this->field_caption	= $field_caption;
		$this->field_value	= &$field_value;
		$this->default_value	= $default_value;
	}

	public function add_check(InputValidationPair $check) {
		$this->checks[]		= $check;
	}

	/**
	 * @return		Boolean true if the field's value passes all checks and false if it had to be assigned the default value.
	 * @throw		UnexpectedValueException
	 */
	public function valid() {
		foreach($this->checks as $check) {
			if(!$check->passes($this->field_value)) {
				if(is_null($this->default_value)) {
//					throw new UnexpectedValueException($check->error_message);
				} else {
					$this->field_value	= $this->default_value;
				}
				return false;
			}
		}
		return true;
	}

}
?>