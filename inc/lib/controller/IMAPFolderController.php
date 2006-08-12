<?php
class IMAPFolderController
	extends AOMAController
	implements INavigationContributor
{
	public function get_navigation_items() {
		if($this->oma->current_user == $this->oma->authenticated_user) {
			return array('link'		=> 'folders.php',
					'caption'	=> txt('103'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'folders.php'));
		}
		return false;
	}

	public function controller_get_shortname() {
		return 'folder';
	}

}
?>