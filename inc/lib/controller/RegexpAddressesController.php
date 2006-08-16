<?php
class RegexpAddressesController
	extends AEmailMapperController
	implements INavigationContributor
{
	public function get_navigation_items() {
		if($this->oma->current_user->max_regexp > 0 || $this->oma->authenticated_user->a_super >= 1 || $this->oma->current_user->get_used_regexp()) {
			return array('link'		=> 'regexp.php'.($this->oma->current_user != $this->oma->authenticated_user ? '?cuser='.$this->oma->current_user->ID : ''),
					'caption'	=> txt('33'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'regexp.php'));
		}
		return false;
	}

	public function controller_get_shortname() {
		return 'regexp';
	}

	/**
	 * @return	Array		with instances as values and IDs as keys.
	 */
	private function get_manipulable(User $owner, array $ids) {
		$regexp = RegexpAddress::get_by_owner($owner);
		$list = array();
		foreach($ids as $id) {
			if(isset($regexp[$id])) {
				$list[$id] = $regexp[$id];
			}
		}
		return $list;
	}

	/*
	 * Returns a long list with all regular expressions (the virtual_regexp table).
	 * If $match_against is given, the flag "matching" will be set on matches.
	 */
	public function get_list($match_against = null) {
		$regexp = RegexpAddress::get_by_owner($this->oma->current_user, $_SESSION['limit'], $_SESSION['offset']['regexp']);
		$list = array();
		foreach($regexp as $rexp) {
			$row = array();
			$row['ID'] = $rexp->ID;
			$row['active'] = $rexp->active;
			$row['reg_exp'] = $rexp->reg_exp;
			$row['dest'] = $rexp->get_destinations();
			if(!is_null($match_against)
			   && @preg_match($row['reg_exp'], $match_against)) {
				$row['matching']	= true;
			} else {
				$row['matching']	= false;
				}
			$list[] = $row;
		}
		return $list;
	}

	public function create($regexp, array $destinations) {
		// some dull checks;
		// if someone knows how to find out whether an string is a valid regexp -> write me please
		if($regexp{0} != '/') {
			$this->ErrorHandler->add_error(txt('127'));
			return false;
		}

		if($this->oma->current_user->get_used_regexp() < $this->oma->current_user->max_regexp
		   || $this->oma->authenticated_user->a_super > 0) {
			try {
				$ret = RegexpAddress::create($regexp, $this->oma->current_user, $destinations);
				$this->ErrorHandler->add_info(sprintf(txt('135'), $ret->__toString()));
				return true;
			} catch(DuplicateEntryException $e) {
				$this->ErrorHandler->add_error(txt('133'));
			}
		} else {
			$this->ErrorHandler->add_error(txt('31'));
		}

		return false;
	}

	public function delete($arr_regexp_ids) {
		$res = true;
		$deleted = array();
		foreach($this->get_manipulable($this->oma->current_user, $arr_regexp_ids) as $id => $rexp) {
			$t = RegexpAddress::delete_by_ID($id);
			if($res && !$t
			   && $this->oma->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
			} else {
				$deleted[] = $rexp->__toString();
			}
			$res = $res && $t;
		}
		if(count($deleted) > 0) {
			$this->ErrorHandler->add_info(sprintf(txt(15), '<ul><li><cite>'.implode('</cite></li><li><cite>', $deleted).'</cite></li></ul>'));
		}
		return count($deleted) > 0 && $res;
	}

	public function change_destination($arr_regexp_ids, $arr_destinations) {
		foreach($this->get_manipulable($this->oma->current_user, $arr_regexp_ids) as $rexp) {
			$rexp->set_destinations($arr_destinations);
		}
	}

	public function toggle_active($arr_regexp_ids) {
		foreach($this->get_manipulable($this->oma->current_user, $arr_regexp_ids) as $rexp) {
			$rexp->set_active(!$rexp->active);
		}
	}

}
?>