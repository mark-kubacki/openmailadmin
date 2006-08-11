<?php
class IMAPFolderController
	extends AOMAController
	implements INavigationContributor
{
	public function get_navigation_items() {
		$oma = $this->oma;
		if($this->oma->current_user->mbox == $this->oma->authenticated_user->mbox) {
			return array('link'		=> 'folders.php'.($this->oma->current_user->mbox != $this->oma->authenticated_user->mbox ? '?cuser='.$this->oma->current_user->mbox : ''),
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