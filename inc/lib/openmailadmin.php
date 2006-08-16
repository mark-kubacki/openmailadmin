<?php
class openmailadmin
{
	public	$current_user;		// What user do we edit/display currently?
	public	$authenticated_user;	// What user did log in?

	public	$db;
	public	$validator;
	protected	$ErrorHandler;

	public	$tablenames;
	public	$cfg;
	public	$imap;

	private	$controller;

	const	regex_valid_domain	= '[a-z0-9\-\_\.]{2,}\.[a-z]{2,}';

	function __construct(ADOConnection $adodb_handler, array $tablenames, array $cfg, IMAP_Administrator $imap) {
		$this->db		= $adodb_handler;
		$this->tablenames	= $tablenames;
		$this->cfg		= $cfg;
		$this->imap		= $imap;
		$this->validator	= new InputValidatorSuite($this, $cfg);
		$this->ErrorHandler	= ErrorHandler::getInstance();
		$this->controller	= $this->get_active_controller();
	}

	/**
	 * Abstract getter for the various active controller.
	 *
	 * @throw	RuntimeException	if no controller has been registered with given shortname.
	 */
	public function __get($shortname) {
		if(array_key_exists($shortname, $this->controller)) {
			return $this->controller[$shortname];
		} else {
			throw new RuntimeException('Unknown controller with shortname "'.$shortname.'".');
		}
	}

	/**
	 * Auxiliary function for initialization.
	 */
	private function get_active_controller() {
		static $controller = array();
		if(count($controller) == 0) {
			foreach($this->cfg['controller'] as $c) {
				$i = new $c($this);
				$controller[$i->controller_get_shortname()] = $i;
			}
		}
		return $controller;
	}

	/**
	 * Returns an array to be used in templates for generating the main menu.
	 */
	public function get_menu() {
		$arr_navmenu = array();
		foreach($this->controller as $c) {
			if($c instanceof INavigationContributor) {
				$e = $c->get_navigation_items();
				if(is_array($e)) {
					$arr_navmenu[] = $e;
				}
			}
		}
		return $arr_navmenu;
	}

}
?>