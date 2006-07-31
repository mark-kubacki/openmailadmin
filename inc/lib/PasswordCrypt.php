<?php
class PasswordCrypt
	implements IPasswordStrategy
{
	public function cipher($plaintext) {
		return crypt($plaintext, substr($plaintext,0,2));
	}
	public function equals($hashed, $plaintext) {
		return crypt($plaintext, $hashed) == $hashed;
	}

}
?>