<?php

namespace WebImage\ControlCompiler\Result;

class AbstractControlComponent implements IControlComponent {
	private $inited = false;
	/**
	 * Getter/setter
	 **/
	public function isInitialized($true_false=null) {

		if (is_null($true_false)) { // Getter
			return $this->inited;
		} else {
			if (!is_bool($true_false)) throw new \Exception('Invalid parameter passed.  Boolean required.');
			$this->inited = $true_false;
		}

	}
}