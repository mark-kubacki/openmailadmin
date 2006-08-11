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

	const	regex_valid_domain	= '[a-z0-9\-\_\.]{2,}\.[a-z]{2,}';

	function __construct(ADOConnection $adodb_handler, array $tablenames, array $cfg, IMAP_Administrator $imap) {
		$this->db		= $adodb_handler;
		$this->tablenames	= $tablenames;
		$this->cfg		= $cfg;
		$this->imap		= $imap;
		$this->validator	= new InputValidatorSuite($this, $cfg);
		$this->ErrorHandler	= ErrorHandler::getInstance();
	}

	/**
	 * Auxiliary function for initialization.
	 */
	private function get_active_controller() {
		static $controller = array();
		if(count($controller) == 0) {
			foreach(array('PasswordAndDataController', 'DomainController',
					'AddressesController', 'RegexpAddressesController',
					'MailboxController', 'IMAPFolderController')
				as $c) {
				$controller[] = new $c($this);
			}
		}
		return $controller;
	}

	/**
	 * Returns an array to be used in templates for generating the main menu.
	 */
	public function get_menu() {
		$arr_navmenu = array();
		foreach($this->get_active_controller() as $c) {
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