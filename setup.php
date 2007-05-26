<?php
if(is_file('./inc/config.local.inc.php')) {
	die('A configuration file does already exist. Please proceed to <a href="index.php">login screen</a>.');
}

ob_start('ob_gzhandler');

include('./inc/config.inc.php');
include('./inc/functions.inc.php');
@include('adodb/adodb.inc.php');
@include('Log.php');

// definition of configuration file's format
$config = <<<EOT
<?php
/* Created by setup.php (%s) on %s */
\$cfg['user_ignore']		= array('%s');
\$cfg['passwd']['strategy']	= '%s';

// repeat these lines for every server or virtual domain
\$cfg['Servers']['verbose'][] = '%s';
\$cfg['Servers']['number'][] = \$i++;
\$cfg['Servers']['DB'][] = array(
	'DSN'		=> '%s',
	'PREFIX'	=> '%s',
);
\$cfg['Servers']['IMAP'][] = array(
	'TYPE'	=> '%s',
	'HOST'	=> '%s',
	'PORT'	=> %d,
	'ADMIN'	=> '%s',
	'PASS'	=> '%s',
	'VDOM'	=> ''
);
?>
EOT;

// now comes processing
include('./templates/setup/header.tpl');
switch($_GET['step']) {
	case '3':
		$db	= false;
		if($_POST['dsn'] != '') {
			$db	= @ADONewConnection($_POST['dsn']);
		}
		if(!$db) {
			$failure	= 'Cannot connect to DB. Please correct your DSN';
		} else {
			$cfg['passwd']['strategy']	= $_POST['hashing_strategy'];
			$tables
			= array('user'		=>	'user.adodb.txt',
				'domains'	=>	'domains.adodb.txt',
				'virtual'	=>	'virtual.adodb.txt',
				'virtual_regexp'=>	'virtual_regexp.adodb.txt',
				'imap_demo'	=>	'imap_demo.adodb.txt',
				);
			// create tables
			$status = array();
			foreach($tables as $name=>$tablefile) {
				$definition	= file_get_contents('./inc/database/'.$tablefile);
				$dict		= NewDataDictionary($db);
				$sqlarray	= $dict->CreateTableSQL($_POST['prefix'].$name, $definition);
				$status[$name]	= array($_POST['prefix'].$name,
							$dict->ExecuteSQLArray($sqlarray),
							);
				$cfg['tablenames'][$name] = $_POST['prefix'].$name;
			}
			// add sample data - only if table has been created and did not exist
			if($status['imap_demo'][1] == 2) {
				$db->Execute('INSERT INTO '.$_POST['prefix'].'imap_demo (mailbox,used,qmax,ACL) VALUES (?,?,?,?)',
							array('shared', 0, 0, 'anyone lrswipcda'));
				if($_POST['imap_type'] == 'fake-imap') {
					$db->Execute('INSERT INTO '.$_POST['prefix'].'imap_demo (mailbox,used,qmax,ACL) VALUES (?,?,?,?)',
								array('user.'.$_POST['admin_user'], 0, 0, $_POST['admin_user'].' lrswipcda'));
				}
			}
			if($status['user'][1] == 2 or $status['user'][1] == 1) {
				if($_POST['imap_user'] == '') {
					$_POST['imap_user'] = '---';
				}
				$db->Execute('INSERT INTO '.$_POST['prefix'].'user VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
					array(	array($_POST['admin_user'], 'Admin John Doe', $_POST['admin_user'], $_POST['admin_user'].'@example.com', '', 'all', 1, time(), 0, 10000, 100, 2, 2, 2),
						array($_POST['imap_user'], $_POST['imap_user'], $_POST['imap_user'], '--@example.com', '', 'none', 1, time(), 0, 0, 0, 0, 0, 1),
						));
				User::$db = $db;
				User::$tablenames = $cfg['tablenames'];
				$tmp = new User($_POST['admin_user']);
				$tmp->password->set($_POST['admin_pass']);
				$tmp = new User($_POST['imap_user']);
				$tmp->password->set($_POST['imap_pass']);
				// create superuser
				if($_POST['imap_type'] != 'fake-imap') {
					$imap = IMAP_get_instance(array('HOST' => $_POST['imap_host'],
									'PORT' => $_POST['imap_port'],
									'ADMIN' => $_POST['imap_user'],
									'PASS' => $_POST['imap_pass'],
									), $_POST['imap_type']);
					$imap->createmb($imap->format_user($_POST['admin_user']));
					if(isset($cfg['folders']['create_default']) && is_array($cfg['folders']['create_default'])) {
						foreach($cfg['folders']['create_default'] as $new_folder) {
							$imap->createmb($imap->format_user($_POST['admin_user'], $new_folder));
						}
					}
				}
			}
			if($status['domains'][1] == 2) {
				$dict->ExecuteSQLArray($dict->CreateIndexSQL('domain', $_POST['prefix'].'domains', 'domain', array('UNIQUE')));
				$dict->ExecuteSQLArray($dict->CreateIndexSQL('owner', $_POST['prefix'].'domains', 'owner'));
				$db->Execute('INSERT INTO '.$_POST['prefix'].'domains (ID,domain,categories,owner,a_admin) VALUES (?,?,?,?,?)',
						array(1, 'example.com', 'all, samples', $_POST['admin_user'], $_POST['admin_user']));
			}
			if($status['virtual'][1] == 2) {
				$dict->ExecuteSQLArray($dict->CreateIndexSQL('owner', $_POST['prefix'].'virtual', 'owner'));
				$db->Execute('INSERT INTO '.$_POST['prefix'].'virtual (address,dest,owner,active,neu) VALUES (?,?,?,?,?)',
						array('me@example.com', $_POST['admin_user'], $_POST['admin_user'], 1, 1));
			}
			if($status['virtual_regexp'][1] == 2) {
				$dict->ExecuteSQLArray($dict->CreateIndexSQL('owner', $_POST['prefix'].'virtual_regexp', 'owner'));
				$db->Execute('INSERT INTO '.$_POST['prefix'].'virtual_regexp (ID,reg_exp,dest,owner,active,neu) VALUES (?,?,?,?,?,?)',
						array(11, '/^(postmaster|abuse|security|root)@example\\.com$/', $_POST['admin_user'], $_POST['admin_user'], 1, 1));
			}

			$config = sprintf($config, $version, date('r'),
				$_POST['imap_user'] != '' ? $_POST['imap_user'] : '---',
				$_POST['hashing_strategy'],
				'my database', $_POST['dsn'], $_POST['prefix'],
				$_POST['imap_type'], $_POST['imap_host'], $_POST['imap_port'], $_POST['imap_user'], $_POST['imap_pass']);
			if(!file_exists('./inc/config.local.inc.php')) {
				$written = strlen($config) == @file_put_contents('./inc/config.local.inc.php', $config);
			} else {
				$written = false;
			}
		}
		include('./templates/setup/step3.tpl');
		break;
	case '2':
		$available_db	= array();
		if(function_exists('mysql_connect'))	$available_db[] = array('mysql', 'mysql://user:pwd@host/mydb');
		if(function_exists('mysqli_connect'))	$available_db[] = array('mysqli', 'mysqli://user:pwd@host/mydb');
		if(function_exists('oci_connect'))	$available_db[] = array('oci8', 'oci8://user:pwd@host/sid');
		if(function_exists('pg_connect'))	$available_db[] = array('postgres', 'postgres://user:pwd@host/mydb');
		include('./templates/setup/step2.tpl');
		break;
	default:
	case '1':
		$expectations
		= array('asp_tags'			=> 0,
			'display_errors'		=> 0,
			'log_errors'			=> 1,
			'ignore_repeated_errors'	=> 1,
			'ignore_repeated_source'	=> 1,
			);

		$requirements
		= array(
			'short_open_tag'		=> 1,
			);

		$checks
		= array(
				array('PHP is version 5.1.0 or later?', version_compare(PHP_VERSION, '5.1.0', '>=')),
				array('Multibyte String support active?', function_exists('mb_convert_encoding')),
				array('Socket or IMAP support available?', function_exists('fsockopen') || function_exists('imap_open')),
				array('MySQL or MySQLi, PostgreSQL, Oracle (OCI8)?', function_exists('mysql_connect') || function_exists('mysqli_connect') || function_exists('pg_connect') || function_exists('oci_connect')),
				array('Is ADOdb installed?', function_exists('ADONewConnection')),
				array('Is PEAR::Log installed?', class_exists('Log')),
				array('Is PECL::idn installed?', function_exists('idn_to_ascii') && function_exists('idn_to_utf8')),
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