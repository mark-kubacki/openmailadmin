<?php
class RegexpAddressesController
	extends AEmailMapperController
	implements INavigationContributor
{
	public function get_navigation_items() {
		if($this->oma->current_user->max_regexp > 0 || $this->oma->authenticated_user->a_super >= 1 || $this->oma->current_user->get_used_regexp()) {
			return array('link'		=> 'regexp.php'.($this->oma->current_user != $this->oma->authenticated_user ? '?cuser='.$this->oma->current_user->mbox : ''),
					'caption'	=> txt('33'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'regexp.php'));
		}
		return false;
	}

	public function controller_get_shortname() {
		return 'regexp';
	}

	/*
	 * Returns a long list with all regular expressions (the virtual_regexp table).
	 * If $match_against is given, the flag "matching" will be set on matches.
	 */
	public function get_list($match_against = null) {
		$regexp = array();

		$result = $this->oma->db->SelectLimit('SELECT * FROM '.$this->oma->tablenames['virtual_regexp']
				.' WHERE owner='.$this->oma->db->qstr($this->oma->current_user->mbox).$_SESSION['filter']['str']['regexp']
				.' ORDER BY dest',
				$_SESSION['limit'], $_SESSION['offset']['regexp']);
		if(!$result === false) {
			while(!$result->EOF) {
				$row	= $result->fields;
				// if ordered, check whether expression matches probe address
				if(!is_null($match_against)
				   && @preg_match($row['reg_exp'], $match_against)) {
					$row['matching']	= true;
				} else {
					$row['matching']	= false;
				}
				// explode all destinations (as there may be many)
				$dest = array();
				foreach(explode(',', $row['dest']) as $value) {
					$value = trim($value);
					// replace the current user's name with "mailbox"
					if($value == $this->oma->current_user->mbox)
					$dest[] = txt('5');
					else
					$dest[] = $value;
				}
				sort($dest);
				$row['dest'] = $dest;
				// add the current entry to our list of aliases
				$regexp[] = $row;
				$result->MoveNext();
			}
		}
		return $regexp;
	}
	/*
	 * Creates a new regexp-address.
	 */
	public function create($regexp, $arr_destinations) {
		// some dull checks;
		// if someone knows how to find out whether an string is a valid regexp -> write me please
		if($regexp == '' || $regexp{0} != '/') {
			$this->ErrorHandler->add_error(txt('127'));
			return false;
		}

		if($this->oma->current_user->get_used_regexp() < $this->oma->current_user->max_regexp
		   || $this->oma->authenticated_user->a_super > 0) {
			$this->oma->db->Execute('INSERT INTO '.$this->oma->tablenames['virtual_regexp'].' (reg_exp, dest, owner) VALUES (?, ?, ?)',
				array($regexp, implode(',', $arr_destinations), $this->oma->current_user->mbox));
			if($this->oma->db->Affected_Rows() < 1) {
				if($this->oma->db->ErrorNo() != 0) {
					$this->ErrorHandler->add_error(txt('133'));
				}
			} else {
				return true;
			}
		} else {
			$this->ErrorHandler->add_error(txt('31'));
		}

		return false;
	}
	/*
	 * Deletes the given regular expressions if they belong to the current user.
	 */
	public function delete($arr_regexp_ids) {
		$this->oma->db->Execute('DELETE FROM '.$this->oma->tablenames['virtual_regexp']
				.' WHERE owner='.$this->oma->db->qstr($this->oma->current_user->mbox)
				.' AND '.db_find_in_set($this->oma->db, 'ID', $arr_regexp_ids));
		if($this->oma->db->Affected_Rows() < 1) {
			if($this->oma->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
			}
		} else {
			$this->ErrorHandler->add_info(txt('32'));
			return true;
		}

		return false;
	}
	/**
	 * @see		AddressController::change_destination
	 */
	public function change_destination($arr_regexp_ids, $arr_destinations) {
		$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual_regexp'].' SET dest='.$this->oma->db->qstr(implode(',', $arr_destinations)).', neu = 1'
				.' WHERE owner='.$this->oma->db->qstr($this->oma->current_user->mbox)
				.' AND '.db_find_in_set($this->oma->db, 'ID', $arr_regexp_ids));
		if($this->oma->db->Affected_Rows() < 1) {
			if($this->oma->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
			}
		} else {
			return true;
		}

		return false;
	}
	/**
	 * @see		AddressController::toggle_active
	 */
	public function toggle_active($arr_regexp_ids) {
		$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual_regexp'].' SET active = NOT active, neu = 1'
				.' WHERE owner='.$this->oma->db->qstr($this->oma->current_user->mbox)
				.' AND '.db_find_in_set($this->oma->db, 'ID', $arr_regexp_ids));
		if($this->oma->db->Affected_Rows() < 1) {
			if($this->oma->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
			}
		} else {
			return true;
		}

		return false;
	}

}
?>