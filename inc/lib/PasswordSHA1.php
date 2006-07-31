<?php
class PasswordSHA1
	implements IPasswordStrategy
{
	public function cipher($plaintext) {
		return sha1($plaintext);
	}
	public function equals($hashed, $plaintext) {
		return $hashed == sha1($plaintext);
	}

}
?>