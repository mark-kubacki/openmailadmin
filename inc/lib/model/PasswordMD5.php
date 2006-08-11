<?php
class PasswordMD5
	implements IPasswordStrategy
{
	public function cipher($plaintext) {
		return md5($plaintext);
	}
	public function equals($hashed, $plaintext) {
		return $hashed == md5($plaintext);
	}

}
?>