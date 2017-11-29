<?php
/**
 * CHANGELOG
 * 10/11/2010	(Robert Jones) Added support for permissions
 * 11/17/2017	(Robert Jones) Converted functions to class
 */

class AdminMenu {
	const ROOT = '_';
	const NODE_SITEMAPNODE = 'siteMapNode';
	const NODE_RESETCHILDREN= 'resetChildren';
	const PARAM_RESET = 'reset';
	const SORTORDER_RESET = 'reset';
	
	/**
	 * @var
	 */
	private $hierarchy = array();
	private $sortorders = array();
	
	/**
	 * Get items by their parent ID
	 * @param String $parent
	 * @return mixe
	 */
	public function getItems($parent=null) {
		if (null === $parent) $parent = self::ROOT;
		
		if (isset($this->hierarchy[$parent])) return $this->hierarchy[$parent];
	}
	/**
	 * @param $xml
	 */
	public function importFromXml(CWI_XML_Traversal $site_map) {
		
		/** @var CWI_XML_Traversal $root */
		$root = $site_map->getPathSingle('/siteMap');
		
		if ($root) $this->addChildren($root);
	}
	
	private function addChildren(CWI_XML_Traversal $node, $parent_key=null, $level = 0) {
		
		$sections = $node->getChildren();
		if (null === $parent_key) $parent_key = self::ROOT;
		
		if (!isset($this->hierarchy[$parent_key])) $this->resetHierarchy($parent_key);
		
		/** @var CWI_XML_Traversal $section */
		foreach($sections as $section)  {
		
			if ($section->getTagName() == self::NODE_SITEMAPNODE) {
			
				$section_node = $this->createSectionNode($section, $parent_key, $this->hierarchy[$parent_key]);
				
				if ($this->hasResetParam($section)) {
					$this->resetHierarchy($section_node->getId());
				}
				
				$this->hierarchy[$parent_key][$section_node->getId()] = $section_node;
				
				$this->addChildren($section, $section_node->getId());
			
			} else if ($section->getTagName() == self::NODE_RESETCHILDREN) {
				// Reset the parent key
				$this->resetHierarchy($parent_key);
			}
		}
		
		uasort($this->hierarchy[$parent_key], array($this, 'compareChildren'));
	}
	
	private function compareChildren(AdminMenuItem $a, AdminMenuItem $b) {
		if ($a->getSortorder() == $b->getSortorder()) return 0;
		
		return $a->getSortorder() < $b->getSortorder() ? -1 : 1;
	}
	
	private function hasResetParam(CWI_XML_Traversal $node) {
		$reset = $node->getParam(self::PARAM_RESET, '');
		$reset = strtolower($reset);
		
		return ($reset == 'true');
	}
	
	private function createSectionNode(CWI_XML_Traversal $xml, $parent_key, array $sibling_nodes) {
		
		$id = $this->getIdForNode($xml, $parent_key);
		$original = isset($sibling_nodes[$id]) ? $sibling_nodes[$id] : new AdminMenuItem(null, null, null, null, null, null, null, null, null, null);
		
		$title		= $this->getXmlStringParamValue($xml, 'title', $original->getTitle());
		$url		= $this->getXmlStringParamValue($xml, 'url', $original->getUrl());
		$description	= $this->getXmlStringParamValue($xml, 'description', $original->getDescription());
		$image		= $this->getXmlStringParamValue($xml, 'image', $original->getImage());
		$roles		= $this->getXmlArrayParamValue($xml, 'roles', $original->getRoles());
		$permissions	= $this->getXmlArrayParamValue($xml, 'permissions', $original->getPermissions());
		$new_window	= $this->getXmlStringParamValue($xml, 'newWindow', $original->getNewWindowAttributes());
		$is_enabled	= $this->getXmlBooleanParamValue($xml, 'enable', true, $original->isEnabled());
		
		$sortorder	= $this->getXmlStringParamValue($xml, 'sortorder');
		
		/**
		 * If $sortorder is not set then it will be automatically assigned
		 * If $sortorder == 'reset' then re-calculate the sort position to appear next after the previous menu item under the same parent
		 */
		if (null === $sortorder || $sortorder == self::SORTORDER_RESET) {
			if ($sortorder == self::SORTORDER_RESET || null === $original->getSortorder()) {
				$sortorder = $this->getNextSortorder($parent_key);
			} else {
				$sortorder = $original->getSortorder();
			}
		}
		
		$sortorder = intval($sortorder);
		
		return new AdminMenuItem($id, $title, $url, $description, $image, $roles, $permissions, $new_window, $is_enabled, $sortorder);
	}
	
	private function getIdForNode(CWI_XML_Traversal $xml, $parent_key) {
		
		$id = $this->getXmlStringParamValue($xml, 'id'); // See if an ID is already specified
		
		if (empty($id)) {
			$title = $this->getXmlStringParamValue($xml, 'title');
			$id = sprintf('%s::%s', $parent_key, $title);
		}
		
		return $id;
	}
	
	/**
	 * Retrieves an XML parameter as a string and processes any configuration values in the format %CONFIG_KEY%
	 * @param CWI_XML_Traversal $node
	 * @param $param_name
	 * @return mixed
	 */
	private function getXmlStringParamValue(CWI_XML_Traversal $node, $param_name, $default=null) {
		$value = $default;
		
		if ($node->getParam($param_name)) $value = $this->replaceConfigValues($node->getParam($param_name));
		
		return $value;
	}
	
	/**
	 * Retrieves an XML parameter as a list an return an array
	 * @param CWI_XML_Traversal $node
	 * @param $param_name
	 * @return array
	 */
	private function getXmlArrayParamValue(CWI_XML_Traversal $node, $param_name, $default=null) {
		$values = array();
		
		$value = $node->getParam($param_name);
		
		if ($value && !empty($value)) {
			$split = preg_split('/, */', $value);
			foreach($split as $val) {
				$values[] = $this->replaceConfigValues($val);
			}
		}
		
		return count($values) > 0 ? $values : $default;
	}
	
	/**
	 * Retrieves an XML parameter as a boolean
	 * @param CWI_XML_Traversal $node
	 * @param $param_name
	 * @return array
	 */
	private function getXmlBooleanParamValue(CWI_XML_Traversal $node, $param_name, $fallback_value, $default=null) {
		if (null !== $fallback_value && !is_bool($fallback_value)) throw new Exception('Unsupported fallback value.  Must be boolean');
		
		$value = $node->getParam($param_name, null);
		
		if (null === $value) return $default;
		else {
			$value = strtolower($value);
			if ($value == 'true') return true;
			else if ($value == 'false') return false;
		}
		
		return $fallback_value;
	}
	
	/**
	 * Retrieves an XML parameter as an integer
	 * @param CWI_XML_Traversal $node
	 * @param $param_name
	 * @return array
	 */
	private function getXmlIntegerParamValue(CWI_XML_Traversal $node, $param_name, $default_value=null) {
		$value = $node->getParam($param_name, null);
		
		if (null === $value) return $default_value;
		
		return intval($value);
	}
	
	private function replaceConfigValues($value) {
		
		if (preg_match_all('#%(.+?)%#', $value, $matches)) {
			for ($i=0; $i < count($matches[0]); $i++) {
				if ($configuration_value = ConfigurationManager::get($matches[1][$i])) {
					$value = str_replace($matches[0][$i], $configuration_value, $value);
				}
			}
		}
		
		return $value;
	}
	
	/**
	 * Reset a hiearchy back to an empty array
	 * @param $parent_key
	 */
	private function resetHierarchy($parent_key)
	{
		$this->hierarchy[$parent_key] = array();
	}
	
	/**
	 * Checks if the XML node has a param="true" node
	 * @param CWI_XML_Traversal $node
	 * @return bool
	 */
	
	/**
	 * Get the next sorting order for an item of a given parent key
	 * @param string $parent_key
	 */
	public function getNextSortorder($parent_key)
	{
		if (!isset($this->sortorders[$parent_key])) $this->sortorders[$parent_key] = 0;
		
		return $this->sortorders[$parent_key]++;
	}
	
}

class AdminMenuItem {
	private $id;
	private $title;
	private $url;
	private $description;
	private $image;
	private $roles = array();
	private $permissions = array();
	private $newWindowAttributes = '';
	private $enable = true;
	private $sortorder;
	
	/**
	 * AdminMenuItem constructor.
	 * @param String $id
	 * @param String $title
	 * @param String $url
	 * @param String $description
	 * @param String $image
	 * @param array $roles
	 * @param array $permissions
	 * @param bool $newWindowAttributes
	 * @param bool $enable
	 * @param int $sortorder
	 */
	public function __construct($id, $title, $url, $description, $image, $roles, $permissions, $new_window_attrs, $enable, $sortorder)
	{
		$this->id = $id;
		$this->title = $title;
		$this->url = $url;
		$this->description = $description;
		$this->image = $image;
		$this->roles = $roles;
		$this->permissions = $permissions;
		$this->newWindowAttributes = $new_window_attrs;
		$this->sortorder = $sortorder;
		
		if (is_bool($enable)) $this->enable = $enable;
	}
	
	/**
	 * @return String
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * @return String
	 */
	public function getTitle()
	{
		return $this->title;
	}
	
	/**
	 * @return String
	 */
	public function getUrl()
	{
		return $this->url;
	}
	
	/**
	 * @return String
	 */
	public function getDescription()
	{
		return $this->description;
	}
	
	/**
	 * @return String
	 */
	public function getImage()
	{
		return $this->image;
	}
	
	/**
	 * @return array
	 */
	public function getRoles()
	{
		return $this->roles;
	}
	
	/**
	 * @return array
	 */
	public function getPermissions()
	{
		return $this->permissions;
	}
	
	/**
	 * @return bool
	 */
	public function getNewWindowAttributes()
	{
		return $this->newWindowAttributes;
	}
	
	/**
	 * @return bool
	 */
	public function shouldOpenNewWindow()
	{
		return !empty($this->newWindowAttributes);
	}
	
	/**
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->enable;
	}
	
	/**
	 * @return int
	 */
	public function getSortorder()
	{
		return $this->sortorder;
	}
	
}