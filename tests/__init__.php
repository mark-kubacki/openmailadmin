<?php
require_once 'PHPUnit2/Framework/TestSuite.php';
require_once 'PHPUnit2/Framework/TestCase.php';
require_once 'PHPUnit2/Framework/IncompleteTestError.php';
require_once 'PHPUnit2/TextUI/TestRunner.php';

require_once './tests/oma_InputValidationPair.php';
require_once './tests/oma_InputValidationCase.php';

class OMA_Test_Suite
{
	public static function main() {
		PHPUnit2_TextUI_TestRunner::run(self::suite());
	}

	public static function suite() {
		$suite = new PHPUnit2_Framework_TestSuite();

		$suite->addTestSuite('oma_InputValidationPair');
		$suite->addTestSuite('oma_InputValidationCase');

		return $suite;
	}

}

if(PHPUnit2_MAIN_METHOD == 'OMA_Test_Suite::main') {
	OMA_Test_Suite::main();
}

?>
