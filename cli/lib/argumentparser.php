<?php

// Command-line arguments
class ArgumentParser {
	private $command;
	private $flags = array();
	function __construct($raw_command_args) {
		$this->command = $raw_command_args[0];
		
		$flag_name = 'global';
		for($i=1; $i < count($raw_command_args); $i++) {
			$arg = $raw_command_args[$i];
			if (substr($arg, 0, 1) == '-') {
				$remove_char = 1;
				if (substr($arg, 1, 1) == '-') $remove_char ++;
				
				$flag_name = substr($arg, $remove_char);
				$arg = null; // Set default value
			}
			$this->flags[$flag_name] = $arg;
		}
	}
	function getCommand() { return $this->command; }
	function isFlagSet($flag) {
		return (in_array($flag, array_keys($this->flags)));
	}
	function getFlag($flag, $default=false) {
		if ($this->isFlagSet($flag)) return $this->flags[$flag];
		else return $default;
	}
	function setFlag($name, $value) {
		$this->flags[$name] = $value;
	}
}

?>