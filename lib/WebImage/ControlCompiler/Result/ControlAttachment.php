<?php

namespace WebImage\ControlCompiler\Result;

class ControlAttachment extends AbstractControlComponent {
	private $childName, $parentName;
	public function __construct($child_name, $parent_name) {
		$this->childName = $child_name;
		$this->parentName = $parent_name;
	}
	public function getChildName() { return $this->childName; }
	public function getParentName() { return $this->parentName; }
}