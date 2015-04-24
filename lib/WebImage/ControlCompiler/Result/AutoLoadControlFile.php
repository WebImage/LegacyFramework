<?php

namespace WebImage\ControlCompiler\Result;

class AutoLoadControlFile extends AbstractControlComponent {
	private $file;
	public function __construct($file) {
		$this->file = $file;
	}
}