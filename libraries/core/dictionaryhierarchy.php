<?php

/**
 * Allows the creation of a hierarchy of dictionary values
 **/
class DictionaryHierarchy extends Dictionary {
	private $parent;
	
	public function setParent(Dictionary $parent) { $this->parent = $parent; }
	public function getParent() { return $this->parent; }
}
