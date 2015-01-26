<?php
/**
 * Data structure for Assets
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */
class AssetStruct {
	#var $asset_type_id, $caption, $category_id, $config, $created, $created_by, $description, $display_date, $file_src, $folder_id, $id, $manageable, $options, $original_file_name, $properties, $type_id, $updated, $updated_by;
	var $caption, $category_id, $created, $created_by, $description, $enable, $file_src, $folder_id, $id, $manageable, $original_file_name, $parent_id, $properties, $updated, $updated_by, $variation_key, $version;

	#var $height, $width; // <-- Need to be deprecated (moved to parameters/options)
	#var $category_name; // AssetCategory
	var $folder_path, $folder_name, $folder_parent_id; // AssetFolder
	var $asset_type_name; // AssetType
}

?>