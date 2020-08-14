<?php

FrameworkManager::loadDAO('configvalue');

class ConfigValueLogic {
	
	public static function getConfigValues() {
		$dao = new ConfigValueDAO();
		return $dao->getConfigValues();
	}

	public static function getConfigValue($group_key, $field) {
		
		$dao = new ConfigValueDAO();
		return $dao->getConfigValue($group_key, $field);
		
	}
		
	public static function setGroupConfigValue($group_key, $field, $value, $locked=false, $plugin_id=0) {
		
		if ($struct = ConfigValueLogic::getConfigValue($group_key, $field)) {
			
			// Only update values if the config is not locked
			if ($struct->locked != 1) {
				$struct->value = $value;
				$struct->locked = is_bool($locked) ? ($locked === true ? 1 : 0) : $locked;

				$struct = ConfigValueLogic::save($struct);
			}

		} else {
			
			$struct = new ConfigValueStruct();
			$struct->field = $field;
			$struct->group_key = $group_key;
			$struct->locked = ($locked ? 1 : 0);
			$struct->plugin_id = $plugin_id;
			$struct->value = $value;
			
			$struct = ConfigValueLogic::create($struct);
						
		}
		
		return $struct;
	}
	
	public static function save($config_value_struct) {
		
		$dao = new ConfigValueDAO();
		return $dao->save($config_value_struct);
		
	}
	
	public static function create($config_value_struct) {
		
		$dao = new ConfigValueDAO();
		$dao->setForceInsert(true);
		return $dao->save($config_value_struct);
		
	}
}

?>