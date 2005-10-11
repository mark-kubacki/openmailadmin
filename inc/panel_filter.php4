<?php
// ------------------------------ Filter ----------------------------------------------------------
// set the limiting-suffix for SQL
if(!isset($_SESSION['limit']['upper'])) {
    $_SESSION['limit']['upper'] = $cfg['max_elements_per_page'];
}
if(!isset($_SESSION['limit'][$oma->current_user['mbox']]) || isset($_POST['limit'])) {
    $_SESSION['limit'][$oma->current_user['mbox']] = array('address' => 0, 'regexp' => 0, 'domain' => 0, 'mbox' => 0,
						'addr_page' => 0, 'regx_page' => 0, 'dom_page' => 0, 'mbox_page' => 0);
}
if(isset($_POST['limit'])) {
    if(is_numeric($_POST['limit'])) {
	$_SESSION['limit']['upper']	= intval($_POST['limit']);
    }
    else
	$_SESSION['limit']['upper']	= false; // equals 'no limit'
}
if(isset($_GET['addr_page']) && is_numeric($_GET['addr_page'])) {
    $_SESSION['limit'][$oma->current_user['mbox']]['address'] = max(0, (intval($_GET['addr_page']) - 1) * $_SESSION['limit']['upper']);
    $_SESSION['limit'][$oma->current_user['mbox']]['addr_page'] = intval($_GET['addr_page']);
    unset($_GET['addr_page']);
}
if(isset($_GET['regx_page']) && is_numeric($_GET['regx_page'])) {
    $_SESSION['limit'][$oma->current_user['mbox']]['regexp'] = max(0, (intval($_GET['regx_page']) - 1) * $_SESSION['limit']['upper']);
    $_SESSION['limit'][$oma->current_user['mbox']]['regx_page'] = intval($_GET['regx_page']);
    unset($_GET['regx_page']);
}
if(isset($_GET['dom_page']) && is_numeric($_GET['dom_page'])) {
    $_SESSION['limit'][$oma->current_user['mbox']]['domain'] = max(0, (intval($_GET['dom_page']) - 1) * $_SESSION['limit']['upper']);
    $_SESSION['limit'][$oma->current_user['mbox']]['dom_page'] = intval($_GET['dom_page']);
    unset($_GET['dom_page']);
}
if(isset($_GET['mbox_page']) && is_numeric($_GET['mbox_page'])) {
    $_SESSION['limit'][$oma->current_user['mbox']]['mbox'] = max(0, (intval($_GET['mbox_page']) - 1) * $_SESSION['limit']['upper']);
    $_SESSION['limit'][$oma->current_user['mbox']]['mbox_page'] = intval($_GET['mbox_page']);
    unset($_GET['mbox_page']);
}
if($_SESSION['limit']['upper']) {
    $_SESSION['limit']['str']['address']= ' LIMIT '.$_SESSION['limit'][$oma->current_user['mbox']]['address'].','.$_SESSION['limit']['upper'];
    $_SESSION['limit']['str']['regexp']	= ' LIMIT '.$_SESSION['limit'][$oma->current_user['mbox']]['regexp'].','.$_SESSION['limit']['upper'];
    $_SESSION['limit']['str']['domain']	= ' LIMIT '.$_SESSION['limit'][$oma->current_user['mbox']]['domain'].','.$_SESSION['limit']['upper'];
    $_SESSION['limit']['str']['mbox']	= ' LIMIT '.$_SESSION['limit'][$oma->current_user['mbox']]['mbox'].','.$_SESSION['limit']['upper'];
}
else {
    $_SESSION['limit']['str'] = array('address' => '', 'regexp' => '', 'domain' => '', 'mbox' => '');
}
if(isset($_SESSION['limit']['upper']) && $_SESSION['limit']['upper']) {
    $_POST['limit'] = $_SESSION['limit']['upper'];
}
// now on creating additional WHERE
if(isset($_POST['filtr']) && !isset($_POST['filtr_addr'])) {
    $_SESSION['filter']['active'] = false;
    $_SESSION['filter']['str'] = array('address' => '', 'regexp' => '', 'domain' => '', 'mbox' => '');
}
else if((isset($_SESSION['filter']['active']) && $_SESSION['filter']['active']) || (isset($_POST['filtr_addr']) && $_POST['filtr_addr'] == 1)) {
    $_SESSION['filter']['active'] = true; $_POST['filtr_addr'] = 1;
}
if(isset($_POST['filtr']) && isset($_POST['filtr_addr']) && $_POST['filtr'] == 'set' && trim($_POST['cont']) != '') {
    $filtr_post = '';
    $_SESSION['filter']['str'] = array('address' => '', 'regexp' => '', 'domain' => '', 'mbox' => '');
    switch($_POST['cond']) {
	case 'has':	$filtr_post = '\'%'.str_replace(txt('5'), $oma->current_user['mbox'], mysql_escape_string($_POST['cont'])).'%\'';	break;
	case 'begins':	$filtr_post = '\''.str_replace(txt('5'), $oma->current_user['mbox'], mysql_escape_string($_POST['cont'])).'%\'';		break;
	case 'ends':	$filtr_post = '\'%'.str_replace(txt('5'), $oma->current_user['mbox'], mysql_escape_string($_POST['cont'])).'\'';		break;
    }
    switch($_POST['what']) {
	case 'addr':
	    $_SESSION['filter']['str']['address'] = ' AND address LIKE '.$filtr_post;
	    break;
	case 'target':
	    $_SESSION['filter']['str']['address'] = ' AND dest LIKE '.$filtr_post;
	    $_SESSION['filter']['str']['regexp'] = ' AND dest LIKE '.$filtr_post;
	    break;
	case 'domain':
	    $_SESSION['filter']['str']['address'] = ' AND SUBSTRING_INDEX(address, \'@\', -1) LIKE '.$filtr_post;
	    $_SESSION['filter']['str']['domain'] = ' AND domain LIKE '.$filtr_post;
	    break;
	case 'mbox':
	    $_SESSION['filter']['str']['mbox'] = ' AND mbox LIKE '.$filtr_post;
	    $_SESSION['filter']['str']['domain'] = ' AND owner LIKE '.$filtr_post;
	    break;
    }

    $_SESSION['filter']['active'] = true;
    $_SESSION['filter']['what'] = $_POST['what'];
    $_SESSION['filter']['cond'] = $_POST['cond'];
    $_SESSION['filter']['cont'] = $_POST['cont'];
}
if(isset($_SESSION['filter']['active'])) {
    if($_SESSION['filter']['active']) $_POST['filtr_addr'] = 1;
    $_POST['what'] = isset($_SESSION['filter']['what']) ? $_SESSION['filter']['what'] : '';
    $_POST['cond'] = isset($_SESSION['filter']['cond']) ? $_SESSION['filter']['cond'] : '';
    $_POST['cont'] = isset($_SESSION['filter']['cont']) ? $_SESSION['filter']['cont'] : '';
}
if(!isset($_SESSION['filter'])) {
    $_SESSION['filter']['str'] = array('address' => '', 'regexp' => '', 'domain' => '', 'mbox' => '');
}
// DISPLAY
include('templates/'.$cfg['theme'].'/filter_panel.tpl');


?>