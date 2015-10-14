<?php

namespace WebImage\Core;
/**
 * 05/31/2009	(Robert Jones) Added setAt($index, $value) - not sure why this wasn't added originally
 * 02/05/2010	(Robert Jones) Added merge($collection)
 */
interface ICollection {
	/**
	 * Add an item to the collection stack
	 * @param mixed $list_item any type of simple or complex object that can be added to the stack
	 * @access public
	 * @return void
	 */
	public function add($list_item);
	/**
	 * Insert an item at the top of the stack
	 * @param mixed $list_item any type of simple or complex object that can be insert into the top of the stack
	 * @access public
	 * @return void
	 */
	public function insert($list_item);
	/**
	 * Resets the collection to empty
	 * @access public 
	 * @return void
	 */
	public function clear();
	/**
	 * Get the number of elements currently in the collection
	 * @access public
	 * @return int the number of items in the collection
	 */
	public function getCount();
	/**
	 * Get the internal index (0-based) of the currently selected item
	 * @access public
	 * @return int the current index (0-based)
	 */
	public function getCurrentIndex();
	/**
	 * Sets the current index (0-based) / internal counter
	 * @param int $index the index to be set
	 * @access public
	 * @return void
	 **/
	public function setCurrentIndex($index);
	/**
	 * Removes an element at the specified index
	 * @param int $index the index to remove an item at
	 * @access public
	 * @return void
	 */
	public function removeAt($index);
	/**
	 * Resets an index back to 0
	 * @access public
	 * @return void
	 */
	public function resetIndex();
	/**
	 * Get the object at the index specified
	 * @param int $index the index at which to get the associated object
	 * @access public
	 * @return mixed the object at the specified index
	 */
	public function getAt($index);	
	/**
	 * Sets the object at the specified index
	 * @param int $index the index at which the new item will be set (overwrites an object if it is exists)
	 * @param mixed $list_item_value the object to set at the specified index
	 * @access public
	 * @return void
	 */
	public function setAt($index, $list_item_value);
	/**
	 * Returns an array of all of the objects associated with this collection
	 * @accesss public
	 * @return array of mixed objects
	 */
	public function &getAll();
	/**
	 * Get the next object by increasing the internal index by 1
	 * @access public
	 * @return mixed|boolean returns mixed if an object exists at the next index, or false if the current index is the last object in the collection
	 */
	public function getNext();
	/**
	 * Tests whether an object exists at the next index 
	 * @access public
	 * @return boolean true if another object exists at the next index, or false if the current object is the last object, or false if there are not any objects in the collection
	 */
	public function hasNext();
	/**
	 * Merge another collection into this collection
	 * @param Collection another collection to be merged
	 * @access public
	 * @return mixed (same as if getAll() was called after the merge is complete
	 */
	public function merge($collection);
}

?>