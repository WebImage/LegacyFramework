<?php

function permission_to_form_var($permission) {
	return str_replace('.', '_', $permission);
}

if (!$role_id = Page::get('roleid')) Page::redirect('index.html');

FrameworkManager::loadLibrary('formbuilder');
FrameworkManager::loadLogic('role');
FrameworkManager::loadLogic('permission');

// Make sure all required ("default") permissions are installed from config file
FrameworkManager::loadStruct('rolepermission');

PermissionLogic::installMissingPermissions();

if (!$role = RoleLogic::getRoleById($role_id)) Page::redirect('index.html?error=NO+ROLE');
$permissions = RoleLogic::getPermissionsByRoleId($role_id);

if (Page::isPostBack()) {
	RoleLogic::deleteRolePermissions($role_id);
	
	while ($permission = $permissions->getNext()) {
		$field_name_base = permission_to_form_var($permission->permission);
		
		// Build field names
		$can_create_field	= $field_name_base . '__create';
		$can_read_field		= $field_name_base . '__read';
		$can_update_field	= $field_name_base . '__update';
		$can_delete_field	= $field_name_base . '__delete';
		
		// Retrieve field values
		$can_create		= Page::get($can_create_field, 0);
		$can_read		= Page::get($can_read_field, 0);
		$can_update		= Page::get($can_update_field, 0);
		$can_delete		= Page::get($can_delete_field, 0);
		
		if ($can_create == 1 || $can_read == 1 || $can_update == 1 || $can_delete == 1) {
			$role_permission_struct = new RolePermissionStruct();
			$role_permission_struct->allow_create	= $can_create;
			$role_permission_struct->allow_read	= $can_read;
			$role_permission_struct->allow_update	= $can_update;
			$role_permission_struct->allow_delete	= $can_delete;
			$role_permission_struct->role_id	= $role_id;
			$role_permission_struct->permission	= $permission->permission;
			RoleLogic::saveRolePermission($role_permission_struct);
		}
	}
	Page::redirect('index.html');
}

/**
 * Setup display
 */
$rs_permissions = new ResultSet();
while ($permission = $permissions->getNext()) {
	$row = new stdClass();
	$field_name_base = permission_to_form_var($permission->permission);
	
	$row->permission = $permission->permission;
	
	if (Page::isPostBack()) {
		$can_create_checked	= (Page::get($field_name_base . '__create')) ? ' checked="true"' : '';
		$can_read_checked	= (Page::get($field_name_base . '__read')) ? ' checked="true"' : '';
		$can_update_checked	= (Page::get($field_name_base . '__update')) ? ' checked="true"' : '';
		$can_delete_checked	= (Page::get($field_name_base . '__delete')) ? ' checked="true"' : '';
	} else {
		$can_create_checked	= (empty($permission->allow_create)) ? '' : ' checked="true"';
		$can_read_checked	= (empty($permission->allow_read)) ? '' : ' checked="true"';
		$can_update_checked	= (empty($permission->allow_update)) ? '' : ' checked="true"';
		$can_delete_checked	= (empty($permission->allow_delete)) ? '' : ' checked="true"';
	}
	
	$row->can_create	= '<input type="checkbox" name="' . $field_name_base . '__create" value="1" ' . $can_create_checked . ' />';
	$row->can_read		= '<input type="checkbox" name="' . $field_name_base . '__read" value="1" ' . $can_read_checked . ' />';
	$row->can_update	= '<input type="checkbox" name="' . $field_name_base . '__update" value="1" ' . $can_update_checked . ' />';
	$row->can_delete	= '<input type="checkbox" name="' . $field_name_base . '__delete" value="1" ' . $can_delete_checked . ' />';
	
	$rs_permissions->add($row);
}

$dg_permissions = Page::getControlById('dg_permissions');
$dg_permissions->setData($rs_permissions);

?>