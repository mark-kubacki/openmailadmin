<?php
// Returns corresponding text:
function txt($id) {
    global $lang, $res, $default_lang;
    if(isset($res[$lang][$id]))
	return $res[$lang][$id];
    else if(isset($res[$default_lang][$id]))
	return $res[$default_lang][$id];
    else
	return '#'.$id;
}

// this will determine which language to use
if(! $cfg['force_default_language']) {
    $tmp_av = explode(',', str_replace(';', ',', $_SERVER['HTTP_ACCEPT_LANGUAGE']));
    if(is_array($tmp_av))
    for($i = 0; $i < count($tmp_av); $i++) {
	if(is_file('inc/lang/'.$tmp_av[$i].'.inc.php')) {
	    $lang = $tmp_av[$i];
	    break;
	}
    }
}

include_once('inc/lang/'.$lang.'.inc.php');
include_once('inc/lang/'.$default_lang.'.inc.php');
// include_once('inc/lang/de.inc.php');

if(txt('encoding') == '#encoding')
    $encoding = 'ISO-8859-1';
else
    $encoding = txt('encoding');

?>