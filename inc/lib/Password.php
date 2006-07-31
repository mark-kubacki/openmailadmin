<?php
/**
 * Auxiliary class for being used by class User to handle passwords.
 *
 * @see		obfuscator_decrypt(), obfuscator_encrypt()
 */
class Password
{
	public		$ciphered;
	private		$plaintext		= null;
	private		$cipher_strategy;
	private		$user;

	/**
	 * @param	user		The user this class manages password for.
	 * @param	algorithm	Name of cipher algorithm. If none is given PasswordMD5 will be used.
	 */
	public function __construct(User $user, $ciphered = null, IPasswordStrategy $algorithm = null) {
		if(is_null($algorithm)) {
			$algorithm = new PasswordMD5();
		}
		$this->cipher_strategy = $algorithm;
		$this->ciphered = $ciphered;
		$this->user = $user;
	}

	/**
	 * @param	plaintext	The password as plain text.
	 * @return	boolean
	 */
	public static function is_secure($plaintext) {
		return preg_match('/[a-z]{1}/', $plaintext)
			&& preg_match('/[A-Z]{1}/', $plaintext)
			&& preg_match('/[0-9]{1}/', $plaintext);
	}

	/**
	 * @return	boolean		whether plaintext password matches stored hash.
	 */
	public function equals($plaintext_password) {
		return $this->cipher_strategy->equals($this->ciphered, $plaintext_password);
	}

	/**
	 * Ciphers and sets the given password for attached user.
	 */
	public function set($plaintext_password) {
		$tmp = $this->cipher_strategy->cipher($plaintext_password);
		if($this->user->immediate_set('password', $tmp)) {
			$this->ciphered = $tmp;
			$this->store_plaintext($plaintext_password);
			return true;
		}
		return false;
	}

	/**
	 * Generates a random password and sets it.
	 *
	 * @param	min	New password's minimum length.
	 * @param	max	New password's maximum length.
	 * @return	String	with the generated password s plain text.
	 */
	public function set_random($min, $max) {
		srand((double)microtime()*674563);
		do {
			$pw = generatePW(rand($min, $max));
		} while(!Password::is_secure($pw));
		$this->set($pw);
		return $pw;
	}

	/**
	 * For storing the entire class in $_SESSION.
	 */
	public function store_plaintext($plaintext_password) {
		$this->plaintext = obfuscator_encrypt($plaintext_password);
	}

	/**
	 * @return	String		With decrypted plaintext password.
	 * @throws	RuntimeException	if no plaintext password has been stored so far.
	 */
	public function get_plaintext() {
		if(is_null($this->plaintext)) {
			throw new RuntimeException('No plaintext password has been provided for storage, yet.');
		} else {
			return obfuscator_decrypt($this->plaintext);
		}
	}

}
?>