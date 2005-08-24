<?php
/*
 * Transforms a string like 'INBOX.Archiv.Drafts' to an array['INBOX']['Archiv']['Drafts']
 * and adds ['^'] = string to the top.
 * (^ is a magic token in IMAP-Servers which cannot be part of a mailbox' name.)
 */
function array_stepper($delimiter, $string) {
    $hy['^'] = $string;
    foreach(array_reverse(explode($delimiter, $string)) as $key=>$value) {
	$hy = array($value => $hy);
    }

    return $hy;
}

/* display_tree is specialized for /folders.php4 */
function display_tree($tree) {
    echo('<ul class="tree">');
    foreach($tree as $key=>$value) {
	$of_interest	= isset($_GET['folder']) && stristr($_GET['folder'], $key);
	$this_new	= isset($value['^']) && isset($GLOBALS['to_be_created']) && $value['^'] == $GLOBALS['to_be_created'];
	$has_subfolders	= !(isset($value['^']) && count($value) == 1);

	// Does this folder contain subfolders?
        if($has_subfolders) {
	    echo('<li class="container">');
	}
	else {
	    echo('<li class="leaf">');
	}

	// Is the node selectable
	if(isset($value['^'])) {
	    // Is this one new? Does it lead to the selected one?
            if($this_new) {
		echo('<span class="new_mbox">');
	    }
	    else if($of_interest) {
		echo('<span class="act_mbox">');
	    }
	    else {
		echo('<span class="ina_mbox">');
	    }
	    echo('<a href="'.mkSelfRef(array('folder' => $value['^'])).'">'.$key.'</a></span>');
	    unset($value['^']);
	}
	else { // ... or just a step?
	    if($of_interest) {
		echo('<span class="act_mbox">');
	    }
	    else {
		echo('<span class="ina_mbox">');
	    }
	    echo($key.'</span>');
	}

	// If it contains subfolders, display them.
        if($has_subfolders && count($value) > 0) {
	    display_tree($value);
	}

	// Finally, the ending tag.
	echo('</li>');
    }
    echo('</ul>');
}

?>