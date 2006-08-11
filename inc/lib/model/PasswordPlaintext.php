<?php
class PasswordPlaintext
	implements IPasswordStrategy
{
	public function cipher($plaintext) {
		return $plaintext;
	}
	public function equals($hashed, $plaintext) {
		if($hashed == '')
			return false;
		return $hashed == $plaintext;
	}

}
?>