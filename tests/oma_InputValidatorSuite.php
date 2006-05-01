<?php
/**
* Test class for InputValidatorSuite.
*/
class oma_InputValidatorSuite
	extends PHPUnit2_Framework_TestCase
{
	/**
	 * Runs the test methods of this class.
	 */
	public static function main() {
		require_once 'PHPUnit2/TextUI/TestRunner.php';

		$suite  = new PHPUnit2_Framework_TestSuite('oma_InputValidatorSuite');
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

	public function testEmptyTestsuiteReturnsTrue() {
		$suite	= new InputValidatorSuite();
//		$this->assertTrue($suite->validate(array(), array()));
	}

	/**
	 * @deprecated		Initialization shall be made by every plugin and not in constructor.
	 */
	public function testValidFieldsReturnTrue() {
		$suite	= new InputValidatorSuite();
		$input	= array('quota'		=> 102400,
				'max_alias'	=> 20,
				'max_regexp'	=> 0,
				'categories'	=> 'all, secrecy,flowers,trees',
				);
		$todo	= array();
		foreach($input as $key=>$value) {
			$todo	= array_merge($todo, array($key => $value));
			$this->assertTrue($suite->validate($todo, array_keys($todo)), 'After having added '.$key.', ');
		}
	}

	/**
	 * @deprecated		Initialization shall be made by every plugin and not in constructor.
	 */
	public function testInvalidFieldsReturnFalse() {
		$suite	= new InputValidatorSuite();
		$input	= array('quota'		=> -234,
				'max_alias'	=> 'abc',
				'max_regexp'	=> 0,
				'categories'	=> '#########',
				);
		$todo	= array();
		foreach($input as $key=>$value) {
			$todo	= array_merge($todo, array($key => $value));
			$this->assertFalse($suite->validate($todo, array_keys($todo)), 'After having added '.$key.', ');
		}
	}

}
?>
