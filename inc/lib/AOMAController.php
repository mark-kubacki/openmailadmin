<?php
abstract class AOMAController
{
	protected	$oma;
	protected	$ErrorHandler;

	public function __construct(openmailadmin $oma) {
		$this->oma		= $oma;
		$this->ErrorHandler	= ErrorHandler::getInstance();
	}

	/**
	 * @return 	String 		with shortname of that controller
	 */
	abstract public function controller_get_shortname();

}
?>