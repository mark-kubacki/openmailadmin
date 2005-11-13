<?php
/*************************************************************

 File: cyradm-php.lib
 Author: gernot
 Revision: 2.0.1
 Date: 2000/08/11

 This is a completely new implementation of the IMAP Access for
 PHP. It is based on a socket connection to the server an is
 independent from the imap-Functions of PHP

 Copyright 2000 Gernot Stocker <muecketb@sbox.tu-graz.ac.at>

 Changes by Luc de Louw <luc@delouw.ch>
 - Added renamemailbox command as available with cyrus IMAP 2.2.0-Alpha
 - Added getversion to find out what version of cyrus IMAP is running

 Changes by W-Mark Kubacki <wmark@hurrikane.de>
 - Removed specialized functions which formatted output.
 - Fixed bugs regarding fetching of ACL.

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

} //KLASSEN ENDE

