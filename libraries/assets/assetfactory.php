<?php
FrameworkManager::loadManager('asset');
FrameworkManager::loadLibrary('assets.assetfolder');
FrameworkManager::loadLogic('assetmanager');

class CWI_ASSETS_AssetFactory {
	
	private static function createAssetFromFilePath($file_path) {
		$dot_pos = strrpos($file_path, '.');
		
		if ($dot_pos > 0) {
			$extension = substr($file_path, $dot_pos+1);
			if ($asset_file_type = CWI_MANAGER_AssetManager::getAssetFileTypeByExtension($extension)) {
				return $asset_file_type;
			} else return false;
		} else return false;
	}
	public static function createAssetFromStruct($asset_struct) {
		
		if (is_object($asset_struct) && is_a($asset_struct, 'AssetStruct')) {
			
			$check_file_name = $asset_struct->original_file_name;
			if (empty($check_file_name)) $check_file_name = $asset_struct->file_src;
			
			if ($asset_file_type = CWI_ASSETS_AssetFactory::createAssetFromFilePath($check_file_name)) {
				
				$class_name = $asset_file_type->getClassName();

				// Load class if it is not defined
				if (!class_exists($class_name)) include_once( PathManager::translate($asset_file_type->getClassFile()) );
				// If it is still not defined, return false
				if (!class_exists($class_name)) return false;

				$asset = new $class_name;
				$asset->setId($asset_struct->id);
				
				#$rs_properties = AssetManagerLogic::getAssetParametersByAssetId($asset_struct->id);
				#$extracted_properties = ConfigDictionary::createFromString($asset->properties);
				#echo '<pre>';
				#print_r($asset);
				#while ($property_struct = $rs_properties->getNext()) {
				#while ($extracted_property = $extracted_properties->getNext()) {
				#	#$asset->setProperty($property_struct->parameter, $property_struct->value);
				#	$asset->setProperty($extracted_property->getKey(), $extracted_property->getDefinition());
				#}
				
				$asset->setProperties( ConfigDictionary::createFromString($asset_struct->properties) );
				$asset->setWebFilePath($asset_struct->file_src);
				$asset->setSystemFilePath(str_replace(ConfigurationManager::get('DIR_WS_ASSETS_WMS'), ConfigurationManager::get('DIR_FS_ASSETS_WMS'), $asset_struct->file_src));
				$asset->setType($asset_file_type);
				
				if (empty($asset_struct->folder_parent_id)) { // Root folder doesn't have a definition
					$asset_struct->folder_parent_id = null;
					$asset_struct->folder_path = '/';
				}
					
				$folder = new CWI_ASSETS_AssetFolder();
				$folder->setFolder($asset_struct->folder_path);
				$folder->setId($asset_struct->folder_id);
				$folder->setName($asset_struct->folder_name);
				$folder->setParentId($asset_struct->folder_parent_id);
				$asset->setFolder($folder);
						
				$asset->setCaption($asset_struct->caption);
				$asset->setCategoryId($asset_struct->category_id);
				$asset->setDescription($asset_struct->description);
				$asset->setEnable($asset_struct->enable);
				$asset->isManageable(($asset_struct->manageable == 1 ? true : false));
				$asset->setOriginalFileName($asset_struct->original_file_name);
				$asset->setParentId($asset_struct->parent_id);
				$asset->setVariationKey($asset_struct->variation_key);
				$asset->setVersion($asset_struct->version);

#echo '<pre>';print_r($asset_struct);echo '</pre>';

				return $asset;
				
			} else return false;
			
		} else return false;
	}
	public static function createAssetFromId($asset_id) {
		$asset_struct = AssetManagerLogic::getAssetById($asset_id); // Being lazy about type checking since it is done in createAssetFromStruct($asset_struct)
		return CWI_ASSETS_AssetFactory::createAssetFromStruct($asset_struct);
	}
}

?>