<?php
//
//	Except for the _T_tag abstract class every class has to contain:
//	Uber-Classes:
//		SpecialTag	e.g. td($content)	directly a tag or subtype (input: checkbox)
//	Solitaire-Classes:
//		_([specific])				generates and returns the tag for displaying
//		display([specific], $arrProperties)	same as above plus one argument (may differ!!!)
//
class _T_tag {
    var		$images_dir;			// storage of images (for most derived classes need it)
    var		$arrClass;			// CSS Class of tag
    var		$arrProperties;			// tag's properties

    // constructor
    function _T_tag() {
	$this->images_dir	= '';
	$this->arrClass		= array();
	$this->arrProperties	= array();
    }

    // synthesize the tag
    function _generic($tag, $arrProperties, $strContent = '', $container = true, $part = 'a') {
	$ret = '';
	if($part != 'e') {
	    $arrProperties	= array_merge(isset($this->arrProperties[$tag]) ? $this->arrProperties[$tag] : $this->arrProperties, $arrProperties);
	    $ret	= '<'.$tag;
		foreach($arrProperties as $key => $value)
		    $ret	.= ' '.strtolower($key).'="'.$value.'"';
		unset($arrProperties);
	    if($container) {
		$ret	.= '>';
		$ret	.= $strContent;
	    }
	    else {
		$ret	.= ' />';
	    }
	}
	if($part != 's' && $container) {
		$ret	.= '</'.$tag.'>';
	}
	return $ret;
    }
};

class _input extends _T_tag {
    function _input() {
	$this->arrClass['text']		= 'text';
	$this->arrClass['password']	= 'password';
	$this->arrClass['image']	= 'image';
	$this->arrClass['submit']	= 'submit';
	$this->arrClass['checkbox']	= 'checkbox';
	$this->arrClass['textarea']	= 'textarea';
	$this->arrClass['select']	= 'select';
	$this->arrProperties['image']	= array('border' 	=> '0',
						'alt'		=> ''
						);
	$this->arrProperties['input']	= array();
	$this->arrProperties['textarea']	= array();
	$this->arrProperties['select']	= array();
    }

    function _generate($type, $name, $value, $arrProperties) {
	global $_POST;

	$arrProperties['type']	= $type;
	$arrProperties['name']	= $name;
	if($type == 'checkbox' || $type == 'radio') {
	    if(isset($_POST[$name]) && $_POST[$name] == $value)
		$arrProperties['checked'] = '1';
	    $arrProperties['value'] = $value;
	}
	else
	    if(is_null($value) && $type != 'password' && isset($_POST[$name]))
		$arrProperties['value']	= $_POST[$name];
	    else
		$arrProperties['value']	= $value;
	if(!isset($arrProperties['class']) && isset($this->arrClass[$type]))
	    $arrProperties['class']	= $this->arrClass[$type];

	if(isset($this->arrProperties[$type]))
	    $arrProperties	= array_merge($this->arrProperties[$type], $arrProperties);
	return($this->_generic('input', $arrProperties, '', false));
    }

    function checkbox($name, $value = null, $prop = array()) {
	return($this->_generate('checkbox', $name, $value, $prop));
    }

    function radio($name, $value, $prop = array()) {
	return($this->_generate('radio', $name, $value, $prop));
    }

    function submit($name) {
	return($this->_generate('submit', $name, $name, array()));
    }

    function text($name, $maxlength = '') {
	if($maxlength != '' && is_numeric($maxlength))
	    return($this->_generate('text', $name, null, array('maxlength' => $maxlength)));
	else
	    return($this->_generate('text', $name, null, array()));
    }

    function textarea($name, $rows = 2, $cols=49) {
	global $_POST;
	return($this->_generic('textarea', array('rows' => $rows, 'cols' => $cols, 'name' => $name), isset($_POST[$name]) ? $_POST[$name] : '', true));
    }

    function select($name, $arr_names, $arr_values = array(), $size = '1', $multiple = 0) {
	global $_POST; $select_value = '';
	foreach($arr_names as $key => $value) {
	    $select_value .= '<option';
	    if(isset($arr_values[$key])) {
		$select_value .= ' value="'.$arr_values[$key].'"';
		if(isset($_POST[$name]) && $_POST[$name] == $arr_values[$key])
		    $select_value .= ' selected="selected"';
	    }
	    else {
		if(isset($_POST[$name]) && $_POST[$name] == $value)
		    $select_value .= ' selected="selected"';
	    }
	    $select_value .= ">$value</option>";
	}
	return($this->_generic('select', array('size' => $size, 'name' => $name), $select_value, true));
    }

    function password($name, $maxlength = '') {
	if($maxlength != '' && is_numeric($maxlength))
	    return($this->_generate('password', $name, null, array('maxlength' => $maxlength)));
	else
	    return($this->_generate('password', $name, null, array()));
    }

    function hidden($name, $value) {
	return($this->_generate('hidden', $name, $value, array()));
    }
};

class _font extends _T_tag {
    function display($strContent, $arrProperties = array()) {
	return($this->_generic('font', $arrProperties, $strContent));
    }

    function _($strContent) {
	return($this->display($strContent));
    }
};

class _table extends _T_tag {
    function _table()
    {
	$this->arrProperties['td']	=	array();
	$this->arrProperties['tr']	=	array();
	$this->arrProperties['table']	=	array(	'border'	=> '0',
							'cellpadding'	=> '1',
							'cellspacing'	=> '1');
    }

    function table($strContent, $part = 'a', $arrProperties = array()) {
	return($this->_generic('table', $arrProperties, $strContent, true, $part));
    }

    function tr($strContent, $part = 'a', $arrProperties = array()) {
	return($this->_generic('tr', $arrProperties, $strContent, true, $part));
    }

    function td($strContent, $part = 'a', $arrProperties = array()) {
	return($this->_generic('td', $arrProperties, $strContent, true, $part));
    }

};

class _table_shadow extends _table {
    var		$old_netscape;

    function _table_shadow()
    {
	$this->old_netscape		=	false;
	$this->arrClass['caption']	=	'caption';
	$this->arrClass['caption_dea']	=	'caption_dea';
	$this->arrProperties['td']	=	array(	'class'		=> 'std');
	$this->arrProperties['tr']	=	array();
	$this->arrProperties['table']	=	array(	'border'	=> '0',
							'cellpadding'	=> '1',
							'cellspacing'	=> '1');
    }

    function echo_caption($strCaption, $right = null, $width = null, $active=true) {
	echo('<table border="0" cellpadding="0" cellspacing="0"');
	    if(!is_null($width))
		echo(' width="'.$width.'"');
	    echo('><tr><td>');
	echo('<table border="0" cellpadding="2" cellspacing="0"><tr><td class="'.($active ? $this->arrClass['caption'] : $this->arrClass['caption_dea']).'">&nbsp;');
	echo($strCaption);
	echo('&nbsp;</td></tr></table>');
	echo('</td><td valign="top" class="sh_hor" style="width: 6px"><img border="0" src="'.$this->images_dir.'/sh_lu.gif" width="6" height="6" alt="\" /></td>');
	    if(!is_null($right) && $right != '')
		echo('<td align="right" width="95%" class="ed">'.$right.'</td>');	// will be tight enough
	    echo('</tr></table>');
	return true;
    }

    function outer_shadow_start() {
	return('<table border="0" cellpadding="0" cellspacing="0"><tr><td>');
    }

    function outer_shadow_stop() {
	$ret	= '</td><td valign="top" class="sh_hor"><img border="0" src="'.$this->images_dir.'/sh_lu.gif" width="6" height="6" alt="\" /></td></tr>';
	$ret	.= '<tr><td class="sh_ver"><img border="0" src="'.$this->images_dir.'/sh_ro.gif" width="6" height="6" alt="+" /></td><td align="right" class="sh_ver"><img border="0" src="'.$this->images_dir.'/sh_lo.gif" width="6" height="6" alt="\" /></td></tr></table><br />';
	return $ret;
    }
};

function B($txt) {
    return('<b>'.$txt.'</b>');
}

// add a attribute to the given element
function addProp($element, $prop = array()) {
    if(count($prop) < 1)
	return $element;
    else {
	$ret = '';
	foreach($prop as $key => $value)
	    $ret	.= ' '.strtolower($key).'="'.$value.'"';
	return preg_replace('/(<[a-z]+)\s(.*)/', '$1'.$ret.' $2', $element, 1);
    }
}

/*
 * displays a nice error
 */
function error($text, $width=580) {
    global $cfg;
    include('templates/'.$cfg['theme'].'/error_box.tpl');
}
/*
 * displays a nice info-box
 */
function info($text, $width=580) {
    global $cfg;
    include('templates/'.$cfg['theme'].'/info_box.tpl');
}
/*
 * displays a nice caption
 */
function caption($text, $right=null, $width=null) {
    global $cfg;
    include('templates/'.$cfg['theme'].'/caption.tpl');
}

/*
 *
 */
function table_array_matrix($array) {
    $output = '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
    foreach($array as $key=>$value) {
	$output .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
    }
    $output .= '</table>';
    return $output;
}

?>