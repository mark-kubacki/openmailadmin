<?php
/**
* Test class for InputValidationCase.
*/
class oma_InputValidationCase
	extends PHPUnit2_Framework_TestCase
{
	/**
	 * Runs the test methods of this class.
	 */
	public static function main() {
		require_once 'PHPUnit2/TextUI/TestRunner.php';

		$suite  = new PHPUnit2_Framework_TestSuite('oma_InputValidationCase');
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
		$name	= 'John Doe';
		$case	= new InputValidationCase($name, 'Name');
		$this->assertTrue($case->valid());
	}

	public function testInteger() {
		$num	= -34;
		$case	= new InputValidationCase($num, 'Number');
		$case->add_check(new InputValidationPair(InputValidationPair::IsInteger));
		$this->assertTrue($case->valid());
	}

	public function testUnsignedInteger() {
		$num	= 3;
		$case	= new InputValidationCase($num, 'Number');
		$case->add_check(new InputValidationPair(InputValidationPair::IsInteger));
		$case->add_check(new InputValidationPair(InputValidationPair::Unsigned));
		$this->assertTrue($case->valid());
	}

	public function testInvalidInteger() {
		$name	= 'John Doe';
		$case	= new InputValidationCase($name, 'Name');
		$case->add_check(new InputValidationPair(InputValidationPair::IsInteger));
		$this->assertFalse($case->valid());
	}

	public function testNotUnsignedInteger() {
		$num	= -3;
		$case	= new InputValidationCase($num, 'Number');
		$case->add_check(new InputValidationPair(InputValidationPair::IsInteger));
		$case->add_check(new InputValidationPair(InputValidationPair::Unsigned));
		$this->assertFalse($case->valid());
	}

	public function testInvalidIntegerButAssignsDefaultValue() {
		$name	= 'John Doe';
		$case	= new InputValidationCase($name, 'Name', 30);
		$case->add_check(new InputValidationPair(InputValidationPair::IsInteger));
		$this->assertFalse($case->valid());
		$this->assertEquals($name, 30);
	}

}
?>
