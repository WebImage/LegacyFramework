<?php

class CWI_PLUGIN_PluginRequirementCollection extends Collection {
	function add($requirement) {
		if (is_a($requirement, 'CWI_PLUGIN_PluginRequirement')) {
			parent::add($requirement);
		}
	}
	function getAllByClass($class_name) {
		$requirements = new CWI_PLUGIN_PluginRequirementCollection();
		while ($plugin = $this->getNext()) {
			if (is_a($plugin, $class_name)) $requirements->add($plugin);
		}
		return $requirements;
	}
}

?>