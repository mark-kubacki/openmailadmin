<?php
if($cfg['show_exceptions_online']) {
	set_exception_handler('PrettyBlueScreen');
} else {
	set_exception_handler('PrimitiveBlueScreen');
}

function PrimitiveBlueScreen($e) {
	ob_end_clean();
	@ob_start('ob_gzhandler');
	global $lang, $cfg;
	$text = $e->getMessage();
	$width = 580;
	include('./templates/shadow/common-header.tpl');
	include('./templates/shadow/error_box.tpl');
	include('./templates/shadow/common-footer_nv.tpl');
	ob_end_flush();
	trigger_error($e->__toString(), E_USER_ERROR);
}

/**
 * @source	http://www.sitepoint.com/blogs/2006/04/04/pretty-blue-screen/
 * @author	Harry Fuecks; http://www.sitepoint.com/articlelist/210
 */
function PrettyBlueScreen($e) {
	ob_end_clean();
	@ob_start('ob_gzhandler');
	$o = create_function('$in', 'echo htmlspecialchars($in);');
	$sub = create_function('$f', '$loc="";if(isset($f["class"])){
		$loc.=$f["class"].$f["type"];}
		if(isset($f["function"])){$loc.=$f["function"];}
		if(!empty($loc)){$loc=htmlspecialchars($loc);
		$loc="<strong>$loc</strong>";}return $loc;');
	$parms = create_function('$f', '$params=array();if(isset($f["function"])){
		try{if(isset($f["class"])){
		$r=new ReflectionMethod($f["class"]."::".$f["function"]);}
		else{$r=new ReflectionFunction($f["function"]);}
		return $r->getParameters();}catch(Exception $e){}}
		return $params;');
	$src2lines = create_function('$file', '$src=nl2br(highlight_file($file,TRUE));
		return explode("<br />",$src);');
	$clean = create_function('$line', 'return trim(strip_tags($line));');
	$desc = get_class($e)." making ".$_SERVER['REQUEST_METHOD']." request to ".$_SERVER['REQUEST_URI'];
	include('./templates/ExceptionHandler.tpl');
	ob_end_flush();
	trigger_error($e->__toString(), E_USER_ERROR);
}
