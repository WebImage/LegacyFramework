<?php

/**
 * Interface for IXmlObjects, that is, objects that can be exported to Xml
 * (at the time of implementation this was only used by the listings plugin)
 */
interface IXmlObject {
	/**
	 * Export the current object to XML
	 * @param boolean $verbose Whether to return empty tags - defaults to false to save output space
	 * @return string The xml that represents the question
	 */
	public function toXmlTraversal($verbose=false); // String
}