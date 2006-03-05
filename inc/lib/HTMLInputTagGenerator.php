<?php
/**
 * Regard this as huge factory for generating strings of HTML "input" tags.
 */
class HTMLInputTagGenerator
{
	var		$images_dir;			// storage of images (for most derived classes need it)
	var		$arrClass	= array();	// CSS Class of tag
	var		$arrProperties	= array();	// tag's properties

	// constructor
	function HTMLInputTagGenerator() {
		$this->images_dir	= '';
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

	/** For synthesizing generic tags. */
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
			} else {
				$ret	.= ' />';
			}
		}
		if($part != 's' && $container) {
			$ret	.= '</'.$tag.'>';
		}
		return $ret;
	}

	function _generate($type, $name, $value, $arrProperties) {
		$arrProperties['type']	= $type;
		$arrProperties['name']	= $name;
		if($type == 'checkbox' || $type == 'radio') {
			if(isset($_POST[$name]) && $_POST[$name] == $value)
				$arrProperties['checked'] = '1';
			$arrProperties['value'] = $value;
		} else {
			if(is_null($value) && $type != 'password' && isset($_POST[$name]))
				$arrProperties['value']	= $_POST[$name];
			else
				$arrProperties['value']	= $value;
		}
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
		return($this->_generic('textarea', array('rows' => $rows, 'cols' => $cols, 'name' => $name), isset($_POST[$name]) ? $_POST[$name] : '', true));
	}

	function select($name, $arr_names, $arr_values = array(), $size = '1', $multiple = 0) {
		$select_value = '';
		foreach($arr_names as $key => $value) {
			$select_value .= '<option';
			if(isset($arr_values[$key])) {
				$select_value .= ' value="'.$arr_values[$key].'"';
				if(isset($_POST[$name]) && $_POST[$name] == $arr_values[$key])
					$select_value .= ' selected="selected"';
			} else {
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

}
?>