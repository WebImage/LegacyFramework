<?php

/**
 * Helper style class to retrieve database results for parameters
 **/

FrameworkManager::loadDAO('parameter');

class ParameterLogic {
	
	public static function _sortParametersByGroup($a, $b) {
		
		if ($a->group_order < $b->group_order) return -1;
		else if ($a->group_order> $b->group_order) return 1;
		else { // Same group
			if ($a->sortorder < $b->sortorder) return -1;
			else if ($a->sortorder > $b->sortorder) return 1;
			#else return 0;
			else {
				$grp_cmp = strcmp($a->group, $b->group);
				if ($grp_cmp != 0) return $grp_cmp;
				else return strcmp($a->name, $b->name);
			}
		}
		
	}
	
	public static function getParametersByType($type) {
		$dao = new ParameterDAO();
		$rs_parameters = $dao->getParametersByType($type);
		
		$groups = array();
		// Setup groups
		while ($parameter_struct = $rs_parameters->getNext()) {
			if (empty($parameter_struct->group)) $parameter_struct->group = 'General';
		
			// Keep track of groups and mark the lowest sortorder for each group so that groups take precendence in sorting, then sortorder
			if (!isset($groups[$parameter_struct->group])) $groups[$parameter_struct->group] = $parameter_struct->sortorder;
			else {
				if ($parameter_struct->sortorder < $groups[$parameter_struct->group]) $groups[$parameter_struct->group] = $parameter_struct->sortorder;
			}
		}
		
		// Designate group order for each parameter
		while ($parameter_struct = $rs_parameters->getNext()) {
			$parameter_struct->group_order = $groups[$parameter_struct->group];
		}
		// Sort parameters by group
		usort($rs_parameters->getAll(), array('ParameterLogic', '_sortParametersByGroup'));
		
		return $rs_parameters;
	}
	
	public static function getParameterByTypeAndKey($type, $key) {
		$dao = new ParameterDAO();
		return $dao->getParameterByTypeAndKey($type, $key);
	}
	
	public static function save($parameter_struct) {

		// Make sure parameter is assigned a key
		if (empty($parameter_struct->key)) {
		
			FrameworkManager::loadLibrary('string.helper');
			$parameter_struct->key = CWI_STRING_Helper::strToMachineKey($parameter_struct->name);
		
		}
		
		$dao = new ParameterDAO();
		if (!self::getParameterByTypeAndKey($parameter_struct->type, $parameter_struct->key)) {
			$dao->setForceInsert(true);
		}
		
		$dao->save($parameter_struct);
	}
	
	public static function deleteParameter($type, $key) {
		$dao = new ParameterDAO();
		$dao->deleteParameter($type, $key);
	}
	
}

?>