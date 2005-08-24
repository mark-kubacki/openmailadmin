<?php
/*************************************************************

 File: cyradm-php.lib
 Author: gernot
 Revision: 2.0.0
 Date: 2000/08/11

 This is a completely new implementation of the IMAP Access for
 PHP. It is based on a socket connection to the server an is
 independent from the imap-Functions of PHP

 Copyright 2000 Gernot Stocker <muecketb@sbox.tu-graz.ac.at>

 Changes by Luc de Louw <luc@delouw.ch>
 - Added renamemailbox command as available with cyrus IMAP 2.2.0-Alpha
 - Added getversion to find out what version of cyrus IMAP is running

 Last Change on $Date: 2003/05/04 14:22:10 $

 $Id: cyradm.php,v 1.3 2003/05/04 14:22:10 luc Exp $


 You should have received a copy of the GNU Public
 License along with this package; if not, write to the
 Free Software Foundation, Inc., 59 Temple Place - Suite 330,
 Boston, MA 02111-1307, USA.


 THIS PROGRAM IS AS IT IS! THE AUTHOR TAKES NO RESPONSABILTY ABOUT
 EVENTUAL DEMAGES, SECURITS-HOLES OR ATTACKES, WHICH COULD BE ENABLED
 BY THIS PROGRAM


 ***************************************************************/


class cyradm
{

	var $host;
	var $port;
	var $mbox;
	var $list;

	var $admin;
	var $pass;
	var $fp;
	var $line;
	var $error_msg;

	/*
	#
	#Konstruktor
	#
	*/
	function cyradm()
	{
		global $rtxt;
		$_keys = array('host', 'port', 'admin', 'pass');
		foreach ($_keys as $_key){
			$this->$_key = $GLOBALS['CYRUS'][strtoupper($_key)];
		}
		$this->mbox	= $this->line	= $this->error_msg	= '';
		$this->list	= array();
		$this->fp	= 0;
	}


	/*
	#
	# SOCKETLOGIN on Server via Telnet-Connection!
	#
	*/
	function imap_login()
	{
		$this->fp = fsockopen($this->host, $this->port, $errno, $errstr);
		$this->error_msg = $errstr;
		if(!$this->fp) {
			echo "<br>ERRORNO: ($errno) <br>ERRSTR: ($errstr)<br><hr>\n";
		} else {
			$_cmd = sprintf('. login "%s" "%s"',
				$this->admin, $this->pass);
			$this->command($_cmd);
		}
		return $errno;
	}


	/*
	#
	# SOCKETLOGOUT from Server via Telnet-Connection!
	#
	*/
	function imap_logout()
	{
		$this->command(". logout");
		fclose($this->fp);
	}

	/*
	#
	# SENDING COMMAND to Server via Telnet-Connection!
	#
	*/
	function command($line)
	{
		global $rtxt;
		// print ("line in command: <br><pre><tt>" . $line . "</tt></pre><br>");
		$result = array();
		$i = $f = 0;
		$returntext = "";
		$r = fputs($this->fp,$line . "\n");
		while (!((strstr($returntext,". OK")||(strstr($returntext,". NO"))||(strstr($returntext,". BAD"))))){
			$returntext = $this->getline();
			// print ("$returntext <br>");
			if (strstr($returntext,"IMAP4")){
				$rtxt = $returntext;
			}
			if ($returntext){
				if (!((strstr($returntext,". OK")||(strstr($returntext,". NO"))||(strstr($returntext,". BAD"))))){
					$result[$i]=$returntext;
				}
				$i++;
			}
		}

		if (strstr($returntext,". BAD")||(strstr($returntext,". NO"))){
			$result[0]="$returntext";
			$this->error_msg  = $returntext;

			if (( strstr($returntext,". NO Quota") )){
				} else {
				/*
				print "<br><font color=red><hr><H1><center><blink>ERROR: </blink>UNEXPECTED IMAP-SERVER-ERROR</center></H1><hr><br>
				<table color=red border=0 align=center cellpadding=5 callspacing=3>
				<tr><td><font color=red>SENT COMMAND: </font></td><td><font color=red>$line</font></td></tr>
				<tr><td><font color=red>SERVER RETURNED:</font></td><td></td></tr>
				";
				for ($i=0; $i < count($result); $i++) {
				print "<tr><td></td><td><font color=red>$result[$i]</font></td></tr>";
				}
				print "</table><hr><br><br></font>";
				*/
				return false;
			}
		}
		return $result;
	}


	/*
	#
	# READING from Server via Telnet-Connection!
	#
	*/
	function getline()
	{
		$this->line = fgets($this->fp, 256);
		return $this->line;
	}

	/*
	#
	# Getting Cyrus IMAP Version
	#
	*/
	function getversion()
	{
		global $rtxt;
		$pos=strpos($rtxt,"IMAP4 v");
		$pos+=7;
		$version=substr($rtxt,$pos,5);
		// print "<p>Version: ".$version;
		return $version;
	}

	/*
	#
	# QUOTA Functions
	#
	*/

	// GETTING QUOTA
	function getquota($mb_name)
	{
		$output = $this->command(". getquota \"" . $mb_name . "\"");
		if (strstr($output[0], ". NO")) {
			$ret["used"] = "NOT-SET";
			$ret["qmax"] = "NOT-SET";
		} else {
			$realoutput = str_replace(")", "", $output[0]);
			$tok_list = split(" ", $realoutput);
			$si_used = sizeof($tok_list) - 2;
			$si_max = sizeof($tok_list) - 1;
			$ret["used"] = str_replace(")", "", $tok_list[$si_used]);
			$ret["qmax"] = $tok_list[$si_max];
		}
		return $ret;
	}


	// SETTING QUOTA
	function setmbquota($mb_name, $quota)
	{
		$this->command(". setquota \"$mb_name\" (STORAGE $quota)");
	}



	/*
	#
	# MAILBOX Functions
	#
	*/
	function createmb($mb_name)
	{
		$this->command(". create \"$mb_name\"");
	}


	function deletemb($mb_name)
	{
		$this->command(". setacl \"$mb_name\" $this->admin lrswipcda");
		$this->command(". delete \"$mb_name\"");
	}

	function renamemb($mb_name, $newmbname)
	{
		$all = "lrswipcda";
		$this->setacl($mb_name, $this->admin,$all);
		$ret=$this->command(". rename \"$mb_name\" \"$newmbname\"");
		$this->deleteacl($newmbname, $this->admin);
		return $ret;
	}

	function renamemailbox($oldname, $newname)
	{
		// This only works with cyrus imap version 2.2.x for older please use renameuser
		$ret=$this->command(". renamemailbox \"$oldname\" \"$newname\"");
		return $ret;
	}

	function renameuser($from_mb_name, $to_mb_name)
	{
		$all = "lrswipcda";
		$find_out = $split_res = array();
		$owner = $oldowner = '';

		/* Anlegen und Kopieren der INBOX */
		$this->createmb($to_mb_name);
		$this->setacl($to_mb_name, $this->admin,$all);
		$this->copymailsfromfolder($from_mb_name, $to_mb_name);

		/* Quotas uebernehmen */
		$quota = $this->getquota($from_mb_name);
		$oldquota = trim($quota["qmax"]);

		if (strcmp($oldquota,"NOT-SET")!=0) {
			$this->setmbquota($to_mb_name, $oldquota);
		}

		/* Den Rest Umbenennen */
		$username = str_replace(".","/",$from_mb_name);
		$split_res = explode(".", $to_mb_name);
		if (strcmp($split_res[0],"user") == 0) {
			$owner=$split_res[1];
		}
		$split_res=explode(".", $from_mb_name);
		if (strcmp($split_res[0],"user") == 0) {
			$oldowner=$split_res[1];
		}

		$find_out = $this->GetFolders($username);

		for ($i=0; $i < count($find_out); $i++) {

			if (strcmp($find_out[$i],$username)!=0) {
				$split_res = split("$username",$find_out[$i]);
				$split_res[1] = str_replace("/",".",$split_res[1]);
				$this->renamemb((str_replace("/",".",$find_out[$i])), ("$to_mb_name"."$split_res[1]"));
				if ($owner) {
					$this->setacl(("$to_mb_name"."$split_res[1]"),$owner,$all);
				}
				if ($oldowner) {
					$this->deleteacl(("$to_mb_name"."$split_res[1]"),$oldowner);
				}
			}
		}
		$this->deleteacl($to_mb_name, $this->admin);
		$this->imap_logout();
		$this->imap_login();
		$this->deletemb($from_mb_name);
	}

	function copymailsfromfolder($from_mb_name, $to_mb_name)
	{
		$com_ret = $find_out = array();
		$all = "lrswipcda";
		$mails = 0;

		$this->setacl($from_mb_name, $this->admin,$all);
		$com_ret = $this->command(". select $from_mb_name");
		for ($i=0; $i < count($com_ret); $i++) {
			if (strstr($com_ret[$i], "EXISTS")){
			$findout=explode(" ", $com_ret[$i]);
			$mails=$findout[1];
			}
		}
		if ($mails != 0){
			$com_ret=$this->command(". copy 1:$mails $to_mb_name");
			for ($i=0; $i < count($com_ret); $i++) {
				print "<span style=\"color: red;\">" . $com_ret[$i] . "</span><br>";
			}
		}
		$this->deleteacl($from_mb_name, $this->admin);
	}

	/*
	#
	# ACL Functions
	#
	*/
	function setacl($mb_name, $user, $acl)
	{
		$this->command(". setacl \"$mb_name\" \"$user\" $acl");
	}

	function deleteacl($mb_name, $user)
	{
		$result=$this->command(". deleteacl \"$mb_name\" \"$user\"");
	}


	function getacl($mb_name)
	{
		$result = array(); $arr = array();
		$output = $this->command(". getacl \"$mb_name\"");

		if(!is_null($mb_name)) {
		    $output = str_replace($mb_name, '##', $output);
		}

		if(preg_match('/\*\sACL\s[^\s]*\s(.*)/', $output[0], $arr)) {
		    if(preg_match_all('/([^\s]*)\s([lrswipcda]*)\s?/', $arr[1], $arr)) {
			$result = array_combine($arr[1], $arr[2]);
		    }
		}

		return $result;
	}

	/*
	#
	# Folder Functions
	#
	*/

	function GetFolders($username)
	{
		$username = str_replace("/", ".", $username);
		$output = $this->command(". list \"$username\" *");

		for ($i=0; $i < count($output); $i++) {
			$splitfolder=split("\"",$output[$i]);
			$output[$i]=str_replace(".","/",$splitfolder[3]);
		}
		return $output;
	}

	function EGetFolders($username)
	{
		$lastfolder=split("/",$username);
		$position=count($lastfolder)-1;
		$last=$lastfolder[$position];
		$username=str_replace("/",".",$username);
		$output = $this->command(". list \"$username\" *");

		for ($i=0; $i < count($output); $i++) {
			$splitfolder=split("\"",$output[$i]);
			$currentfolder=split("\.",$splitfolder[3]);
			$current=$currentfolder[$position];
			// echo "<br>FOLDER:($) CURRENTFOLDER:($splitfolder[3]) CURRENT:($current) LAST:($last) POSITION:($position)<br>";
			if (strcmp($current,$last)==0){
				$newoutput[$i]=str_replace(".","/",$splitfolder[3]);
			}
		}
		return $newoutput;
	}


	/*
	#
	# Folder-Output Functions
	#
	*/
	function GenerateFolderList($folder_array, $username)
	{
		?>
		<table border="0" align="center">
			<?php
			for ($l=0; $l < count($folder_array); $l++){
				?>
				<tr>
					<td>
						<a href="acl.php?username=<?php
						echo urlencode($username);
						?>&amp;folder=<?php
						echo urlencode($folder_array[$l]);
						?>">/<?php
						echo $folder_array[$l];
						?></a>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
	}


	function GetUsers($char="") {
		$users = array();
		$this->imap_login();
		$output = $this->GetFolders("user." . $char);
		$this->imap_logout();
		$j = $prev = 0;
		for ($i=0; $i < count($output); $i++) {
			$username = split("/", $output[$i], -1);
			$this->debug("(" . $username[1] . "\n" . $users[$prev]);
			if ((isset($username)) && (isset($users))) {
				if (strcmp($username[1], $users[$prev])) {
					$users[$j] = $username[1];
					$j++;
				}
			}
			if ($j != 0) {
				$prev = $j - 1;
			}
		}
		return $users;
	}

	function debug($message) {
		// echo "<hr>$message<br><hr>";
	}


} //KLASSEN ENDE

