<?php
class MailboxController
	extends AOMAController
	implements INavigationContributor
{
	public function get_navigation_items() {
		$oma = $this->oma;
		if($this->oma->authenticated_user->a_admin_user >= 1 || $this->oma->user_get_number_mailboxes($this->oma->current_user->mbox) > 0) {
			return	array('link'		=> 'mailboxes.php'.($this->oma->current_user->mbox != $this->oma->authenticated_user->mbox ? '?cuser='.$this->oma->current_user->mbox : ''),
					'caption'	=> txt('79'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'mailboxes.php'));
		}
		return false;
	}

	public function controller_get_shortname() {
		return 'mailbox';
	}

/* ******************************* mailboxes ******************************** */
	/*
	 * Returns list with mailboxes the current user can see.
	 * Typically all his patenkinder will show up.
	 * If the current user is at his pages and is superuser, he will see all mailboxes.
	 */
	public function get_mailboxes() {
		$mailboxes = array();

		if($this->current_user->mbox == $this->authenticated_user->mbox
		   && $this->authenticated_user->a_super >= 1) {
			$where_clause = ' WHERE TRUE';
		} else {
			$where_clause = ' WHERE pate='.$this->db->qstr($this->current_user->mbox);
		}

		$result = $this->db->SelectLimit('SELECT mbox, person, canonical, pate, max_alias, max_regexp, usr.active, last_login AS lastlogin, a_super, a_admin_domains, a_admin_user, '
						.'COUNT(DISTINCT virt.alias) AS num_alias, '
						.'COUNT(DISTINCT rexp.ID) AS num_regexp '
					.'FROM '.$this->tablenames['user'].' usr '
						.'LEFT OUTER JOIN '.$this->tablenames['virtual'].' virt ON (mbox=virt.owner) '
						.'LEFT OUTER JOIN '.$this->tablenames['virtual_regexp'].' rexp ON (mbox=rexp.owner) '
					.$where_clause.$_SESSION['filter']['str']['mbox']
					.' GROUP BY mbox, person, canonical, pate,  max_alias, max_regexp, usr.active, last_login, a_super, a_admin_domains, a_admin_user '
					.'ORDER BY pate, mbox',
					$_SESSION['limit'], $_SESSION['offset']['mbox']);

		if(!$result === false) {
			while(!$result->EOF) {
				if(!in_array($result->fields['mbox'], $this->cfg['user_ignore']))
					$mailboxes[] = $result->fields;
				$result->MoveNext();
			}
		}
		$this->current_user->n_mbox = $this->user_get_number_mailboxes($this->current_user->mbox);

		return $mailboxes;
	}

	/*
	 * This will return a list with $whose's patenkinder for further use in selections.
	 */
	public function get_selectable_paten($whose) {
		if(!isset($_SESSION['paten'][$whose])) {
			$selectable_paten = array();
			if($this->authenticated_user->a_super >= 1) {
				$result = $this->db->Execute('SELECT mbox FROM '.$this->tablenames['user']);
			} else {
				$result = $this->db->Execute('SELECT mbox FROM '.$this->tablenames['user'].' WHERE pate='.$this->db->qstr($whose));
			}
			while(!$result->EOF) {
				if(!in_array($result->fields['mbox'], $this->cfg['user_ignore']))
					$selectable_paten[] = $result->fields['mbox'];
				$result->MoveNext();
			}
			$selectable_paten[] = $whose;
			$selectable_paten[] = $this->authenticated_user->mbox;

			// Array_unique() will do alphabetical sorting.
			$_SESSION['paten'][$whose] = array_unique($selectable_paten);
		}

		return $_SESSION['paten'][$whose];
	}

	/*
	 * Eliminates every mailbox name from $desired_mboxes which is no descendant
	 * of $who. If the authenticated user is superuser, no filtering is done
	 * except elimination imposed by $this->cfg['user_ignore'].
	 */
	private function mailbox_filter_manipulable($who, $desired_mboxes) {
		$allowed = array();

		// Does the authenticated user have the right to do that?
		if($this->authenticated_user->a_super >= 1) {
			$allowed = array_diff($desired_mboxes, $this->cfg['user_ignore']);
		} else {
			foreach($desired_mboxes as $mbox) {
				if(!in_array($mbox, $this->cfg['user_ignore']) && $this->user_is_descendant($mbox, $who)) {
					$allowed[] = $mbox;
				}
			}
		}

		return $allowed;
	}

	/*
	 * $props is typically $_POST, as this function selects all the necessary fields
	 * itself.
	 */
	public function mailbox_create($mboxname, $props) {
		$rollback	= array();

		// Check inputs for sanity and consistency.
		if(!$this->authenticated_user->a_admin_user > 0) {
			$this->ErrorHandler->add_error(txt('16'));
			return false;
		}
		if(in_array($mboxname, $this->cfg['user_ignore'])) {
			$this->ErrorHandler->add_error(sprintf(txt('130'), txt('83')));
			return false;
		}
		if(!$this->validator->validate($props, array('mbox','person','pate','canonical','domains','max_alias','max_regexp','a_admin_domains','a_admin_user','a_super','quota'))) {
			return false;
		}

		// check contingents (only if non-superuser)
		if($this->authenticated_user->a_super == 0) {
			// As the current user's contingents will be decreased we have to use his values.
			if($props['max_alias'] > ($this->current_user->max_alias - $this->user_get_used_alias($this->current_user->mbox))
			   || $props['max_regexp'] > ($this->current_user->max_regexp - $this->user_get_used_regexp($this->current_user->mbox))) {
				$this->ErrorHandler->add_error(txt('66'));
				return false;
			}
			$quota	= $this->imap->get_users_quota($this->current_user->mbox);
			if($quota->is_set && $props['quota']*1024 > $quota->free) {
				$this->ErrorHandler->add_error(txt('65'));
				return false;
			}
		}

		// first create the default-from (canonical) (must not already exist!)
		if($this->cfg['create_canonical']) {
			$tmp = explode('@', $props['canonical']);
			if(!(is_array($tmp)
			     && $this->address_create($tmp[0], $tmp[1], array($mboxname))) ) {
				$this->ErrorHandler->add_error(txt('64'));
				return false;
			}
			$rollback[] = '$this->db->Execute(\'DELETE FROM '.$this->tablenames['virtual'].' WHERE owner='.addslashes($this->db->qstr($mboxname)).'\')';
		}

		// on success write the new user to database
		$this->db->Execute('INSERT INTO '.$this->tablenames['user'].' (mbox, person, pate, canonical, domains, max_alias, max_regexp, created, a_admin_domains, a_admin_user, a_super)'
				.' VALUES (?,?,?,?,?,?,?,?,?,?,?)',
				array($props['mbox'], $props['person'], $props['pate'], $props['canonical'], $props['domains'], $props['max_alias'], $props['max_regexp'], time(), $props['a_admin_domains'], $props['a_admin_user'], $props['a_super'])
				);
		if($this->db->Affected_Rows() < 1) {
			$this->ErrorHandler->add_error(txt('92'));
			// Rollback
			$this->rollback($rollback);
			return false;
		}
		$rollback[] = '$this->db->Execute(\'DELETE FROM '.$this->tablenames['user'].' WHERE mbox='.addslashes($this->db->qstr($mboxname)).'\')';

		$tmpu = new User($props['mbox']);
		$pw = $tmpu->password->set_random($this->cfg['passwd']['min_length'], $this->cfg['passwd']['max_length']);

		// Decrease current users's contingents...
		if($this->authenticated_user->a_super == 0) {
			$rollback[] = '$this->db->Execute(\'UPDATE '.$this->tablenames['user'].' SET max_alias='.$this->current_user->max_alias.', max_regexp='.$this->current_user->max_regexp.' WHERE mbox='.addslashes($this->db->qstr($this->current_user->mbox)).'\')';
			$this->db->Execute('UPDATE '.$this->tablenames['user']
				.' SET max_alias='.($this->current_user->max_alias-intval($props['max_alias'])).', max_regexp='.($this->current_user->max_regexp-intval($props['max_regexp']))
				.' WHERE mbox='.$this->db->qstr($this->current_user->mbox));
		}
		// ... and then create the user on the server.
		$result = $this->imap->createmb($this->imap->format_user($mboxname));
		if(!$result) {
			$this->ErrorHandler->add_error($this->imap->error_msg);
			// Rollback
			$this->rollback($rollback);
			return false;
		} else {
			if(isset($this->cfg['folders']['create_default']) && is_array($this->cfg['folders']['create_default'])) {
				foreach($this->cfg['folders']['create_default'] as $new_folder) {
					$this->imap->createmb($this->imap->format_user($mboxname, $new_folder));
				}
			}
		}
		$rollback[] = '$this->imap->deletemb($this->imap->format_user(\''.$mboxname.'\'))';

		// Decrease the creator's quota...
		$cur_usr_quota	= $this->imap->getquota($this->imap->format_user($this->current_user->mbox));
		if($this->authenticated_user->a_super == 0 && $cur_usr_quota->is_set) {
			$result = $this->imap->setquota($this->imap->format_user($this->current_user->mbox), $cur_usr_quota->max - $props['quota']*1024);
			if(!$result) {
				$this->ErrorHandler->add_error($this->imap->error_msg);
				// Rollback
				$this->rollback($rollback);
				return false;
			}
			$rollback[] = '$this->imap->setquota($this->imap->format_user($this->current_user->mbox), '.$cur_usr_quota->max .'))';
			$this->ErrorHandler->add_info(sprintf(txt('69'), floor(($cur_usr_quota->max - $props['quota']*1024)/1024) ));
		} else {
			$this->ErrorHandler->add_info(txt('71'));
		}

		// ... and set the new user's quota.
		if(is_numeric($props['quota'])) {
			$result = $this->imap->setquota($this->imap->format_user($mboxname), $props['quota']*1024);
			if(!$result) {
				$this->ErrorHandler->add_error($this->imap->error_msg);
				// Rollback
				$this->rollback($rollback);
				return false;
			}
		}
		$this->ErrorHandler->add_info(sprintf(txt('72'), $mboxname, $props['person'], $pw));
		if(isset($_SESSION['paten'][$props['pate']])) {
			$_SESSION['paten'][$props['pate']][] = $mboxname;
		}

		return true;
	}

	/*
	 * $props can be $_POST, as every sutable field from $change is used.
	 */
	public function mailbox_change($mboxnames, $change, $props) {
		// Ensure sanity of inputs and check requirements.
		if(!$this->authenticated_user->a_admin_user > 0) {
			$this->ErrorHandler->add_error(txt('16'));
			return false;
		}
		if(!$this->validator->validate($props, $change)) {
			return false;
		}
		$mboxnames = $this->mailbox_filter_manipulable($this->authenticated_user->mbox, $mboxnames);
		if(!count($mboxnames) > 0) {
			return false;
		}

		// Create an array holding every property we have to change.
		$to_change	= array();
		foreach(array('person', 'canonical', 'pate', 'domains', 'a_admin_domains', 'a_admin_user', 'a_super')
			as $property) {
			if(in_array($property, $change)) {
				if(is_numeric($props[$property])) {
					$to_change[]	= $property.' = '.$props[$property];
				} else {
					$to_change[]	= $property.'='.$this->db->qstr($props[$property]);
				}
			}
		}

		// Execute the change operation regarding properties in DB.
		if(count($to_change) > 0) {
			$this->db->Execute('UPDATE '.$this->tablenames['user']
				.' SET '.implode(',', $to_change)
				.' WHERE '.db_find_in_set($this->db, 'mbox', $mboxnames));
		}

		// Manipulate contingents (except quota).
		foreach(array('max_alias', 'max_regexp') as $what) {
			if(in_array($what, $change)) {
				$seek_table = $what == 'max_alias'
						? $this->tablenames['virtual']
						: $this->tablenames['virtual_regexp'];
				$to_be_processed = $mboxnames;
				// Select users which use more aliases than allowed in future.
				$result = $this->db->Execute('SELECT COUNT(*) AS consum, owner, person'
						.' FROM '.$seek_table.','.$this->tablenames['user']
						.' WHERE '.db_find_in_set($this->db, 'owner', $mboxnames).' AND owner=mbox'
						.' GROUP BY owner'
						.' HAVING consum > '.$props[$what]);
				if(!$result === false) {
					// We have to skip them.
					$have_skipped = array();
					while(!$result->EOF) {
						$row	= $result->fields;
						$have_skipped[] = $row['owner'];
						if($this->cfg['mboxview_pers']) {
							$tmp[] = '<a href="'.mkSelfRef(array('cuser' => $row['owner'])).'" title="'.$row['owner'].'">'.$row['person'].' ('.$row['consum'].')</a>';
						} else {
							$tmp[] = '<a href="'.mkSelfRef(array('cuser' => $row['owner'])).'" title="'.$row['person'].'">'.$row['owner'].' ('.$row['consum'].')</a>';
						}
						$result->MoveNext();
					}
					if(count($have_skipped) > 0) {
						$this->ErrorHandler->add_error(sprintf(txt('131'),
									$props[$what], $what == 'max_alias' ? txt('88') : txt('89'),
									implode(', ', $tmp)));
						$to_be_processed = array_diff($to_be_processed, $have_skipped);
					}
				}
				if(count($to_be_processed) > 0) {
					// We don't need further checks if a superuser is logged in.
					if($this->authenticated_user->a_super > 0) {
					$this->db->Execute('UPDATE '.$this->tablenames['user']
						.' SET '.$what.'='.$props[$what]
						.' WHERE '.db_find_in_set($this->db, 'mbox', $to_be_processed));
					} else {
						// Now, calculate whether the current user has enough free contingents.
						$has_to_be_free = $this->db->GetOne('SELECT SUM('.$props[$what].'-'.$what.')'
								.' FROM '.$this->tablenames['user']
								.' WHERE '.db_find_in_set($this->db, 'mbox', $to_be_processed));
						if($has_to_be_free <= $this->user_get_used_alias($this->current_user->mbox)) {
							// If so, set new contingents on the users...
							$this->db->Execute('UPDATE '.$this->tablenames['user']
							.' SET '.$what.'='.$props[$what]
							.' WHERE '.db_find_in_set($this->db, 'mbox', $to_be_processed));
							// ... and add/remove the difference from the current user.
							$this->db->Execute('UPDATE '.$this->tablenames['user']
							.' SET '.$what.'='.$what.'-'.$has_to_be_free
							.' WHERE mbox='.$this->db->qstr($this->current_user->mbox));
						} else {
							// Else, we have to show an error message.
							$this->ErrorHandler->add_error(txt('66'));
						}
					}
				}
			}
		}

		// Change Quota.
		if(in_array('quota', $change)) {
			$add_quota = 0;
			if($this->authenticated_user->a_super == 0) {
				foreach($mboxnames as $user) {
					if($user != '') {
						$quota	= $this->imap->get_users_quota($user);
						if($quota->is_set)
							$add_quota += intval($props['quota'])*1024 - $quota->max;
					}
				}
				$quota	= $this->imap->get_users_quota($this->current_user->mbox);
				if($add_quota != 0 && $quota->is_set) {
					$this->imap->setquota($this->imap->format_user($this->current_user->mbox), $quota->max - $add_quota);
					$this->ErrorHandler->add_info(sprintf(txt('78'), floor(($quota->max - $add_quota)/1024) ));
				}
			}
			reset($mboxnames);
			foreach($mboxnames as $user) {
				if($user != '') {
					$result = $this->imap->setquota($this->imap->format_user($user), intval($props['quota'])*1024);
					if(!$result) {
						$this->ErrorHandler->add_error($this->imap->error_msg);
					}
				}
			}
		}

		// Renaming of (single) user.
		if(in_array('mbox', $change)) {
			if($this->imap->renamemb($this->imap->format_user($mboxnames['0']), $this->imap->format_user($props['mbox']))) {
				$this->db->Execute('UPDATE '.$this->tablenames['user'].' SET mbox='.$this->db->qstr($props['mbox']).' WHERE mbox='.$this->db->qstr($mboxnames['0']));
				$this->db->Execute('UPDATE '.$this->tablenames['domains'].' SET owner='.$this->db->qstr($props['mbox']).' WHERE owner='.$this->db->qstr($mboxnames['0']));
				$this->db->Execute('UPDATE '.$this->tablenames['domains'].' SET a_admin = REPLACE(a_admin, '.$this->db->qstr($mboxnames['0']).', '.$this->db->qstr($props['mbox']).') WHERE a_admin LIKE '.$this->db->qstr('%'.$mboxnames['0'].'%'));
				$this->db->Execute('UPDATE '.$this->tablenames['virtual'].' SET dest=REPLACE(dest, '.$this->db->qstr($mboxnames['0']).', '.$this->db->qstr($props['mbox']).'), neu = 1 WHERE dest REGEXP '.$this->db->qstr($mboxnames['0'].'[^@]{1,}').' OR dest LIKE '.$this->db->qstr('%'.$mboxnames['0']));
				$this->db->Execute('UPDATE '.$this->tablenames['virtual'].' SET owner='.$this->db->qstr($props['mbox']).' WHERE owner='.$this->db->qstr($mboxnames['0']));
				$this->db->Execute('UPDATE '.$this->tablenames['virtual_regexp'].' SET dest=REPLACE(dest, '.$this->db->qstr($mboxnames['0']).', '.$this->db->qstr($props['mbox']).'), neu = 1 WHERE dest REGEXP '.$this->db->qstr($mboxnames['0'].'[^@]{1,}').' OR dest LIKE '.$this->db->qstr('%'.$mboxnames['0']));
				$this->db->Execute('UPDATE '.$this->tablenames['virtual_regexp'].' SET owner='.$this->db->qstr($props['mbox']).' WHERE owner='.$this->db->qstr($mboxnames['0']));
			} else {
				$this->ErrorHandler->add_error($this->imap->error_msg.'<br />'.txt('94'));
			}
		}

		if(isset($_SESSION['paten']) && in_array(array('mbox', 'pate'), $change)) {
			unset($_SESSION['paten']);	// again: inefficient, but maybe we come up with something more elegant
		}

		return true;
	}

	/*
	 * If ressources are freed, the current user will get them.
	 */
	public function mailbox_delete($mboxnames) {
		$mboxnames = $this->mailbox_filter_manipulable($this->authenticated_user->mbox, $mboxnames);
		if(count($mboxnames) == 0) {
			return false;
		}

		// Delete the given mailboxes from server.
		$add_quota = 0;			// how many space was actually freed?
		$toadd = 0;
		$processed = array();		// which users did we delete successfully?
		foreach($mboxnames as $user) {
			if($user != '') {
				// We have to sum up all the space which gets freed in case we later want increase the deleter's quota.
				$quota	= $this->imap->get_users_quota($user);
				if($this->authenticated_user->a_super == 0
				   && $quota->is_set) {
					$toadd = $quota->max;
				}
				$result = $this->imap->deletemb($this->imap->format_user($user));
				if(!$result) {		// failure
					$this->ErrorHandler->add_error($this->imap->error_msg);
				} else {		// success
					$add_quota += $toadd;
					$processed[] = $user;
				}
			}
		}

		// We need not proceed in case no users were deleted.
		if(count($processed) == 0) {
			return false;
		}

		// Now we have to increase the current user's quota.
		$quota	= $this->imap->get_users_quota($this->current_user->mbox);
		if($this->authenticated_user->a_super == 0
		   && $add_quota > 0
		   && $quota->is_set) {
			$this->imap->setquota($this->imap->format_user($this->current_user->mbox), $quota->max + $add_quota);
			$this->ErrorHandler->add_info(sprintf(txt('76'), floor(($quota->max + $add_quota)/1024) ));
		}

		// Calculate how many contingents get freed if we delete the users.
		if($this->authenticated_user->a_super == 0) {
			$result = $this->db->GetRow('SELECT SUM(max_alias) AS nr_alias, SUM(max_regexp) AS nr_regexp'
					.' FROM '.$this->tablenames['user']
					.' WHERE '.db_find_in_set($this->db, 'mbox', $processed));
			if(!$result === false) {
				$will_be_free = $result;
			}
		}

		// virtual
		$this->db->Execute('DELETE FROM '.$this->tablenames['virtual'].' WHERE '.db_find_in_set($this->db, 'owner', $processed));
		$this->db->Execute('UPDATE '.$this->tablenames['virtual'].' SET active=0, neu=1 WHERE '.db_find_in_set($this->db, 'dest', $processed));
		// virtual.regexp
		$this->db->Execute('DELETE FROM '.$this->tablenames['virtual_regexp'].' WHERE '.db_find_in_set($this->db, 'owner', $processed));
		$this->db->Execute('UPDATE '.$this->tablenames['virtual_regexp'].' SET active=0, neu=1 WHERE '.db_find_in_set($this->db, 'dest', $processed));
		// domain (if the one to be deleted owns domains, the deletor will inherit them)
		$this->db->Execute('UPDATE '.$this->tablenames['domains'].' SET owner='.$this->db->qstr($this->current_user->mbox).' WHERE '.db_find_in_set($this->db, 'owner', $processed));
		// user
		$this->db->Execute('DELETE FROM '.$this->tablenames['user'].' WHERE '.db_find_in_set($this->db, 'mbox', $processed));
		if($this->authenticated_user->a_super == 0 && isset($will_be_free['nr_alias'])) {
			$this->db->Execute('UPDATE '.$this->tablenames['user']
			.' SET max_alias='.($this->current_user->max_alias+$will_be_free['nr_alias']).', max_regexp='.($this->current_user->max_regexp+$will_be_free['nr_regexp'])
			.' WHERE mbox='.$this->db->qstr($this->current_user->mbox));
		}
		// patenkinder (will be inherited by the one deleting)
		$this->db->Execute('UPDATE '.$this->tablenames['user']
			.' SET pate='.$this->db->qstr($this->current_user->mbox)
			.' WHERE '.db_find_in_set($this->db, 'pate', $processed));

		$this->ErrorHandler->add_info(sprintf(txt('75'), implode(',', $processed)));
		if(isset($_SESSION['paten'])) unset($_SESSION['paten']); // inefficient, but maybe we come up with something more elegant

		return true;
	}

	/*
	 * active <-> inactive
	 */
	public function mailbox_toggle_active($mboxnames) {
		$tobechanged = $this->mailbox_filter_manipulable($this->current_user->mbox, $mboxnames);
		if(count($tobechanged) > 0) {
			$this->db->Execute('UPDATE '.$this->tablenames['user']
					.' SET active = NOT active'
					.' WHERE '.db_find_in_set($this->db, 'mbox', $tobechanged));
			if($this->db->Affected_Rows() < 1) {
				if($this->db->ErrorNo() != 0) {
					$this->ErrorHandler->add_error($this->db->ErrorMsg());
				}
			} else {
				return true;
			}
		}
		return false;
	}

}
?>