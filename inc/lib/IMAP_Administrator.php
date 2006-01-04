<?php
interface IMAP_Administrator
{
	function gethierarchyseparator();
	function createmb($mb);
	function deletemb($mb);
	function renamemb($from_mb, $to_mb);

	function getquota($mb);
	function setquota($mb, $many, $storage = '');

	function getmailboxes($ref = '', $pat = '*');
	function setacl($mb, $user, $acl);
	function getacl($mb);
	function format_user($username, $folder = null);

	function getversion();
}
?>