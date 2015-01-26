<?php

class CWI_ASSETS_AssetFolder {
	
	private $folder, $id, $name, $parentId;//, $type;
	
	public function __construct() {}
	
	public function getFolder() { return $this->folder; }
	public function getId() { return $this->id; }
	public function getName() { return $this->name; }
	public function getParentId() { return $this->parentId; }
	#public function getType() { return $this->type; }
	
	public function setFolder($folder) { $this->folder = $folder; }
	public function setId($id) { $this->id = $id; }
	public function setName($name) { $this->name = $name; }
	public function setParentId($parent_id) { $this->parentId = $parent_id; }
	#public function setType($type) { $this->type = $type; }
	
}

?>