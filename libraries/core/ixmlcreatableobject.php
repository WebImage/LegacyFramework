<?php

/**
 * Interface for objects that can be instantiated using an XML Object (CWI_XML_Traversal)
 **/
interface IXmlCreatableObject {
	/**
	 * Creates an instance of whatever implementing object using an CWI_XML_Traversal object
	 **/
	public function createFromXmlTraversal(CWI_XML_Traversal $xml_root);
}