<?php
/**
 * Interface to various algorithms for ciphering passwords.
 *
 * @pattern 		Strategy
 * @see_also 		Password
 */
interface IPasswordStrategy
{
	/**
	 * @return	String		with ciphered password.
	 */
	public function cipher($plaintext);
	/**
	 * @return 	boolean 	True if plaintext matches hashed password, else False.
	 */
	public function equals($hashed, $plaintext);

}
?>