<?php
/*
 * This class collects all methods of Openmailadmin, except for the view
 * and data storage. It is not finished, yet!
 *
 * This file is soo long!
 * - Forgive me, but PHP does not support "require_once" inside of class definitions.
 */

class openmailadmin {
    var $current_user;		// What user do we edit/display currently?
    var $authenticated_user;	// What user did log in?

    var $error;			// This will store any errors. (Array)
    var $info;			// Array for informations.

    var $regex_valid_email	= '[A-Za-z0-9][A-Za-z0-9\.\-\_\+]{1,}@[A-Za-z0-9\.\-\_]{2,}\.[A-Za-z]{2,}';
    var $regex_valid_domain	= '[a-z0-9\-\_\.]{2,}\.[a-z]{2,}';

    function openmailadmin() {
	$this->status_reset();
    }

    /*
     * Sets $errors to 'no errors occured' and $info to 'no info'.
     */
    function status_reset() {
	$this->error		= array();
	$this->info		= array();
    }
    /*
     * If any errors occured, returns true.
     */
    function errors_occured() {
	return (count($this->error) > 0);
    }
    function info_occured() {
	return (count($this->info) > 0);
    }
    /*
     * Concatenates every error message.
     */
    function errors_get() {
	$err	= implode(' ',$this->error);
	return $err;
    }
    function info_get() {
	$err	= implode(' ',$this->info);
	return $err;
    }

    /*
     * Returns a long list with every active mailbox.
     */
    function get_mailbox_names() {
	global $cfg;
	$tmp	= array();

	$result = mysql_query('SELECT mbox FROM '.$cfg['tablenames']['user'].' WHERE active = 1 AND mbox_exists = 1');
	if(mysql_num_rows($result) > 0) {
	    while($row = mysql_fetch_assoc($result)) {
		$tmp[] = $row['mbox'];
	    }
	    mysql_free_result($result);
	}
	return $tmp;
    }

    /*
     * Accepts a string containing possible destination for an email-address,
     * selects valid destinations and returns them.
     */
    function get_valid_destinations($possible) {
	global $cfg;

	// Define what addresses we will accept.
	$pattern  = $this->regex_valid_email;
	$pattern .= '|'.$this->current_user['mbox'].'|'.txt('5').'|'.strtolower(txt('5'));
	if($cfg['allow_mbox_as_target']) {
	    $mailboxes = &$this->get_mailbox_names();
	    if(count($mailboxes) > 0) {
		$pattern .= '|'.implode('|', $mailboxes);
	    }
	}
	else if($cfg['allow_wcyr_as_target']) {
	    $pattern .= '|[a-z]{2,}[0-9]{4}';
	}

	// Get valid destinations.
	if(preg_match_all('/'.$pattern.'/', $possible, $matched)) {
	    if(is_array($matched[0])) {
		// Replace every occurence of 'mailbox' with the correct name.
		array_walk($matched[0],
				create_function('&$item,$index',
						'if(strtolower($item) == \''.strtolower(txt('5')).'\') $item = \''.$this->current_user['mbox'].'\';'
					       ));
		return $matched[0];
	    }
	}
	return array();
    }

/* ******************************* addresses ******************************** */
    /*
     * Returns a long list with all addresses (the virtual table).
     */
    function get_addresses() {
	global $cfg;
	$alias = array();

	$result = mysql_query('SELECT address, dest, SUBSTRING_INDEX(address, "@", 1) as alias, SUBSTRING_INDEX(address, "@", -1) as domain, active'
				.' FROM '.$cfg['tablenames']['virtual']
				.' WHERE owner="'.$this->current_user['mbox'].'"'.$_SESSION['filter']['str']['address']
				.' ORDER BY domain, dest, alias'
				.$_SESSION['limit']['str']['address']);
	if(mysql_num_rows($result) > 0) {
	    while($row = mysql_fetch_assoc($result)) {
		// explode all destinations (as there may be many)
		$dest = array();
		foreach(explode(',', $row['dest']) as $key => $value) {
		    $value = trim($value);
		    // replace the current user's name with "mailbox"
		    if($value == $this->current_user['mbox'])
			$dest[] = txt('5');
		    else
			$dest[] = $value;
		}
		$row['dest'] = $dest;
		//turn the alias of catchalls to a star
		if($row['address']{0} == '@')
		    $row['alias'] = '*';
		// add the current entry to our list of aliases
		$alias[] = $row;
	    }
	    mysql_free_result($result);
	}

	return $alias;
    }

    /*
     * Creates a new email-address.
     */
    function address_create($alias, $domain, $arr_destinations) {
	global $cfg;

	// May the user create another address?
	if($this->current_user['used_alias'] < $this->current_user['max_alias'] || $this->authenticated_user['a_super'] >= 1) {
	    // If he did choose a catchall, may he create such an address?
	    if($alias == '*' && $cfg['address']['allow_catchall']) {
		if($cfg['address']['restrict_catchall']) {
		    // If either the current or the authenticated user is
		    // owner of that given domain, we can permit creation of that catchall.
		    $result = mysql_query('SELECT domain FROM '.$cfg['tablenames']['domains']
						.' WHERE domain = "'.mysql_real_escape_string($domain).'"'
						.' AND (owner="'.$this->current_user['mbox'].'" OR owner="'.$this->authenticated_user['mbox'].'")'
						.' LIMIT 1');
		    if(mysql_num_rows($result) > 0) {
			mysql_free_result($result);
		    }
		    else {
			$this->error[]	= txt('16');
			return false;
		    }
		    // There shall be no local part in the address. That is characteristic for catchalls.
		    $alias = '';
		}
	    }
	    // Will his new address be a valid one?
	    else if(preg_match('/([A-Z0-9\.\-\_]{'.strlen($alias).'})/i', $alias)) {
		if(!($this->current_user['reg_exp'] == '' || preg_match($this->current_user['reg_exp'], $alias.'@'.$domain))) {
		    $this->error[]	= txt('12');
		    return false;
		}
	    }
	    else {
		$this->error[]	= txt('13');
		return false;
	    }

	    // Finally, create that address.
	    mysql_query('INSERT INTO '.$cfg['tablenames']['virtual'].' (address, dest, owner)'
			.' VALUES ("'.mysql_real_escape_string(strtolower($alias.'@'.$domain)).'", "'.implode(',', $arr_destinations).'", "'.$this->current_user['mbox'].'")');
	    if(mysql_affected_rows() < 1)
		$this->error[]	=mysql_error();
	    else
		$this->current_user['used_alias']++;
	}
	else {
	    $this->error[]	= txt('14');
	    return false;
	}
    }
    /*
     * Deletes the given addresses if they belong to the current user.
     */
    function address_delete($arr_addresses) {
	global $cfg;

	mysql_query('DELETE FROM '.$cfg['tablenames']['virtual']
			.' WHERE owner = "'.$this->current_user['mbox'].'"'
			.' AND FIND_IN_SET(address, "'.mysql_real_escape_string(implode(',', $arr_addresses)).'")'
			.' LIMIT '.count($arr_addresses));
	if(mysql_affected_rows() < count($arr_addresses)) {
	    $this->error[]	= mysql_error();
	}
	else if(mysql_affected_rows() < 1) {
	    return false;
	}
	else {
	    $this->info[]	= sprintf(txt('15'), implode(',', $arr_addresses));
	    $this->current_user['used_alias'] -= mysql_affected_rows();
	    return true;
	}
    }
    /*
     * Changes the destination of the given addresses if they belong to the current user.
     */
    function address_change_destination($arr_addresses, $arr_destinations) {
	global $cfg;

	mysql_query('UPDATE '.$cfg['tablenames']['virtual'].' SET dest = "'.mysql_real_escape_string(implode(',', $arr_destinations)).'", neu = 1'
			.' WHERE owner = "'.$this->current_user['mbox'].'"'
			.' AND FIND_IN_SET(address, "'.mysql_real_escape_string(implode(',', $arr_addresses)).'")'
			.' LIMIT '.count($arr_addresses));
	if(mysql_affected_rows() < 1) {
	    if(mysql_error() != '') {
		$this->error[]	= mysql_error();
		return false;
	    }
	}
	return true;
    }
    /*
     * Toggles the 'active'-flag of a set of addresses  of the current user
     * and thus sets inactive ones to active ones and vice versa.
     */
    function address_toggle_active($arr_addresses) {
	global $cfg;

	mysql_query('UPDATE '.$cfg['tablenames']['virtual'].' SET active = NOT active, neu = 1'
			.' WHERE owner = "'.$this->current_user['mbox'].'"'
			.' AND FIND_IN_SET(address, "'.mysql_real_escape_string(implode(',', $arr_addresses)).'")'
			.' LIMIT '.count($arr_addresses));
	if(mysql_affected_rows() < 1) {
	    $this->error[]	= mysql_error();
	    return false;
	}
	return true;
    }

/* ******************************* domains ********************************** */
    var $editable_domains;	// How many domains can the current user change?
    /*
     * Returns a long list with all domains (from table 'domains').
     */
    function get_domains() {
	global $cfg;
	$this->editable_domains = 0;
	$domains = array();

	$query  = 'SELECT SQL_CALC_FOUND_ROWS *'
		 .' FROM '.$cfg['tablenames']['domains'];
	if($this->authenticated_user['a_super'] > 0) {
		$query .= ' WHERE 1=1 '.$_SESSION['filter']['str']['domain'];
	}
	else {
		$query .= ' WHERE (owner=\''.$this->current_user['mbox'].'\' or a_admin LIKE \'%'.$this->current_user['mbox'].'%\')'
			 .$_SESSION['filter']['str']['domain'];
	}
	$query .= ' ORDER BY owner, length(a_admin), domain'
		 .$_SESSION['limit']['str']['domain'];;

	$result = mysql_query($query);
	if(mysql_num_rows($result) > 0) {
	    while($row = mysql_fetch_assoc($result)) {
		if($row['owner'] == $this->authenticated_user['mbox']
		    || find_in_set($this->authenticated_user['mbox'], $row['a_admin'])) {
		    $row['selectable']	= true;
		    ++$this->editable_domains;
		}
		else {
		    $row['selectable']	= false;
		}
		$domains[] = $row;
	    }
	    mysql_free_result($result);
	    $result = mysql_query('SELECT FOUND_ROWS()');
	    $this->current_user['n_domains'] = mysql_result($result, 0, 0);
	    mysql_free_result($result);

	}

	return $domains;
    }
    /*
     * Adds a new domain into the corresponding table.
     * Categories are for grouping domains.
     */
    function domain_add($domain, $categories, $owner, $administrators) {
	global $cfg;

	if(!stristr($categories, 'all'))
	    $categories = 'all, '.$categories;
	if(trim($owner) == '')
	    $owner = $this->current_user['mbox'];
	if(trim($administrators) == '')
	    $administrators = $this->current_user['mbox'];
	if(!stristr($administrators.$owner, $this->current_user['mbox']))
	    $administrators .= ' '.$this->current_user['mbox'];

	if(preg_match('/^'.$this->regex_valid_domain.'$/i', $domain)) {
	    mysql_query('INSERT INTO '.$cfg['tablenames']['domains'].' (domain, categories, owner, a_admin)'
			.' VALUES ("'.mysql_real_escape_string($domain).'", "'.mysql_real_escape_string($categories).'", "'.mysql_real_escape_string($owner).'", "'.mysql_real_escape_string($administrators).'")');
	    if(mysql_affected_rows() < 1) {
		$this->error[]	= mysql_error();
	    }
	    else {
		return true;
	    }
	}
	else {
	    $this->error[]	= txt('51');
	}

	return false;
    }
    /*
     * Not only removes the given domains by their ids,
     * it deactivates every address which ends in that domain.
     */
    function domain_remove($domains) {
	global $cfg;

	// We need the old domain name later...
	if(is_array($domains) && count($domains) > 0) {
	    if($cfg['admins_delete_domains'])
		$result = mysql_query('SELECT ID, domain'
					.' FROM '.$cfg['tablenames']['domains']
					.' WHERE (owner="'.$this->authenticated_user['mbox'].'" or a_admin LIKE "%'.$this->authenticated_user['mbox'].'%") AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $domains)).'")'
					.' LIMIT '.count($domains));
	    else
		$result = mysql_query('SELECT ID, domain'
					.' FROM '.$cfg['tablenames']['domains']
					.' WHERE owner="'.$this->authenticated_user['mbox'].'" AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $domains)).'")'
					.' LIMIT '.count($domains));
	    if(mysql_num_rows($result) > 0) {
		while($domain = mysql_fetch_assoc($result)) {
		    $del_ID[] = $domain['ID'];
		    $del_nm[] = $domain['domain'];
		}
		mysql_free_result($result); unset($domain);
		mysql_query('DELETE FROM '.$cfg['tablenames']['domains'].' WHERE FIND_IN_SET(ID, "'.implode(',',$del_ID).'") LIMIT '.count($del_ID));
		if(mysql_affected_rows() < 1) {
		    $this->error[]	= mysql_error();
		}
		else {
		    $this->info[]	= txt('52').'<br />'.implode(', ', $del_nm);
		    // We better deactivate all aliases containing that domain, so users can see the domain was deleted.
		    mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET active = 0, neu = 1 WHERE FIND_IN_SET(SUBSTRING(address, LOCATE(\'@\', address)+1), \''.implode(',', $del_nm).'\')');
		    // We can't do such on REGEXP addresses: They may catch more than the given domains.
		    return true;
		}
	    }
	    else {
		$this->error[]	= txt('16');
	    }
	}
	else {
	    $this->error[]	= txt('11');
	}

	return false;
    }
    /*
     * Every parameter is an array.
     */
    function domain_change($domains, $change, $data) {
	global $cfg;

	if(!is_array($change)) {
	    $this->error[]	= txt('53');
	    return false;
	}
	if($cfg['admins_delete_domains'] && in_array('owner', $change))
	    $toc[] = 'owner="'.mysql_real_escape_string($data['owner']).'"';
	if(in_array('a_admin', $change))
	    $toc[] = 'a_admin="'.mysql_real_escape_string($data['a_admin']).'"';
	if(in_array('categories', $change))
	    $toc[] = 'categories="'.mysql_real_escape_string($data['categories']).'"';
	if(isset($toc) && is_array($toc)) {
	    mysql_query('UPDATE '.$cfg['tablenames']['domains']
			.' SET '.implode(',', $toc)
			.' WHERE (owner="'.$this->authenticated_user['mbox'].'" or a_admin LIKE "%'.$this->authenticated_user['mbox'].'%") AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $domains)).'")'
			.' LIMIT '.count($domains));
	    if(mysql_affected_rows() < 1) {
		if(mysql_error() != '') {
		    $this->error[]	= mysql_error();
		}
		else {
		    $this->error[]	= txt('16');
		}
	    }
	}
	// changing ownership if $cfg['admins_delete_domains'] == false
	if(!$cfg['admins_delete_domains'] && in_array('owner', $change)) {
	    mysql_query('UPDATE '.$cfg['tablenames']['domains']
			.' SET owner="'.mysql_escape_string($data['owner']).'"'
			.' WHERE owner="'.$this->authenticated_user['mbox'].'" AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $domains)).'")'
			.' LIMIT '.count($domains));
	}
	// No domain be renamed?
	if(! in_array('domain', $change)) {
	    return true;
	}
	// Otherwise (and if only one) try adapting older addresses.
	if(count($domains) == 1) {
	    if(preg_match('/[a-z0-9\-\_\.]{2,}\.[a-z]{2,}/i', $_POST['domain'])) {
		// Grep the old name, we will need it later for replacement.
		$result = mysql_query('SELECT ID, domain AS name FROM '.$cfg['tablenames']['domains'].' WHERE ID = "'.mysql_real_escape_string($domains[0]).'" AND (owner="'.$this->authenticated_user['mbox'].'" or a_admin LIKE "%'.$this->authenticated_user['mbox'].'%")');
		if(mysql_num_rows($result) == 1) {
		    $domain = mysql_fetch_assoc($result);
		    mysql_free_result($result);
		    // First, update the name. (Corresponding field is marked as unique, therefore we will not receive doublettes.)...
		    mysql_query('UPDATE '.$cfg['tablenames']['domains'].' SET domain = "'.$data['domain'].'" WHERE ID = '.$domain['ID'].' LIMIT 1');
		    // ... and then, change every old address.
		    if(mysql_affected_rows() == 1) {
			// address
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET neu = 1, address = REPLACE(address, "@'.$domain['name'].'", "@'.$data['domain'].'") WHERE address LIKE "%@'.$domain['name'].'"');
			// dest
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET neu = 1, dest = REPLACE(dest, "@'.$domain['name'].'", "@'.$data['domain'].'") WHERE dest LIKE "%@'.$domain['name'].'%"');
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual_regexp'].' SET neu = 1, dest = REPLACE(dest, "@'.$domain['name'].'", "@'.$data['domain'].'") WHERE dest LIKE "%@'.$domain['name'].'%"');
			// canonical
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['user'].' SET canonical = REPLACE(canonical, "@'.$domain['name'].'", "@'.$data['domain'].'") WHERE canonical LIKE "%@'.$domain['name'].'"');
		    }
		    else
			$this->error[]	= mysql_error();
		}
		else
		    $this->error[]	= txt('91');
	    }
	    else
		$this->error[]	= txt('51');
	}
	else
	    $this->error[]	= txt('53');

	return false;
    }

}
?>