<?php
/**
 * Does administer all the properties, permissions and any relationship
 * methods, such as "is_descendant" etc.
 * Use instances of this class whenever data about any user is needed.
 *
 * @see		obfuscator_decrypt(), obfuscator_encrypt()
 */
class User
	extends DataCarrier
{
	public static		$db;
	public static		$tablenames;

	private		$username	= null;
	protected	$pass_clear	= null;

	/**
	 * @param	username	User must exist.
	 * @throws	Exception	if user does not exist.
	 */
	public function __construct($username) {
		$data = self::$db->GetRow('SELECT * FROM '.self::$tablenames['user'].' WHERE mbox='.self::$db->qstr($username));
		if($data === false) {
			throw new Exception(txt(2));
		}
		$this->become($data);
	}

	/**
	 * @return	Boolean		whether plaintext password matches stored hash.
	 */
	public function check_password($plaintext_password) {
		return (md5($plaintext_password) == $this->pass_md5);
	}

	/**
	 * @return	String		With decrypted plaintext password or empty string, if no password was set.
	 */
	public function get_plaintext_password() {
		if(is_null($this->pass_clear)) {
			return '';
		} else {
			return obfuscator_decrypt($this->pass_clear);
		}
	}

	public function set_plaintext_password($plaintext_password) {
		$this->pass_clear = obfuscator_encrypt($plaintext_password);
	}

	private function update_last_login() {
		self::$db->Execute('UPDATE '.self::$tablenames['user'].' SET last_login='.time().' WHERE mbox='.self::$db->qstr($this->username));
	}

	/**
	 * Use this to get a new user only if given plaintext password matches.
	 *
	 * @param	username	User must exist.
	 * @param	password	Plaintext password
	 * @return			User
	 * @throws	Exception	if user does not exist or password didn't match.
	 */
	public static function authenticate($username, $password) {
		$usr	= new User($username);
		if($usr->check_password($password)) {
			$usr->update_last_login();
			$usr->set_plaintext_password($password);
			return $usr;
		}
		throw new Exception(txt(0));
	}

	public function is_superuser() {
		return $this->a_super >= 1;
	}

}
?>