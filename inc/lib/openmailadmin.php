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

    var $regex_valid_email	= '[a-z0-9]{1,}[a-z0-9\.\-\_\+]*@[a-z0-9\.\-\_]{2,}\.[a-z]{2,}';
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
	$err	= implode(' ', $this->error);
	return $err;
    }
    /*
     * Concatenates every information message.
     */
    function info_get() {
	$err	= implode(' ', $this->info);
	return $err;
    }

    /*
     * This procedure simply executes every command stored in the array.
     */
    function rollback($what) {
	global $cfg;
	global $imap;

	if(is_array($what)) {
	    foreach($what as $cmd) {
		eval($cmd.';');
	    }
	}
	else {
	    eval($what.';');
	}
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
     * As the name says, returns an array containing the entire row
     * of the "user" table belonging to that mailbox.
     */
    function get_user_row($mailbox) {
	global $cfg;

	$result = mysql_query('SELECT * FROM '.$cfg['tablenames']['user']
				.' WHERE mbox="'.mysql_real_escape_string($mailbox).'"'
				.' LIMIT 1');
	if(mysql_num_rows($result) > 0) {
	    $tmp = mysql_fetch_assoc($result);
	    mysql_free_result($result);
	    return $tmp;
	}

	return false;
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
	if(preg_match_all('/'.$pattern.'/i', $possible, $matched)) {
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

    /*
     * Returns an array containing all domains the user may choose from.
     */
    function get_domain_set($user, $categories, $cache = true) {
	global $cfg;
	$cat = '';
	$poss_dom = array();

	if($cache && isset($_SESSION['cache']['getDomainSet'][$user][$categories])) {
	    return $_SESSION['cache']['getDomainSet'][$user][$categories];
	}
	else {
	    foreach(explode(',', $categories) as $value) {
		$poss_dom[] = trim($value);
		$cat .= ' OR categories LIKE "%'.trim($value).'%"';
	    }
	    $dom = array();
	    $result = mysql_unbuffered_query('SELECT domain FROM '.$cfg['tablenames']['domains']
		    .' WHERE owner="'.$user.'" OR a_admin LIKE "%'.$user.'%" OR FIND_IN_SET(domain, "'.implode(',', $poss_dom).'")'.$cat);
	    if($result != false) {
		while($row = mysql_fetch_assoc($result)) {
		    $dom[] = $row['domain'];
		}
		mysql_free_result($result);
	    }

		$_SESSION['cache']['getDomainSet'][$user][$categories] = $dom;
		return $_SESSION['cache']['getDomainSet'][$user][$categories];
	}
    }

    /*
     * Checks whether a user is a descendant of another user.
     * (Unfortunately, PHP does not support inline functions.)
     */
    function user_is_descendant($child, $parent, $levels = 7, $cache = array()) {
	global $cfg;
	// initialize cache
	if(!isset($_SESSION['cache']['IsDescendant'])) {
	    $_SESSION['cache']['IsDescendant'] = array();
	}

	if(trim($child) == '' || trim($parent) == '')
	    return false;
	if(isset($_SESSION['cache']['IsDescendant'][$parent][$child]))
	    return $_SESSION['cache']['IsDescendant'][$parent][$child];

	if($child == $parent) {
	    $rec = true;
	}
	else if($levels <= 0 ) {
	    $rec = false;
	}
	else {
	    $result = mysql_query('SELECT pate FROM '.$cfg['tablenames']['user']
				    .' WHERE mbox="'.$child.'" LIMIT 1');
	    if(!$result || mysql_num_rows($result) < 1) {
		$rec = false;
	    }
	    else {
		$inter = mysql_result($result, 0, 0);
		mysql_free_result($result);
		if($inter == $parent) {
		    $rec = true;
		}
		else if(in_array($inter, $cache)) {	// avoids loops
		    $rec = false;
		}
		else {
		    $rec = $this->user_is_descendant($inter, $parent, $levels--, array_merge($cache, array($inter)));
		}
	    }
	}
	$_SESSION['cache']['IsDescendant'][$parent][$child] = $rec;
	return $rec;
    }

    /*
     * How many aliases the user has already in use?
     * Does cache, but not session-wide.
     */
    function user_get_used_alias($username) {
	global $cfg;
	static $used = array();

	if(!isset($used[$username])) {
	    $result = mysql_query('SELECT COUNT(*) FROM '.$cfg['tablenames']['virtual'].' WHERE owner=\''.$username.'\'');
	    $used[$username] = mysql_result($result, 0, 0);
	    mysql_free_result($result);
	}

	return $used[$username];
    }
    /*
     * How many regexp-addresses the user has already in use?
     * Does cache, but not session-wide.
     */
    function user_get_used_regexp($username) {
	global $cfg;
	static $used = array();

	if(!isset($used[$username])) {
	    $result = mysql_query('SELECT COUNT(*) FROM '.$cfg['tablenames']['virtual_regexp'].' WHERE owner=\''.$username.'\'');
	    $used[$username] = mysql_result($result, 0, 0);
	    mysql_free_result($result);
	}

	return $used[$username];
    }

    /*
     * These just count how many elements have been assigned to that given user.
     */
    function user_get_number_mailboxes($username) {
	global $cfg;

	if(!isset($_SESSION['cache']['n_Mailboxes'][$username]['mailboxes'])) {
	    $result = mysql_query('SELECT COUNT(*) FROM '.$cfg['tablenames']['user']
				    .' WHERE pate = "'.$username.'"');
	    $_SESSION['cache']['n_Mailboxes'][$username]['mailboxes'] = mysql_result($result, 0, 0);
	    mysql_free_result($result);
	}

	return $_SESSION['cache']['n_Mailboxes'][$username]['mailboxes'];
    }
    /*
     * These just count how many elements have been assigned to that given user.
     */
    function user_get_number_domains($username) {
	global $cfg;

	if(!isset($_SESSION['cache']['n_Domains'][$username]['domains'])) {
	    $result = mysql_query('SELECT COUNT(*) FROM '.$cfg['tablenames']['domains']
				    .' WHERE owner = "'.$username.'"');
	    $_SESSION['cache']['n_Domains'][$username]['domains'] = mysql_result($result, 0, 0);
	    mysql_free_result($result);
	}

	return $_SESSION['cache']['n_Domains'][$username]['domains'];
    }
    /*
     * In case you have changed something about domains...
     */
    function user_invalidate_domain_sets() {
	if(isset($_SESSION['cache']['getDomainSet'])) {
	    unset($_SESSION['cache']['getDomainSet']);
	}
    }

/* ******************************* addresses ******************************** */
    /*
     * Returns a long list with all addresses (the virtual table).
     */
    function get_addresses() {
	global $cfg;
	$alias = array();

	$result = mysql_unbuffered_query('SELECT address, dest, SUBSTRING_INDEX(address, "@", 1) as alias, SUBSTRING_INDEX(address, "@", -1) as domain, active'
				.' FROM '.$cfg['tablenames']['virtual']
				.' WHERE owner="'.$this->current_user['mbox'].'"'.$_SESSION['filter']['str']['address']
				.' ORDER BY domain, dest, alias'
				.$_SESSION['limit']['str']['address']);
	if($result) {
	    while($row = mysql_fetch_assoc($result)) {
		// explode all destinations (as there may be many)
		$dest = array();
		foreach(explode(',', $row['dest']) as $value) {
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
	if($this->current_user['used_alias'] < $this->current_user['max_alias']
		|| $this->authenticated_user['a_super'] >= 1) {
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
		if(!((isset($this->current_user['reg_exp']) && $this->current_user['reg_exp'] == '')
			|| preg_match($this->current_user['reg_exp'], $alias.'@'.$domain))) {
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
	    if(mysql_affected_rows() < 1) {
		$this->error[]	=mysql_error();
	    }
	    else {
		$this->current_user['used_alias']++;
		return true;
	    }
	}
	else {
	    $this->error[]	= txt('14');
	}

	return false;
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
	if(mysql_affected_rows() < 1) {
	    if(mysql_errno() != 0) {
		$this->error[]	= mysql_error();
	    }
	}
	else {
	    $this->info[]	= sprintf(txt('15'), implode(',', $arr_addresses));
	    $this->current_user['used_alias'] -= mysql_affected_rows();
	    return true;
	}

	return false;
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
	    if(mysql_errno() != 0) {
		$this->error[]	= mysql_error();
	    }
	}
	else {
	    return true;
	}

	return false;
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
	    if(mysql_errno() != 0) {
		$this->error[]	= mysql_error();
	    }
	}
	else {
	    return true;
	}

	return false;
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

	$query  = 'SELECT *'
		 .' FROM '.$cfg['tablenames']['domains'];
	if($this->authenticated_user['a_super'] > 0) {
		$query .= ' WHERE 1=1 '.$_SESSION['filter']['str']['domain'];
	}
	else {
		$query .= ' WHERE (owner="'.$this->current_user['mbox'].'" or a_admin LIKE "%'.$this->current_user['mbox'].'%")'
			 .$_SESSION['filter']['str']['domain'];
	}
	$query .= ' ORDER BY owner, length(a_admin), domain'
		 .$_SESSION['limit']['str']['domain'];

	$result = mysql_unbuffered_query($query);
	if($result) {
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
	}

	$this->current_user['n_domains'] = $this->user_get_number_domains($this->current_user['mbox']);

	return $domains;
    }
    /*
     * May the new user only select from domains which have been assigned to
     * the reference user? If so, return true.
     * $reference is an user, $tobechecked an mailboxname.
     */
    function domain_check($reference, $tobechecked, $domain_key) {
	if(!isset($reference['domain_set'])) {
	    $reference['domain_set'] = $this->get_domain_set($reference['mbox'], $reference['domains']);
	}
	// new domain-key must not lead to more domains than the user already has to choose from
	// A = Domains the new user will be able to choose from.
	$dom_a = $this->get_domain_set(mysql_escape_string($tobechecked), mysql_escape_string($domain_key), false);
	// B = Domains the creator may choose from (that is $reference['domain_set'])?
	// Okay, if A is part of B. (Thus, no additional domains are added for user "A".)
	// Indication: A <= B
	if(count($dom_a) == 0) {
	    // This will be only a warning.
	    $this->error[] = txt('80');
	}
	else if(count($dom_a) > count($reference['domain_set'])
		&& count(array_diff($dom_a, $reference['domain_set'])) > 0) {
	    // A could have domains which the reference cannot access.
	    return false;
	}

	return true;
    }
    /*
     * Adds a new domain into the corresponding table.
     * Categories are for grouping domains.
     */
    function domain_add($domain, $props) {
	global $cfg;

	$props['domain'] = $domain;
	if(!$this->validate_input($props, array('domain', 'categories', 'owner', 'a_admin'))) {
	    return false;
	}

	if(!stristr($props['categories'], 'all'))
	    $props['categories'] = 'all,'.$props['categories'];
	if(!stristr($props['a_admin'], $this->current_user['mbox']))
	    $props['a_admin'] .= ','.$this->current_user['mbox'];

	mysql_query('INSERT INTO '.$cfg['tablenames']['domains'].' (domain, categories, owner, a_admin)'
		    .' VALUES ("'.$domain.'", "'.$props['categories'].'", "'.$props['owner'].'", "'.$props['a_admin'].'")');
	if(mysql_affected_rows() < 1) {
	    $this->error[]	= mysql_error();
	}
	else {
	    $this->user_invalidate_domain_sets();
	    return true;
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
			.' WHERE (owner="'.$this->authenticated_user['mbox'].'" OR a_admin LIKE "%'.$this->authenticated_user['mbox'].'%") AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $domains)).'")'
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
		    if(mysql_errno() != 0) {
			$this->error[]	= mysql_error();
		    }
		}
		else {
		    $this->info[]	= txt('52').'<br />'.implode(', ', $del_nm);
		    // We better deactivate all aliases containing that domain, so users can see the domain was deleted.
		    mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET active = 0, neu = 1 WHERE FIND_IN_SET(SUBSTRING(address, LOCATE(\'@\', address)+1), \''.implode(',', $del_nm).'\')');
		    // We can't do such on REGEXP addresses: They may catch more than the given domains.
		    $this->user_invalidate_domain_sets();
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
     * Every parameter is an array. $domains contains IDs.
     */
    function domain_change($domains, $change, $data) {
	global $cfg;
	$toc = array();		// to be changed

	if(!$this->validate_input($data, $change)) {
	    return false;
	}

	if(!is_array($change)) {
	    $this->error[]	= txt('53');
	    return false;
	}
	if($cfg['admins_delete_domains'] && in_array('owner', $change))
	    $toc[] = 'owner="'.$data['owner'].'"';
	if(in_array('a_admin', $change))
	    $toc[] = 'a_admin="'.$data['a_admin'].'"';
	if(in_array('categories', $change))
	    $toc[] = 'categories="'.$data['categories'].'"';
	if(count($toc) > 0) {
	    mysql_query('UPDATE '.$cfg['tablenames']['domains']
			.' SET '.implode(',', $toc)
			.' WHERE (owner="'.$this->authenticated_user['mbox'].'" or a_admin LIKE "%'.$this->authenticated_user['mbox'].'%") AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $domains)).'")'
			.' LIMIT '.count($domains));
	    if(mysql_affected_rows() < 1) {
		if(mysql_errno() != 0) {
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
			.' SET owner="'.mysql_real_escape_string($data['owner']).'"'
			.' WHERE owner="'.$this->authenticated_user['mbox'].'" AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $domains)).'")'
			.' LIMIT '.count($domains));
	}
	$this->user_invalidate_domain_sets();
	// No domain be renamed?
	if(! in_array('domain', $change)) {
	    return true;
	}
	// Otherwise (and if only one) try adapting older addresses.
	if(count($domains) == 1) {
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
		    else {
			$this->error[]	= mysql_error();
		    }

		    return true;
		}
		else
		    $this->error[]	= txt('91');
	}
	else
	    $this->error[]	= txt('53');

	return false;
    }

/* ******************************* passwords ******************************** */
    /*
     * (Re)sets the user's password.
     * Use this if the old password does not matter.
     * Includes the check whether the user has the right to do this.
     */
    function user_set_password($username, $plaintext_password) {
	global $cfg;

	// Check whether the authenticated user has the right to do that.
	if($this->authenticated_user['a_super'] < 1
		&& $username != $this->authenticated_user['mbox']
		&& !$this->user_is_descendant($username, $this->authenticated_user['mbox'])) {
	    $this->error[]	= txt('49');
	    return false;
	}

	if($plaintext_password != '') {
	    $new_crypt	= crypt($plaintext_password, substr($plaintext_password,0,2));
	    $new_md5	= md5($plaintext_password);
	}
	else {
	    $new_crypt = '';
	    $new_md5 = '';
	}
	mysql_query('UPDATE '.$cfg['tablenames']['user']
			.' SET pass_crypt="'.$new_crypt.'", pass_md5="'.$new_md5.'"'
			.' WHERE mbox="'.$username.'" LIMIT 1');
	if(mysql_affected_rows() > 0) {
	    $this->info[]	= txt('48');
	    return true;
	}
	else {
	    if(mysql_errno() != 0) {
		$this->error[]	= mysql_error();
	    }
	    return false;
	}
    }

    /*
     * Changes the current user's password.
     * This requires the former password for authentication if current user and
     * authenticated user are the same.
     */
   function user_change_password($new, $new_repeat, $old_passwd = null) {
	global $cfg;

	if($this->current_user['mbox'] == $this->authenticated_user['mbox']
		&& !is_null($old_passwd)
		&& !(passwd_check($old_passwd, $this->current_user['pass_crypt'])
			|| passwd_check($old_passwd, $this->current_user['pass_md5']))) {
	    $this->error[]	= txt('45');
	}
	else if($new != $new_repeat) {
	    $this->error[]	= txt('44');
	}
	else if(strlen($new) < $cfg['passwd']['min_length']
		|| strlen($new) > $cfg['passwd']['max_length']) {
	    $this->error[]	= sprintf(txt('46'), $cfg['passwd']['min_length'], $cfg['passwd']['max_length']);
	}
	else {
	    // Warn about insecure passwords, but let them pass.
	    if(!(preg_match('/[a-z]{1}/', $new) && preg_match('/[A-Z]{1}/', $new) && preg_match('/[0-9]{1}/', $new))) {
		$this->error[]	= txt('47');
	    }
	    if($this->user_set_password($this->current_user['mbox'], $new)) {
		return true;
	    }
	}

	return false;
    }

/* ******************************* regexp *********************************** */
    /*
     * Returns a long list with all regular expressions (the virtual_regexp table).
     * If $match_against is given, the flag "matching" will be set on matches.
     */
    function get_regexp($match_against = null) {
	global $cfg;
	$regexp = array();

	$result = mysql_unbuffered_query('SELECT * FROM '.$cfg['tablenames']['virtual_regexp']
			.' WHERE owner="'.$this->current_user['mbox'].'"'.$_SESSION['filter']['str']['regexp']
			.' ORDER BY dest'.$_SESSION['limit']['str']['regexp']);
	if($result) {
	    while($row = mysql_fetch_assoc($result)) {
		// if ordered, check whether expression matches probe address
		if(!is_null($match_against)
			&& @preg_match($row['reg_exp'], $match_against)) {
		    $row['matching']	= true;
		}
		else {
		    $row['matching']	= false;
		}
		// explode all destinations (as there may be many)
		$dest = array();
		foreach(explode(',', $row['dest']) as $value) {
		    $value = trim($value);
		    // replace the current user's name with "mailbox"
		    if($value == $this->current_user['mbox'])
			$dest[] = txt('5');
		    else
			$dest[] = $value;
		}
		$row['dest'] = $dest;
		// add the current entry to our list of aliases
		$regexp[] = $row;
	    }
	    mysql_free_result($result);
	}

	return $regexp;
    }
    /*
     * Creates a new regexp-address.
     */
    function regexp_create($regexp, $arr_destinations) {
	global $cfg;

	// some dull checks;
	// if someone knows how to find out whether an string is a valid regexp -> write me please
	if($regexp == '' || $regexp{0} != '/') {
	    $this->error[]	= txt('127');
	    return false;
	}

	if($this->current_user['used_regexp'] < $this->current_user['max_regexp']
		|| $this->authenticated_user['a_super'] > 0) {
	    mysql_query('INSERT INTO '.$cfg['tablenames']['virtual_regexp'].' (reg_exp, dest, owner)'
			.' VALUES ("'.mysql_real_escape_string($regexp).'", "'.implode(',', $arr_destinations).'", "'.$this->current_user['mbox'].'")');
	    if(mysql_affected_rows() < 1) {
		if(mysql_errno() != 0) {
		    $this->error[]	= mysql_error();
		}
	    }
	    else {
		$this->current_user['used_regexp']++;
		return true;
	    }
	}
	else {
	    $this->error[]	= txt('31');
	}

	return false;
    }
    /*
     * Deletes the given regular expressions if they belong to the current user.
     */
    function regexp_delete($arr_regexp_ids) {
	global $cfg;

	mysql_query('DELETE FROM '.$cfg['tablenames']['virtual_regexp']
			.' WHERE owner = "'.$this->current_user['mbox'].'"'
			.' AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $arr_regexp_ids)).'")'
			.' LIMIT '.count($arr_regexp_ids));
	if(mysql_affected_rows() < 1) {
	    if(mysql_errno() != 0) {
		$this->error[]	= mysql_error();
	    }
	}
	else {
	    $this->info[]	= txt('32');
	    $this->current_user['used_regexp'] -= mysql_affected_rows();
	    return true;
	}

	return false;
    }
    /*
     * See "address_change_destination".
     */
    function regexp_change_destination($arr_regexp_ids, $arr_destinations) {
	global $cfg;

	mysql_query('UPDATE '.$cfg['tablenames']['virtual_regexp'].' SET dest = "'.implode(',', $arr_destinations).'", neu = 1'
			.' WHERE owner = "'.$this->current_user['mbox'].'"'
			.' AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $arr_regexp_ids)).'")'
			.' LIMIT '.count($arr_regexp_ids));
	if(mysql_affected_rows() < 1) {
	    if(mysql_errno() != 0) {
		$this->error[]	= mysql_error();
	    }
	}
	else {
	    return true;
	}

	return false;
    }
    /*
     * See "address_toggle_active".
     */
    function regexp_toggle_active($arr_regexp_ids) {
	global $cfg;

	mysql_query('UPDATE '.$cfg['tablenames']['virtual_regexp'].' SET active = NOT active, neu = 1'
			.' WHERE owner = "'.$this->current_user['mbox'].'"'
			.' AND FIND_IN_SET(ID, "'.mysql_real_escape_string(implode(',', $arr_regexp_ids)).'")'
			.' LIMIT '.count($arr_regexp_ids));
	if(mysql_affected_rows() < 1) {
	    if(mysql_errno() != 0) {
		$this->error[]	= mysql_error();
	    }
	}
	else {
	    return true;
	}

	return false;
    }

/* ******************************* mailboxes ******************************** */
    /*
     * Returns list with mailboxes the current user can see.
     * Typically all his patenkinder will show up.
     * If the current user is at his pages and is superuser, he will see all mailboxes.
     */
    function get_mailboxes() {
	global $cfg;
	$mailboxes = array();

	if($this->current_user['mbox'] == $this->authenticated_user['mbox']
		&& $this->authenticated_user['a_super'] >= 1) {
	    $where_clause = ' WHERE TRUE';
	}
	else {
	    $where_clause = ' WHERE pate="'.$this->current_user['mbox'].'"';
	}
	$result = mysql_query('SELECT mbox, person, canonical, pate, max_alias, max_regexp, active, last_login AS lastlogin, a_super, a_admin_domains, a_admin_user, '
					.'(SELECT count(*) FROM '.$cfg['tablenames']['virtual']
					.' WHERE '.$cfg['tablenames']['virtual'].'.owner=mbox) AS num_alias, '
					.'(SELECT count(*) FROM '.$cfg['tablenames']['virtual_regexp']
					.' WHERE '.$cfg['tablenames']['virtual_regexp'].'.owner=mbox) AS num_regexp'
				.' FROM '.$cfg['tablenames']['user']
				.$where_clause.$_SESSION['filter']['str']['mbox']
				.' ORDER BY pate, mbox'.$_SESSION['limit']['str']['mbox']);

	if($result) {
	    while($row = mysql_fetch_assoc($result)) {
		if(in_array($row['mbox'], $cfg['user_ignore']))
		    continue;

		$row['quota'] = hsys_format_quota($row['mbox']);
		$mailboxes[] = $row;
	    }
	    mysql_free_result($result);
	}
	$this->current_user['n_mbox'] = $this->user_get_number_mailboxes($this->current_user['mbox']);

	return $mailboxes;
    }

    /*
     * This will return a list with $whose's patenkinder for further use in selections.
     */
    function get_selectable_paten($whose) {
	global $cfg;

	if(!isset($_SESSION['paten'][$whose])) {
	    $selectable_paten = array();
	    if($this->authenticated_user['a_super'] >= 1) {
		$result = mysql_unbuffered_query('SELECT mbox FROM '.$cfg['tablenames']['user']);
	    }
	    else {
		$result = mysql_unbuffered_query('SELECT mbox FROM '.$cfg['tablenames']['user']
					.' WHERE pate="'.$whose.'"');
	    }
	    while($row = mysql_fetch_assoc($result)) {
		if(in_array($row['mbox'], $cfg['user_ignore']))
		    continue;
		$selectable_paten[] = $row['mbox'];
	    }
	    mysql_free_result($result);
	    $selectable_paten[] = $whose;
	    $selectable_paten[] = $this->authenticated_user['mbox'];

	    // Array_unique() will do alphabetical sorting.
	    $_SESSION['paten'][$whose] = array_unique($selectable_paten);
	}

	return $_SESSION['paten'][$whose];
    }

    /*
     * Eliminates every mailbox name from $desired_mboxes which is no descendant
     * of $who. If the authenticated user is superuser, no filtering is done
     * except elimination imposed by $cfg['user_ignore'].
     */
    function mailbox_filter_manipulable($who, $desired_mboxes) {
	global $cfg;
	$allowed = array();

	// Does the authenticated user have the right to do that?
	if($this->authenticated_user['a_super'] >= 1) {
	    $allowed = array_diff($desired_mboxes, $cfg['user_ignore']);
	}
	else {
	    foreach($desired_mboxes as $mbox) {
		if(!in_array($mbox, $cfg['user_ignore']) && $this->user_is_descendant($mbox, $who)) {
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
    function mailbox_create($mboxname, $props) {
	global $cfg;
	global $imap;
	$rollback	= array();

	// Check inputs for sanity and consistency.
	if(!$this->authenticated_user['a_admin_user'] > 0) {
	    $this->error[]	= txt('16');
	    return false;
	}
	if(in_array($mboxname, $cfg['user_ignore'])) {
	    $this->error[]	= sprintf(txt('130'), txt('83'));
	    return false;
	}
	$mboxname = mysql_real_escape_string($mboxname);
	if(!$this->validate_input($props, array('mbox','person','pate','canonical','reg_exp','domains','max_alias','max_regexp','a_admin_domains','a_admin_user','a_super','quota'))) {
	    return false;
	}

	// contingents (only if non-superuser)
	if($this->authenticated_user['a_super'] == 0) {
	    // As the current user's contingents will be decreased we have to use his values.
	    if($props['max_alias'] > ($this->current_user['max_alias'] - $this->user_get_used_alias($this->current_user['mbox']))
			|| $props['max_regexp'] > ($this->current_user['max_regexp'] - $this->user_get_used_regexp($this->current_user['mbox']))) {
		$this->error[]	= txt('66');
		return false;
	    }
	    if(hsys_getMaxQuota($this->current_user['mbox']) != 'NOT-SET'
			&& $_POST['quota'] > (hsys_getMaxQuota($this->current_user['mbox']) - hsys_getUsedQuota($this->current_user['mbox']))) {
		$this->error[]	= txt('65');
		return false;
	    }
	}

	// first create the default-from (canonical) (must not already exist!)
	if($cfg['create_canonical']) {
		mysql_query('INSERT INTO '.$cfg['tablenames']['virtual'].' (address, dest, owner)'
			    .' VALUES ("'.$props['canonical'].'", "'.$mboxname.'", "'.$mboxname.'")');
		if(mysql_affected_rows() < 1) {
		    $this->error[]	= mysql_error();
		    return false;
		}
		$rollback[] = 'mysql_unbuffered_query(\'DELETE FROM \'.$cfg[\'tablenames\'][\'virtual\'].\' WHERE address="'.$props['canonical'].'" AND owner="'.$mboxname.'" LIMIT 1\');';
	}

	// on success write the new user to database
	mysql_query('INSERT INTO '.$cfg['tablenames']['user'].' (mbox, person, pate, canonical, reg_exp, domains, max_alias, max_regexp, created, a_admin_domains, a_admin_user, a_super)'
		    .' VALUES ("'.$props['mbox'].'","'.$props['person'].'","'.$props['pate'].'","'.$props['canonical'].'","'.$props['reg_exp'].'","'.$props['domains'].'",'.$props['max_alias'].','.$props['max_regexp'].', '.time().', '.$props['a_admin_domains'].', '.$props['a_admin_user'].', '.$props['a_super'].')');
	if(mysql_affected_rows() < 1) {
	    $this->error[]	= mysql_error();
	    // Rollback
	    $this->rollback($rollback);
	    return false;
	}
	$rollback[] = 'mysql_unbuffered_query(\'DELETE FROM \'.$cfg[\'tablenames\'][\'user\'].\' WHERE mbox="'.$mboxname.'" LIMIT 1\');';

	// Decrease current users's contingents...
	if($this->authenticated_user['a_super'] == 0) {
	    $rollback[] = 'mysql_unbuffered_query(\'UPDATE \'.$cfg[\'tablenames\'][\'user\'].\' SET max_alias='.$this->current_user['max_alias'].', max_regexp='.$this->current_user['max_regexp'].' WHERE mbox="'.$this->current_user['mbox'].'" LIMIT 1\');';
	    mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user']
			.' SET max_alias='.($this->current_user['max_alias']-intval($props['max_alias'])).', max_regexp='.($this->current_user['max_regexp']-intval($props['max_regexp']))
			.' WHERE mbox="'.$this->current_user['mbox'].'" LIMIT 1');
	}
	// ... and then create the user on the server.
	$result = $imap->createmb(cyrus_format_user($mboxname));
	if(!$result) {
	    $this->error[]	= $imap->error_msg;
	    // Rollback
	    $this->rollback($rollback);
	    return false;
	}
	else {
	    mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['user'].' SET mbox_exists=1 WHERE mbox="'.$mboxname.'" LIMIT 1');
	    if(isset($cfg['folders']['create_default']) && is_array($cfg['folders']['create_default'])) {
		foreach($cfg['folders']['create_default'] as $new_folder) {
		    $imap->createmb(cyrus_format_user($mboxname, $new_folder));
		}
	    }
	}
	$rollback[] = '$imap->deletemb(cyrus_format_user(\''.$mboxname.'\'));';

	// Decrease the creator's quota...
	if($this->authenticated_user['a_super'] == 0 && hsys_getMaxQuota($this->current_user['mbox']) != 'NOT-SET') {
	    $tmp = hsys_getMaxQuota($this->current_user['mbox']);
	    $result = $imap->setquota(cyrus_format_user($this->current_user['mbox']), $tmp-$props['quota']);
	    if(!$result) {
		$this->error[]	= $imap->error_msg;
		// Rollback
		$this->rollback($rollback);
		return false;
	    }
	    $rollback[] = '$imap->setquota(cyrus_format_user($this->current_user[\'mbox\']), '.$tmp.'));';
	    $this->info[]	= sprintf(txt('69'), hsys_getMaxQuota($this->current_user['mbox'])-$props['quota']);
	}
	else {
	    $this->info[]	= txt('71');
	}

	// ... and set the new user's quota.
	if(is_numeric($props['quota'])) {
	    $result = $imap->setquota(cyrus_format_user($mboxname), $props['quota']);
	    if(!$result) {
		$this->error[]	= $imap->error_msg;
		// Rollback
		$this->rollback($rollback);
		return false;
	    }
	}
	$this->info[]	= sprintf(txt('72'), B($mboxname), B($props['person']));
	if(isset($_SESSION['paten'][$props['pate']])) {
	    $_SESSION['paten'][$props['pate']][] = $mboxname;
	}

	return true;
    }

    /*
     * $props can be $_POST, as every sutable field from $change is used.
     */
    function mailbox_change($mboxnames, $change, $props) {
	global $cfg;
	global $imap;

	// Ensure sanity of inputs and check requirements.
	if(!$this->authenticated_user['a_admin_user'] > 0) {
	    $this->error[]	= txt('16');
	    return false;
	}
	if(!$this->validate_input($props, $change)) {
	    return false;
	}
	$mboxnames = $this->mailbox_filter_manipulable($this->authenticated_user['mbox'], $mboxnames);
	if(!count($mboxnames) > 0) {
	    return false;
	}
	$aux_tmp = mysql_real_escape_string(implode(',', $mboxnames));

	// Create an array holding every property we have to change.
	$to_change	= array();
	foreach(array('person', 'canonical', 'pate', 'domains', 'reg_exp', 'a_admin_domains', 'a_admin_user', 'a_super')
		as $property) {
	    if(in_array($property, $change)) {
		if(is_numeric($props[$property])) {
		    $to_change[]	= $property.' = '.$props[$property];
		}
		else {
		    $to_change[]	= $property.' = "'.$props[$property].'"';
		}
	    }
	}

	// Execute the change operation regarding properties in DB.
	if(count($to_change) > 0) {
	    mysql_query('UPDATE '.$cfg['tablenames']['user']
			.' SET '.implode(',', $to_change)
			.' WHERE FIND_IN_SET(mbox, "'.$aux_tmp.'")'
			.' LIMIT '.count($mboxnames));
	}

	// Manipulate contingents (except quota).
	foreach(array('max_alias', 'max_regexp') as $what) {
	    if(in_array($what, $change)) {
		$seek_table = $what == 'max_alias'
					? $cfg['tablenames']['virtual']
					: $cfg['tablenames']['virtual_regexp'];
		$to_be_processed = $mboxnames;
		// Select users which use more aliases than allowed in future.
		$result = mysql_query('SELECT COUNT(*) AS consum, owner, person'
			    .' FROM '.$seek_table.','.$cfg['tablenames']['user']
			    .' WHERE FIND_IN_SET(owner, "'.$aux_tmp.'") AND owner=mbox'
			    .' GROUP BY owner'
			    .' HAVING consum > '.$props[$what]);
		if(mysql_num_rows($result) > 0) {
		    // We have to skip them.
		    $have_skipped = array();
		    while($row = mysql_fetch_assoc($result)) {
			$have_skipped[] = $row['owner'];
			if($cfg['mboxview_pers']) {
			    $tmp[] = '<a href="'.mkSelfRef(array('cuser' => $row['owner'])).'" title="'.$row['owner'].'">'.$row['person'].' ('.$row['consum'].')</a>';
			}
			else {
			    $tmp[] = '<a href="'.mkSelfRef(array('cuser' => $row['owner'])).'" title="'.$row['person'].'">'.$row['owner'].' ('.$row['consum'].')</a>';
			}
		    }
		    mysql_free_result($result);
		    $this->error[]	= sprintf(txt('131'),
					    $props[$what], $what == 'max_alias' ? txt('88') : txt('89'),
					    implode(', ', $tmp));
		    $to_be_processed = array_diff($to_be_processed, $have_skipped);
		}
		if(count($to_be_processed) > 0) {
		    // We don't need further checks if a superuser is logged in.
		    if($this->authenticated_user['a_super'] > 0) {
			mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user']
			    .' SET '.$what.'='.$props[$what]
			    .' WHERE FIND_IN_SET(mbox, "'.implode(',', $to_be_processed).'")'
			    .' LIMIT '.count($to_be_processed));
		    }
		    else {
			// Now, calculate whether the current user has enough free contingents.
			$result = mysql_query('SELECT SUM('.$props[$what].'-'.$what.')'
				    .' FROM '.$cfg['tablenames']['user']
				    .' WHERE FIND_IN_SET(mbox, "'.implode(',', $to_be_processed).'")'
				    .' LIMIT '.count($to_be_processed));
			$has_to_be_free = mysql_result($result, 0, 0);
			if($has_to_be_free <= $this->user_get_used_alias($this->current_user['mbox'])) {
			    // If so, set new contingents on the users...
			    mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user']
				.' SET '.$what.'='.$props[$what]
				.' WHERE FIND_IN_SET(mbox, "'.implode(',', $to_be_processed).'")'
				.' LIMIT '.count($to_be_processed));
			    // ... and add/remove the difference from the current user.
			    mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user']
				.' SET '.$what.'='.$what.'-'.$has_to_be_free
				.' WHERE mbox="'.$this->current_user['mbox'].'"'
				.' LIMIT 1');
			}
			else {
			    // Else, we have to show an error message.
			    $this->error[]	= txt('66');
			}
		    }
		}
	    }
	}

	// Change Quota.
	if(in_array('quota', $change)) {
	    $add_quota = 0;
	    if($this->authenticated_user['a_super'] == 0) {
		foreach($mboxnames as $user) {
		    if($user != '') {
			if(hsys_getMaxQuota($user) != 'NOT-SET')
			    $add_quota += intval($props['quota']) - hsys_getMaxQuota($user);
		    }
		}
		if($add_quota != 0 && hsys_getMaxQuota($this->current_user['mbox']) != 'NOT-SET') {
		    $imap->setquota(cyrus_format_user($this->current_user['mbox']), hsys_getMaxQuota($this->current_user['mbox'])-$add_quota);
		    $this->info[]	= sprintf(txt('78'), hsys_getMaxQuota($this->current_user['mbox']));
		}
	    }
	    reset($mboxnames);
	    foreach($mboxnames as $user) {
		if($user != '') {
		    $result = $imap->setquota(cyrus_format_user($user), intval($props['quota']));
		    if(!$result) {
			$this->error[]	= $imap->error_msg;
		    }
		}
	    }
	}

	// Renaming of (single) user.
	if(in_array('mbox', $change)) {
	    if($imap->renamemb(cyrus_format_user($mboxnames['0']), cyrus_format_user($props['mbox']))) {
		mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['user'].' SET mbox = "'.$props['mbox'].'" WHERE mbox = "'.$mboxnames['0'].'" LIMIT 1');
		mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['domains'].' SET owner = "'.$props['mbox'].'" WHERE owner = "'.$mboxnames['0'].'"');
		mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['domains'].' SET a_admin = REPLACE(a_admin, "'.$mboxnames['0'].'", "'.$props['mbox'].'") WHERE a_admin LIKE "%'.$mboxnames['0'].'%"');
		mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET dest = REPLACE(dest, "'.$mboxnames['0'].'", "'.$props['mbox'].'"), neu = 1 WHERE dest REGEXP "'.$mboxnames['0'].'[^@]{1,}" OR dest LIKE "%'.$mboxnames['0'].'"');
		mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET owner = "'.$props['mbox'].'" WHERE owner = "'.$mboxnames['0'].'"');
		mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual_regexp'].' SET dest = REPLACE(dest, "'.$mboxnames['0'].'", "'.$props['mbox'].'"), neu = 1 WHERE dest REGEXP "'.$mboxnames['0'].'[^@]{1,}" OR dest LIKE "%'.$mboxnames['0'].'"');
		mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual_regexp'].' SET owner = "'.$props['mbox'].'" WHERE owner = "'.$mboxnames['0'].'"');
	    }
	    else {
		$this->error[]	= $imap->error_msg.'<br />'.txt('94');
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
    function mailbox_delete($mboxnames) {
	global $cfg;
	global $imap;

	$mboxnames = $this->mailbox_filter_manipulable($this->authenticated_user['mbox'], $mboxnames);
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
		if($this->authenticated_user['a_super'] == 0
			&& hsys_getMaxQuota($user) != 'NOT-SET') {
		    $toadd = hsys_getMaxQuota($user);
		}
		$result = $imap->deletemb(cyrus_format_user($user));
		if(!$result) {		// failure
		    $this->error[]	= $imap->error_msg;
		}
		else {			// success
		    $add_quota += $toadd;
		    $processed[] = $user;
		}
	    }
	}

	// We need not proceed in case no users were deleted.
	if(count($processed) == 0) {
	    return false;
	}
	$aux_tmp = mysql_real_escape_string(implode(',', $processed));

	// Now we have to increase the current user's quota.
	if($this->authenticated_user['a_super'] == 0
		&& $add_quota > 0
		&& hsys_getMaxQuota($this->current_user['mbox']) != 'NOT-SET') {
	    $imap->setquota(cyrus_format_user($this->current_user['mbox']), hsys_getMaxQuota($this->current_user['mbox'])+$add_quota);
	    $this->info[]	= sprintf(txt('76'), (hsys_getMaxQuota($this->current_user['mbox'])+$add_quota));
	}

	// Calculate how many contingents get freed if we delete the users.
	if($this->authenticated_user['a_super'] == 0) {
	    $result = mysql_query('SELECT SUM(max_alias) AS nr_alias, SUM(max_regexp) AS nr_regexp'
				.' FROM '.$cfg['tablenames']['user']
				.' WHERE FIND_IN_SET(mbox, "'.$aux_tmp.'")');
	    if(mysql_num_rows($result) > 0) {
		$will_be_free = mysql_fetch_assoc($result);
		mysql_free_result($result);
	    }
	}

	// virtual
	mysql_unbuffered_query('DELETE FROM '.$cfg['tablenames']['virtual'].' WHERE FIND_IN_SET(owner, "'.$aux_tmp.'")');
	mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['virtual'].' SET active=0, neu=1 WHERE FIND_IN_SET(dest, "'.$aux_tmp.'")');
	// virtual.regexp
	mysql_unbuffered_query('DELETE FROM '.$cfg['tablenames']['virtual_regexp'].' WHERE FIND_IN_SET(owner, "'.$aux_tmp.'")');
	mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['virtual_regexp'].' SET active=0, neu=1 WHERE FIND_IN_SET(dest, "'.$aux_tmp.'")');
	// domain (if the one to be deleted owns domains, the deletor will inherit them)
	mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['domains'].' SET owner="'.$this->current_user['mbox'].'" WHERE FIND_IN_SET(owner, "'.$aux_tmp.'")');
	// user
	mysql_unbuffered_query('DELETE FROM '.$cfg['tablenames']['user'].' WHERE FIND_IN_SET(mbox, "'.$aux_tmp.'")');
	if($this->authenticated_user['a_super'] == 0 && isset($will_be_free['nr_alias'])) {
	    mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user']
		.' SET max_alias='.($this->current_user['max_alias']+$will_be_free['nr_alias']).', max_regexp='.($this->current_user['max_regexp']+$will_be_free['nr_regexp'])
		.' WHERE mbox="'.$this->current_user['mbox'].'" LIMIT 1');
	}
	// patenkinder (will be inherited by the one deleting)
	mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user']
		.' SET pate="'.$this->current_user['mbox'].'"'
		.' WHERE FIND_IN_SET(pate, "'.$aux_tmp.'")');

	$this->info[]	= sprintf(txt('75'), $aux_tmp);
	if(isset($_SESSION['paten'])) unset($_SESSION['paten']); // inefficient, but maybe we come up with something more elegant

	return true;
    }

    /*
     * active <-> inactive
     */
    function mailbox_toggle_active($mboxnames) {
	global $cfg;

	$tobechanged = $this->mailbox_filter_manipulable($this->current_user['mbox'], $mboxnames);

	if(count($tobechanged) > 0) {
	    mysql_query('UPDATE '.$cfg['tablenames']['user']
			    .' SET active = NOT active'
			    .' WHERE FIND_IN_SET(mbox, "'.implode(',', $tobechanged).'")'
			    .' LIMIT '.count($tobechanged));

	    if(mysql_affected_rows() < 1) {
		if(mysql_errno() != 0) {
		    $this->error[]	= mysql_error();
		}
	    }
	    else {
		return true;
	    }
	}

	return false;
    }

    /*
     * Here are all the checks whether an given field carries a valid value.
     * $data		typically $_POST
     * which		array with the fields' names
     * This function will apply mysql_real_escape_string on every valid non-numerical item listed in $which.
     */
    function validate_input(&$data, $which) {
	global $cfg;
	// Fieldname as key, cap as its caption and def as its default value.
	$inputs['mbox']		= array('cap'	=> txt('83'),
				);
	$inputs['pate']		= array('cap'	=> txt('9'),
				'def'	=> $this->current_user['mbox'],
				);
	$inputs['person']	= array('cap'	=> txt('84'),
				);
	$inputs['domains']	= array('cap'	=> txt('86'),
				'def'	=> $this->current_user['domains'],
				);
	$inputs['canonical']	= array('cap'	=> txt('7'),
				);
	$inputs['quota']	= array('cap'	=> txt('87'),
				);
	$inputs['max_alias']	= array('cap'	=> txt('88'),
				);
	$inputs['max_regexp']	= array('cap'	=> txt('89'),
				'def'	=> 0,
				);
	$inputs['reg_exp']	= array('cap'	=> txt('34'),
				'def'	=> '',
				);
	$inputs['a_super']	= array('cap'	=> txt('68'),
				'def'	=> 0,
				);
	$inputs['a_admin_domains']	= array('cap'	=> txt('50'),
				'def'	=> 0,
				);
	$inputs['a_admin_user']	= array('cap'	=> txt('70'),
				'def'	=> 0,
				);
	// domains
	$inputs['domain']	= array('cap'	=> txt('55'),
				);
	$inputs['owner']	= array('cap'	=> txt('56'),
				'def'	=> $this->current_user['mbox'],
				);
	$inputs['a_admin']	= array('cap'	=> txt('57'),
				'def'	=> implode(',', array_unique(array($this->current_user['mbox'], $this->authenticated_user['mbox']))),
				);
	$inputs['categories']	= array('cap'	=> txt('58'),
				);

	// Hash with tests vor sanity and possible error-messages on failure.
	// These will only be processed if a value is given. (I.e. not on the default values from above)
	// If a test fails the next won't be invoked.
	$validate['mbox']	= array(array(	'val'	=> 'strlen(~) >= $cfg[\'mbox\'][\'min_length\'] && strlen(~) <= $cfg[\'mbox\'][\'max_length\'] && preg_match(\'/^[a-zA-Z0-9]*$/\', ~)',
						'error'	=> sprintf(txt('62'), $cfg['mbox']['min_length'], $cfg['mbox']['max_length']) ),
					);
	$validate['pate']	= array(array(	'val'	=> '$this->authenticated_user[\'a_super\'] > 0 || $this->user_is_descendant(~, $this->authenticated_user[\'mbox\'])',
						),
					);
	$validate['person']	= array(array(	'val'	=> 'strlen(~) <= 100 && strlen(~) >= 4 && preg_match(\'/^[\w\s0-9-_\.\(\)]*$/\', ~)',
						),
					);
	$validate['domains']	= array(array(	'val'	=> '(~ = trim(~)) && preg_match(\'/^((?:[\w]+|[\w]+\.[\w]+),\s*)*([\w]+|[\w]+\.[\w]+)$/i\', ~)',
						),
					array(	'val'	=> '$this->domain_check($this->current_user, $this->current_user[\'mbox\'], ~)',
						'error'	=> txt('81')),
					);
	$validate['canonical']	= array(array(	'val'	=> 'preg_match(\'/\'.$this->regex_valid_email.\'/i\', ~)',
						'error'	=> txt('64')),
					);
	$validate['quota']	= array(array(	'val'	=> 'is_numeric(~) && settype(~, \'int\') && ~ >= 0',
						'error'	=> txt('63')),
					);
	$validate['max_alias']	= array(array(	'val'	=> 'is_numeric(~) && settype(~, \'int\') && ~ >= 0',
						'error'	=> txt('63')),
					);
	$validate['max_regexp']	= array(array(	'val'	=> 'is_numeric(~) && settype(~, \'int\') && ~ >= 0',
						'error'	=> txt('63')),
					);
	$validate['a_super']	= array(array(	'val'	=> 'is_numeric(~) && settype(~, \'int\') && ~ < 3 && ~ >= 0',
						),
					array(	'val'	=> '~ == 0 || $this->authenticated_user[\'#\'] >= 2 || $this->authenticated_user[\'a_super\'] > ~ || $this->authenticated_user[\'#\'] > ~',
						'error'	=> txt('16')),
					);
	$validate['a_admin_domains']	= $validate['a_super'];
	$validate['a_admin_user']	= $validate['a_super'];
	// domains
	$validate['domain']	= array(array(	'val'	=> 'preg_match(\'/^\'.$this->regex_valid_domain.\'$/i\', ~)',
						'error'	=> txt('51')),
					);
	$validate['owner']	= array(array(	'val'	=> 'strlen(~) >= $cfg[\'mbox\'][\'min_length\'] && strlen(~) <= $cfg[\'mbox\'][\'max_length\'] && preg_match(\'/^[a-zA-Z0-9]*$/\', ~)',
						),
					);
	$validate['a_admin']	= array(array(	'val'	=> 'preg_match(\'/^([a-z0-9]+,\s*)*[a-z0-9]+$/i\', ~)',
						),
					);
	$validate['categories']	= array(array(	'val'	=> '(~ = trim(~)) && preg_match(\'/^((?:[\w]+|[\w]+\.[\w]+),\s*)*([\w]+|[\w]+\.[\w]+)$/i\', ~)',
						),
					);

	// Check field per field.
	$error_occured	= false;
	$invalid	= array();
	$missing	= array();
	foreach($which as $fieldname) {
	    // Do we have to care about that field?
	    if(isset($inputs[$fieldname])) {
		// Did the user provide it?
		if(isset($data[$fieldname]) && $data[$fieldname] != '') {
		    // If so and if we have a rule to check for validity, we have to validate this field.
		    if(isset($validate[$fieldname])) {
			foreach($validate[$fieldname] as $test) {
			    if(!eval('return ('.str_replace(array('~', '#'), array('$data[\''.$fieldname.'\']', $fieldname), $test['val']).');')) {
				// The given value is invalid.
				$error_occured = true;
				if(isset($test['error'])) {
				    $this->error[]	= $test['error'];
				}
				else {
				    $invalid[] = $inputs[$fieldname]['cap'];
				}
				break;
			    }
			}
		    }
		    $data[$fieldname] = mysql_real_escape_string($data[$fieldname]);
		}
		else {
		    // Assign it a valid value, if possible.
		    if(isset($inputs[$fieldname]['def'])) {
			$data[$fieldname]	= $inputs[$fieldname]['def'];
		    }
		    else {
			// No value was given and we cannot assign it a default value.
			$error_occured = true;
			$missing[] = $inputs[$fieldname]['cap'];
		    }
		}
	    }
	}

	// Now we can set error-messages.
	if($error_occured) {
	    if(count($invalid) > 0) {
		$this->error[]	= sprintf(txt('130'), implode(', ', $invalid));
	    }
	    if(count($missing) > 0) {
		$this->error[]	= sprintf(txt('129'), implode(', ', $missing));
	    }
	}

	return(!$error_occured);
    }

}
?>