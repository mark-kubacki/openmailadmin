<?php
interface IMAP_Administrator
{
	/**
	 * @return		Single char with current separator.
	 */
	function gethierarchyseparator();
	/**
	 * @param	mb	Mailbox to be created.
	 * @return		True on success.
	 */
	function createmb($mb);
	/**
	 * @param	mb	Mailbox to be deleted.
	 * @return		True on success.
	 */
	function deletemb($mb);
	/**
	 * @param	from_mn	That mailbox will be renamed.
	 * @param	to_mb	New name of mailbox.
	 * @return		True on success.
	 */
	function renamemb($from_mb, $to_mb);

	/**
	 * @param	mb	Mailbox.
	 * @return		Instance of class Quota or false.
	 */
	function getquota($mb);
	/**
	 * This is to avoid perpetual calls to format_user.
	 *
	 * @param	username	Username, not mailbox' name.
	 * @return		Instance of class Quota.
	 */
	function get_users_quota($username);
	/**
	 * @param	mb	Mailbox. May be a submailbox (aka subfolder).
	 * @param	many	Quota. Has to be an integer kiB as dimension. If left out or null the mailbox' quota will be removed and thus regarded as 'not set' - that means unlimited.
	 */
	function setquota($mb, $many);

	/**
	 * @param	ref	IMAP folder reference.
	 * @param	pat	Pattern. Typically with wildcards such as "*" and "?".
	 * @return		Array with these attributes: name, delimiter, attributes (don't rely on the latter)
	 */
	function getmailboxes($ref = '', $pat = '*');
	/**
	 * @return	Array	with all available rights as letters.
	 */
	public function get_acl_available();
	/**
	 * @param	mb	mailbox
	 * @return		Array with usernames as keys and corresponding AC as value.
	 */
	function getacl($mb);
	/**
	 * @param	mb	mailbox
	 * @param	user	Mailboxname (aka username) to receive rights on given mailbox.
	 * @param	acl	AC to be granted. "none" means removal.
	 */
	function setacl($mb, $user, $acl);

	/**
	 * @param	username	Mailboxname without formatting.
	 * @return			true or false
	 */
	function is_valid_username($username);
	/**
	 * Some IMAP servers don't repsect standards and introduce their own formatting of mailboxes and subfolders.
	 *
	 * @param	username	Mailboxname without formatting.
	 * @param	folder		Possible subfolder of that given mailbox.
	 * @return			Fully formatted mailbox as string.
	 */
	function format_user($username, $folder = null);
	/**
	 * @return		Version of connected IMAP server as string.
	 */
	function getversion();

}
?>