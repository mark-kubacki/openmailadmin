<?php
/**
* Test class for HTMLInputTagGenerator
*/
class oma_HTMLInputTagGenerator
	extends PHPUnit2_Framework_TestCase
{
	/**
	 * Runs the test methods of this class.
	 */
	public static function main() {
		require_once 'PHPUnit2/TextUI/TestRunner.php';

		$suite  = new PHPUnit2_Framework_TestSuite('oma_HTMLInputTagGenerator');
		$result = PHPUnit2_TextUI_TestRunner::run($suite);
	}

	/**
	 * Sets up the fixture, for example, open a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
	}

	/**
	 * Tears down the fixture, for example, close a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
	}

	public function testDontSetDefaultValuesForTypeHidden() {
		$inp	= new HTMLInputTagGenerator();
		$_POST['tst']	= 'login';
		$this->assertFalse(stristr($inp->hidden('tst', 'pass'), 'login'));
		unset($_POST['tst']);
	}
	public function testDontSetDefaultValuesForTypePassword() {
		$inp	= new HTMLInputTagGenerator();
		$_POST['tst']	= 'login';
		$this->assertFalse(stristr($inp->password('tst'), 'login'));
		unset($_POST['tst']);
	}

}
?>
