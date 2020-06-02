<?php

namespace WebImage\Core;

/**
 * 02/05/2010	(Robert Jones) Changed $lst, and $current_index properties to be private (instead of var)
 * 08/04/2010	(Robert Jones) Added $cacheCount to Collection to keep track of how many entries are in the collection/array.  When used in hasNext() execution decreased by 100 times
 */
class Collection implements ICollection { // Implements ICollection
	private $lst = array();
	private $current_index = -1;
	private $cacheCount;
	
	function __construct() {}
	
	// Allows overriding classes to verify that the object is allowed to be added
	protected function isItemValid($list_item) { return true; }
	
	public function add($list_item) {
		if ($this->isItemValid($list_item)) {
			$this->lst[] = $list_item;
			$this->cacheCount++;
		} else {
			throw new Exception('Invalid object type added to stack');
		}
	}
	public function insert($list_item) { array_unshift($this->lst, $list_item); $this->cacheCount++; }
	public function clear() { $this->lst = array(); $this->cacheCount = null; } // Removes all controls
	public function getCount() {
		if (is_null($this->cacheCount)) {
			return count($this->lst);
		} else {
			return $this->cacheCount;
		}
	} // Returns total controls
	public function getCurrentIndex() { return $this->current_index; }
	public function setCurrentIndex($index) { $this->current_index = $index; }
	public function removeAt($index) { // Remove control at (int) index
		if (isset($this->lst[$index])) array_splice($this->lst, $index, 1);
		if ($this->getCurrentIndex() >= $index) $this->setCurrentIndex($index-1);
		$this->cacheCount --;
	}
	public function resetIndex() { $this->current_index = -1; }
	public function getAt($index) {
		if (isset($this->lst[$index])) { 
			return $this->lst[$index];
		} else return false;
	}
	
	public function setAt($index, $list_item_value) {
		$this->lst[$index] = $list_item_value;
	}
	
	public function &getAll() { return $this->lst; }
	public function getNext() {
		$next_index = $this->getCurrentIndex() + 1;
		if ($this->hasNext()) {
			$this->setCurrentIndex($next_index);
			return $this->lst[$next_index];
		} else {
			$this->resetIndex(); // resets the index so that this object can be used again
			return false;
		}
	}
	public function hasNext() {
		$next_index = $this->getCurrentIndex() + 1;
		if (is_null($this->cacheCount)) {
			return (isset($this->lst[$next_index]));
		} else {
			return ($next_index < $this->cacheCount);
		}
	}
	
	/**
	 * Merge another collection into this collection
	 * @param Collection $collection
	 * @return array|mixed
	 */
	public function merge($collection) {
		if (is_array($collection)) $this->lst = array_merge($this->lst, $collection);
		else if (is_a($collection, 'Collection')) $this->lst = array_merge($this->lst, $collection->lst);
		else throw new Exception('Invalid type passed to Collection::merge($collection).');
		$this->cacheCount = count($this->lst);
		
		return $this->getAll();
	}
	
	/**
	 * Sort the internal storage mechanism
	 * @param callable $sorter
	 */
	public function sort(callable $sorter) {
		usort($this->lst, $sorter);
	}
	
	/**
	 * Return a filtered copy of the internal storage mechanism
	 * @param callable $filterer
	 * @return $this
	 */
	public function filter(callable $filterer) {
		$filtered = new static;
		
		foreach($this as $key => $val) {
			$result = call_user_func($filterer, $val, $key) === true;
			if (!is_bool($result)) throw new \RuntimeException('Filter must return boolean');
			
			if ($result === true) {
				$filtered->add($val);
			}
		}
		
		return $filtered;
	}
}