<?php

FrameworkManager::loadDAO('permission');
class PermissionLogic {
	public static function getPermissions() {
		$permission_dao = new PermissionDAO();
		return $permission_dao->getPermissions();
	}
	
	public static function getPermission($permission) {
		$permission_dao = new PermissionDAO();
		return $permission_dao->load($permission);
	}
	
	public static function permissionExists($permission) {
		return (self::getPermission($permission) ? true : false);
	}
	
	public static function save($permission_struct) {
		$permission_dao = new PermissionDAO();
		return $permission_dao->save($permission_struct);
	}
	public static function delete($permission) {
		$permission_dao = new PermissionDAO();
		return $permission_dao->delete($permission);
	}
	
	/**
	 * Creates a new permission
	 *
	 * @param string $permission The name of the permission, e.g. Admin.Pages
	 * @access private
	 * @return PermissionStruct
	 **/
	public static function createPermission($permission, $description='') {
		
		$permission_dao = new PermissionDAO();
		
		if ($permission_struct = self::getPermission($permission)) {
			
			$permission_struct->description = $description;
			return $permission_dao->save($permission_struct);
			
		} else {
			
			FrameworkManager::loadStruct('permission');
			
			$struct = new PermissionStruct();
			$struct->permission = $permission;
			$struct->description = $description;
			
			return $permission_dao->save($struct);
		}		
	}
	
	/**
	 * Make sure that all required permissions are installed
	 *
	 * @return void
	 **/
	public static function installMissingPermissions() {
		
		$permission_dao = new PermissionDAO();
		$permission_dao->setCacheResults(false);
		
		$existing_permissions = array();
		$rs_permissions = $permission_dao->getPermissions();
		
		while($permission_struct = $rs_permissions->getNext()) {
			array_push($existing_permissions, $permission_struct->permission);
		}
		
		$xml_config = ConfigurationManager::getConfig();
		
		if ($xml_add_permissions = $xml_config->getPath('roleManager/permissions/add')) {
			
			foreach($xml_add_permissions as $xml_add) {
				
				$permission = $xml_add->getParam('permission');
				
				if (!empty($permission) && !in_array($permission, $existing_permissions)) {
					self::createPermission($permission);
				}
				
				
			}
			
		}
	}
}

?>