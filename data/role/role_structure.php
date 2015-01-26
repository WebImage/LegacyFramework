<?php
/**
 * Data structure for Roles 
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */
/**
 * 02/10/2010	(Robert Jones) Added $is_primary and $start_page
 */
class RoleStruct {
	var $created, $created_by, $description, $id, $name, $start_page, $updated, $updated_by, $visible;
	var $is_primary; // MembershipsRoles
}

?>