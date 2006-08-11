<?php
class openmailadmin
{
	public	$current_user;		// What user do we edit/display currently?
	public	$authenticated_user;	// What user did log in?

	private	$db;
	private $validator;
	protected	$ErrorHandler;

	private	$tablenames;
	private	$cfg;
	public	$imap;

	const	regex_valid_email	= '[a-z0-9]{1,}[a-z0-9\.\-\_\+]*@[a-z0-9\.\-\_]{2,}\.[a-z]{2,}';
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

	/*
	 * Accepts a string containing possible destination for an email-address,
	 * selects valid destinations and returns them.
	 */
	public function get_valid_destinations($possible) {
		// Define what addresses we will accept.
		$pattern  = openmailadmin::regex_valid_email;
		$pattern .= '|'.$this->current_user->mbox.'|'.txt('5').'|'.strtolower(txt('5'));
		if($this->cfg['allow_mbox_as_target']) {
			$mailboxes = &$this->get_mailbox_names();
			if(count($mailboxes) > 0) {
				$pattern .= '|'.implode('|', $mailboxes);
			}
		} else if($this->cfg['allow_wcyr_as_target']) {
			$pattern .= '|[a-z]{2,}[0-9]{4}';
		}

		// Get valid destinations.
		if(preg_match_all('/'.$pattern.'/iu', $possible, $matched)) {
			if(is_array($matched[0])) {
				// Replace every occurence of 'mailbox' with the correct name.
				array_walk($matched[0],
					create_function('&$item,$index',
							'if(strtolower($item) == \''.strtolower(txt('5')).'\') $item = \''.$this->current_user->mbox.'\';'
							));
				return $matched[0];
			}
		}
		return array();
	}

}
?>