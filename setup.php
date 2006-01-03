<?php
if(is_file('./inc/config.local.inc.php')) {
	header('HTTP/1.1 303 See Other');
	header('Location: index.php');
	die('Setup has already been run.');
}

ob_start('ob_gzhandler');

include('./inc/config.inc.php');
include('./inc/functions.inc.php');
@include('adodb/adodb.inc.php');

include('./templates/setup/header.tpl');
switch($_GET['step']) {
	case '2':
		$available_db	= array();
		if(function_exists('mysql_connect'))	$available_db[] = array('mysql', 'mysql://user:pwd@host/mydb');
		if(function_exists('mysqli_connect'))	$available_db[] = array('mysqli', 'mysqli://user:pwd@host/mydb');
		if(function_exists('sqlite_open'))	$available_db[] = array('sqlite', 'sqlite://..%2Fmydb.db');
		if(function_exists('pg_connect'))	$available_db[] = array('postgres', 'postgres://user:pwd@host/mydb');
		include('./templates/setup/step2.tpl');
		break;
	default:
	case '1':
		$expectations
		= array('asp_tags'			=> 0,
			'short_open_tag'		=> 0,
			'display_errors'		=> 0,
			'log_errors'			=> 1,
			'file_uploads'			=> 0,
			'ignore_repeated_errors'	=> 1,
			'ignore_repeated_source'	=> 1,
			'safe_mode'			=> 1,
			);

		$requirements
		= array('magic_quotes_gpc'		=> 0,
			'magic_quotes_runtime'		=> 0,
			'register_globals'		=> 0,
			);

		$checks
		= array(
				array('PHP version greater than 4.3.0?', eval('return version_compare(PHP_VERSION, "4.3.0", ">");')),
				array('<cite>file_get_contents</cite> exists?', eval('return function_exists("file_get_contents");')),
				array('Socket or IMAP support available?', eval('return function_exists("fsockopen") || function_exists("imap_open");')),
				array('MySQL or MySQLi, SQLite, PostgreSQL?', eval('return function_exists("mysql_connect") || function_exists("mysqli_connect") || function_exists("sqlite_open") || function_exists("pg_connect");')),
				array('Is ADOdb installed?', eval('return function_exists("ADONewConnection");')),
			);

		$reality	= array();
		foreach($expectations as $value=>$expected) {
			$reality[]
				= array(
				'value'		=> $value,
				'expected'	=> $expected ? true : false,
				'is'		=> ini_get($value) ? true : false,
				'okay'		=> (ini_get($value) == $expected),
				'mandatory'	=> false,
				);
		}
		foreach($requirements as $value=>$expected) {
			$reality[]
				= array(
				'value'		=> $value,
				'expected'	=> $expected ? true : false,
				'is'		=> ini_get($value) ? true : false,
				'okay'		=> (ini_get($value) == $expected),
				'mandatory'	=> true,
				);		}
		include('./templates/setup/step1.tpl');
		break;
}
include('./templates/setup/footer.tpl');

?>