<?php
/**
* Test class for InputValidationPair.
*/
class oma_InputValidationPair
	extends PHPUnit2_Framework_TestCase
{
	/**
	 * Runs the test methods of this class.
	 */
	public static function main() {
		require_once 'PHPUnit2/TextUI/TestRunner.php';

		$suite  = new PHPUnit2_Framework_TestSuite('oma_InputValidationPair');
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

	public function testDefaultIsTrue() {
		$case	= new InputValidationPair();
		$this->assertTrue($case->passes(null));
	}

	public function testValidInteger() {
		$case	= new InputValidationPair();
		$case->validation_command	= InputValidationPair::IsInteger;
		$this->assertTrue($case->passes(3));
	}

	public function testInvalidInteger() {
		$case	= new InputValidationPair();
		$case->validation_command	= InputValidationPair::IsInteger;
		$this->assertFalse($case->passes('abc'));
	}

	public function testValidUnsigned() {
		$case	= new InputValidationPair();
		$case->validation_command	= InputValidationPair::Unsigned;
		$this->assertTrue($case->passes(100.4));
	}

	public function testUnsignedNotSignedInt() {
		$case	= new InputValidationPair();
		$case->validation_command	= InputValidationPair::Unsigned;
		$this->assertFalse($case->passes(-56));
	}

	public function testNotIntNotUnsigned() {
		$case	= new InputValidationPair();
		$case->validation_command	= InputValidationPair::Unsigned;
		$this->assertFalse($case->passes('abc'));
	}

	public function testValidBetween() {
		$case	= new InputValidationPair();
		$case->validation_command	= sprintf(InputValidationPair::Between, -20, 500);
		$this->assertTrue($case->passes(100.4));
	}

	public function testValidBetweenBoundaries() {
		$case	= new InputValidationPair();
		$case->validation_command	= sprintf(InputValidationPair::Between, -20, 500);
		$this->assertTrue($case->passes(-20));
		$this->assertTrue($case->passes(500));
	}

}
?>
