<?php
abstract class AEmailMapperController
	extends AOMAController
{
	const	regex_valid_email	= '[a-z0-9]{1,}[a-z0-9\.\-\_\+]*@[a-z0-9\.\-\_]{2,}\.[a-z]{2,}';

	/**
	 * Accepts a string containing possible destination for an email-address,
	 * selects valid destinations and returns them.
	 */
	public function get_valid_destinations($possible) {
		// Define what addresses we will accept.
		$pattern  = self::regex_valid_email;
		$pattern .= '|'.$this->oma->current_user->mbox.'|'.txt('5').'|'.strtolower(txt('5'));
		if($this->oma->cfg['allow_mbox_as_target']) {
			$mailboxes = $this->oma->mailbox->get_all_names();
			if(count($mailboxes) > 0) {
				$pattern .= '|'.implode('|', $mailboxes);
			}
		} else if($this->oma->cfg['allow_wcyr_as_target']) {
			$pattern .= '|[a-z]{2,}[0-9]{4}';
		}

		// Get valid destinations.
		if(preg_match_all('/'.$pattern.'/iu', $possible, $matched)) {
			if(is_array($matched[0])) {
				// Replace every occurence of 'mailbox' with the correct name.
				array_walk($matched[0],
					create_function('&$item,$index',
							'if(strtolower($item) == \''.strtolower(txt('5')).'\') $item = \''.$this->oma->current_user->mbox.'\';'
							));
				return $matched[0];
			}
		}
		return array();
	}
}
?>