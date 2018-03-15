<?php

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../../../cli/lib/argumentparser.php');

final class ArgumentParserTest extends TestCase {
	private $testArgs = [
		'command.php',
		'-t',
		'--test',
		'--name',
		'robert'
	];
	
	public function testIsFlagSet() {
		
		$args = new ArgumentParser($this->testArgs);
		
		$this->assertTrue($args->isFlagSet('t'), 'isFlagSet should return true when the flag exists');
		$this->assertTrue($args->isFlagSet('test'), 'isFlagSet should return true when the flag exists');
	}
	
	public function testFlagsWithoutValueNull() {
		
		$args = new ArgumentParser($this->testArgs);
		
		$this->assertNull($args->getFlag('t'), 'Short name flags without values should return null');
		$this->assertNull($args->getFlag('test'), 'Long name flags without values should return null');
	}
	
	public function testFlagsWithValue() {
		
		$args = new ArgumentParser($this->testArgs);
		
		$this->assertEquals('robert', $args->getFlag('name'), 'Named values with values should return the value');
	}
	
	public function testCommandNotFlag() {
		
		$args = new ArgumentParser($this->testArgs);
		
		$this->assertFalse($args->isFlagSet('command.php'), 'The command name should not be treated as a flag');
	}
	
	public function testCommandValue() {
		
		$args = new ArgumentParser($this->testArgs);
		
		$this->assertEquals('command.php', $args->getCommand(), 'Command name should be returned');
	}
	
	public function testGlobalFlag() {
		
		$args = new ArgumentParser(['command.php', 'globalValue']);
		
		$this->assertEquals('globalValue', $args->getFlag('global'), 'Global flag should return the last argument (when not associated with a flag)');
	}
}