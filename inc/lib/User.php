<?php
/**
 * Does administer all the properties, permissions and any relationship
 * methods, such as "is_descendant" etc.
 * Use instances of this class whenever data about any user is needed.
 *
 * @see		obfuscator_decrypt(), obfuscator_encrypt()
 */
class User
{
	public static		$db;
	public static		$tablenames;

	private		$username	= null;
	private		$data		= array();
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
		$this->data	= $data;
	}

	/**
	 * This is from Openmaillist's DataCarrier.
	 *
	 * @throw		If no value for $key has yet been set.
	 */
	protected function __get($key) {
		if(array_key_exists($key, $this->data)) {
			return $this->data[$key];
		} else {
			throw new Exception('Variable does not exist or has not been set.');
		}
	}

	protected function __set($key, $value) {
		if(is_null($value)) {
			if(array_key_exists($key, $this->data)) {
				unset($this->data[$key]);
			}
		} else {
			$this->data[$key] = $value;
		}
		return true;
	}

	/**
	 * @return	Boolean		whether plaintext password matches stored hash.
	 */
	public function check_password($plaintext_password) {
		return (md5($plaintext_password) == $this->data['pass_md5']);
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
			self::$db->Execute('UPDATE '.self::$tablenames['user'].' SET last_login='.time().' WHERE mbox='.self::$db->qstr($username));
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