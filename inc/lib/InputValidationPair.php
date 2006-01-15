<?php
/**
 * Multiples of this pair form a test case.
 */
class InputValidationPair
{
	/** Will be evaluated and has to return boolean value. */
	public	$validation_command;
	/** This one is displayed in case the command evaluated to false. */
	public	$error_message;

	const	IsNumeric	= 'is_numeric(~)';
	const	IsInteger	= 'is_numeric(~) && settype(~, \'int\')';
	const	Unsigned	= 'is_numeric(~) && ~ >= 0';
	const	Between		= '~ >= %d && ~ <= %d';

	public function __construct($command = 'true', $error_msg = '') {
		$this->validation_command	= $command;
		$this->error_message		= $error_msg;
	}

	public function passes($field_value) {
		return eval('return '.str_replace('~', '$field_value', $this->validation_command).';');
	}

}
?>