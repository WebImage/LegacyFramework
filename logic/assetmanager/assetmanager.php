<?php

FrameworkManager::loadLibrary('html.form.fileupload');
/**
 * 01/20/2010	(Robert Jones) Added getCategoryByName($name) to retrieve any categories by name
 * 01/19/2013	(Robert Jones) Replaced "category" with "folder" in all methods to make asset db organization more in line with file system storage
 */
class AssetManagerLogic {
	
	const ROOT_FOLDER_ID = 0;
	
	// Assets
	public static function getAssetById($id) {
		FrameworkManager::loadDAO('asset');
		$asset_dao = new AssetDAO();
		return $asset_dao->getAssetById($id);
	}
	
	public static function getAllAssets() {
		FrameworkManager::loadDAO('asset');
		$asset_dao = new AssetDAO();
		return $asset_dao->loadAll();
	}
	
	public static function getBaseSearch() {
		
		FrameworkManager::loadDAO('asset');
		$dao = new AssetDAO();
		return $dao->getBaseSearch();
		
	}
	
	public function addSearchFolder(DAOSearch $search, $folder_id) {
		
		if ($folder_id == 0) { // Assume that any asset with a folder of zero or null belongs to a root folder
			
			$folder_group = new DAOSearchOrGroup();
			$search->addSearchField($folder_group);
			
			$search_folder = new DAOSearchField('assets', 'folder_id', $folder_id);
			$folder_group->addSearchField($search_folder);
			
			$search_folder = new DAOSearchFieldNull('assets', 'folder_id');
			$folder_group->addSearchField($search_folder);
			
			$search_folder = new DAOSearchFieldLength('assets', 'folder_id', 0);
			$folder_group->addSearchField($search_folder);
			
		} else {
			
			$search_folder = new DAOSearchField('assets', 'folder_id', $folder_id);
			$search->addSearchField($search_folder);
			
		}
	}
	/**
	 * @param DAOSearch $search a base search objected, preferrably the object returned from AssetManagerLogic::getBaseSearch()
	 * @param array $variation_keys An indexed or associative array. An indexed array will cause the function to generate a db friendly column name base for the JOINed asset record/variation.  An associative array (in the format array('variation'=>'alias')) allows a table alias to be specified
	 * @return array An associative array with the variation key as the key and a table alias base as the value, e.g. array('my-key'=>'my_key')
	 **/
	public function addSearchVariations(DAOSearch $search, array $variation_keys) {
		
		$variation_table_aliases = array();
		
		foreach($variation_keys as $ix=>$key) {
			
			if (is_numeric($ix)) {
				$table_alias = 'variation_' . preg_replace('#[^a-z0-9]+#', '_', $key);
			} else {
				$table_alias = $key;
				$key = $ix;
			}
			$variation_table_aliases[$key] = $table_alias;
			
			$search->addJoin( new DAOJoin(
				array('assets', $table_alias), 
				DAOJoin::JOIN_LEFT, 
				array(
					$table_alias . '.parent_id'=>'assets.id',
					$table_alias . '.variation_key' => "'" . $key . "'",
					$table_alias . '.enable' => 1
				),
				array(
					'caption' => $table_alias . '_caption',
					'category_id' => $table_alias . '_category_id',
					'description' => $table_alias . '_description',
					'enable' => $table_alias . '_enable',
					'file_src' => $table_alias . '_file_src',
					'id' => $table_alias . '_id',
					'manageable' => $table_alias . '_manageable',
					'original_file_name' => $table_alias . '_original_file_name',
					'properties' => $table_alias . '_properties',
					/*'variation_key' => $table_alias . '_variation_key',*/
					'version' => $table_alias . '_version',
					/*'parent_id' => $table_alias . '_folder_parent_id',*/
				)
			) );
			
		}
		return $variation_table_aliases;
	}
	
	public static function searchManageableAssets($folder_id=null, $asset_type_id=null, $width_min=null, $width_max=null, $height_min=null, $height_max=null, $file_src=null, $current_page=null, $results_per_page=null) {
		
		FrameworkManager::loadDAO('asset');
		$asset_dao = new AssetDAO();
		return $asset_dao->searchManageableAssets($folder_id, $asset_type_id, $width_min, $width_max, $height_min, $height_max, $file_src, $current_page, $results_per_page);

	}
	
	/**
	 * @param AssetStruct The data struct for the asset
	 **/
	public static function save($asset_struct) {
		FrameworkManager::loadDAO('asset');
		$asset_dao = new AssetDAO();
		if (strlen($asset_struct->manageable) == 0) $asset_struct->manageable = 0;
		return $asset_dao->save($asset_struct);
	}
	
	/**
	 * @param CWI_ASSETS_Asset an asset object to be saved
	 */
	public static function saveAsset($asset) {
		FrameworkManager::loadStruct('asset');
		
		$asset_struct = new AssetStruct();
		/*
		$asset_struct->asset_type_id
		$asset_struct->caption
		$asset_struct->folder_id
		$asset_struct->config
		$asset_struct->created
		$asset_struct->created_by
		$asset_struct->description
		$asset_struct->display_date
		$asset_struct->file_src
		#$asset_struct->height
		$asset_struct->id
		$asset_struct->manageable
		$asset_struct->options
		$asset_struct->original_file_name
		$asset_struct->type_id
		$asset_struct->updated
		$asset_struct->updated_by
		#$asset_struct->width
		*/
	}
	
	public static function isUploadFieldAvailable($field_name, &$reason=null) {
		
		if (isset($_FILES[$field_name])) {
			
			if (is_array($_FILES[$field_name])) {
				
				if (isset($_FILES[$field_name]['name']) && !empty($_FILES[$field_name]['name'])) {
					
					return true;
					
				} else {
					
					$reason = '$_FILES[' . $field_name . '][name] not set or empty';
					return false;
				}
				
			} else {
				
				$reason = '$_FILES[' . $field_name . '] not an array';
				return false;
				
			}
			
		} else {
			
			$reason = '$_FILES[' . $field_name . '] not found';
			return false;
			
		}
	}

	/**
	 * Prepares an AssetStruct object from an uploaded file
	 * @param string $field_name
	 * @param string $transfer_to_path
	 * @param string $asset_id
	 * @param boolean $manageable Whether an asset is manageable via the asset manager
	 * @param string $error_message an optional parameter that can be passed back to retrieve an error
	 * @return CWI_ASSETS_Asset
	 **/
	private static function prepareUploadForSave($field_name, $transfer_to_path, $asset_id=null, $manageable=false, &$error_message=null) {
		
		// Create new FileUpload based on field_name
		$file_upload = new CWI_HTML_FORM_FileUpload($field_name, $transfer_to_path);
		
		// If asset id is set, build struct
		if (!is_null($asset_id) && !empty($asset_id)) {
			
			if (!$asset_struct = AssetManagerLogic::getAssetById($asset_id)) {
				
				FrameworkManager::loadStruct('asset');
				$asset_struct = new AssetStruct();
				
			}
			
		// Otherwise build default struct
		} else {
			FrameworkManager::loadStruct('asset');
			$asset_struct = new AssetStruct();
		}
		
		// Handle the actual upload and populate appropriate fields
		$file_upload->handleUpload();
		#if (!$file_upload->isFile()) {
		
		// Check for errors
		if ($file_upload->isError()) {
			$error_message = $file_upload->getError();
			return false;
		}
		$asset_struct->manageable = ($manageable ? 1 : 0);
		
		return array($asset_struct, $file_upload);
	}
	/**
	 * Handles a file upload based on a field name and creates a CWI_ASSETS_Asset
	 * @param string $field_name
	 * @param string $transfer_to_path
	 * @param string $asset_id
	 * @param boolean $manageable Whether an asset is manageable via the asset manager
	 * @param string $error_message an optional parameter that can be passed back to retrieve an error
	 * @return CWI_ASSETS_Asset
	 **/
	public static function handleUpload($field_name, $transfer_to_path, $asset_id=null, $manageable=false, &$error_message=null) {
		
		if ($return_val = AssetManagerLogic::prepareUploadForSave($field_name, $transfer_to_path, $asset_id, $manageable, $error_message)) {
			
			list($asset_struct, $file_upload) = $return_val;
			// Build the CWI_
			$asset = AssetManagerLogic::saveUpload($file_upload, $asset_struct);
			return $asset;
			
		} else return false;
	}
	/**
	 * Convenience method for working with handleUpload(), but using folder id to lookup the actual folder
	 **/
	public static function handleUploadToFolder($field_name, $folder_id, $asset_id, $manageable=false, &$error_message) {
		
		if ($folder_id == AssetManagerLogic::ROOT_FOLDER_ID || $asset_folder = AssetManagerLogic::getFolderById($folder_id)) {
			
			if ($folder_id == AssetManagerLogic::ROOT_FOLDER_ID) {
				$upload_folder = ConfigurationManager::get('DIR_FS_ASSETS');
			} else {
				$upload_folder = ConfigurationManager::get('DIR_FS_ASSETS') . substr($asset_folder->folder, 1);
			}
			
			if ($return_val = AssetManagerLogic::prepareUploadForSave($field_name, $upload_folder, $asset_id, $manageable, $error_message)) {
				
				list($asset_struct, $file_upload) = $return_val;
				
				if (!is_null($folder_id)) {
					$asset_struct->folder_id = $folder_id;
				}
				// Build the CWI_
				$asset = AssetManagerLogic::saveUpload($file_upload, $asset_struct);
				return $asset;
				
			} else return false;
		
		} else {
			
			$error_message = 'Invalid asset folder specified: ' . $folder_id;
			return false;
			
		}
		
	}
	
	/**
	 * @deprecated
	 **/
	public static function handleUploadToCategory($field_name, $category_id, $asset_id, $manageable=false, &$error_message) {
		error_log('Using deprecated version of AssetManagerLogic::handleUploadToCategory().  Use AssetManagerLogic::handleUploadToFolder()');
		return self::handleUploadToFolder($field_name, $category_id, $asset_id, $manageable, $error_message);
	}
	
	/**
	 * Takes an upload object and saves it
	 * @param CWI_HTML_FORM_FileUpload
	 * @param AssetStruct
	 * @param string a string to prepend the file name with
	 **/
	public static function saveUpload($upload, $asset_struct, $prepend='') {
		$rename_file = $prepend;
		$rename_file .= preg_replace('/[^a-z0-9_\-]+/i', '-', $upload->getFileName());
		/*
		$new_image = (empty($asset_struct->id));
		if ($new_image) $asset_struct = AssetManagerLogic::save($asset_struct); // Added $asset_struct = on 02/17/2010 - not sure why it wasn't there before
		
		$rename_file = $prepend;
		
		$rename_file .= preg_replace('/[^a-z0-9\-]+/i', '-', $upload->getFileName());
		$rename_file .= '-i' . $asset_struct->id;
		*/
		// Rename the file name
		$upload->renameFile($rename_file);
		
		// Setup asset struct based on upload properites
		$asset_struct->file_src = $upload->getWSPath();
		$asset_struct->original_file_name = $upload->getFullFileName();
		
		// Save AssetStruct
		#$asset_struct = AssetManagerLogic::save($asset_struct);
		
		// Load factory to create CWI_ASSETS_Asset based on struct
		FrameworkManager::loadLibrary('assets.assetfactory');
		
		$asset = CWI_ASSETS_AssetFactory::createAssetFromStruct($asset_struct);

		$asset->populatePropertiesFromUpload($upload);

		if (!empty($asset_struct->id)) { // Make sure the image has been saved before trying to save attached parameters
			/*
			$properties = $asset->getProperties();
			
			while ($property = $properties->getNext()) {
				AssetManagerLogic::setAssetParameter($asset_struct->id, $property->getKey(), $property->getDefinition());
			}
			*/
			// Save again with any updated properties
			#AssetManagerLogic::saveAsset($asset);
		}
		
		// Serialize properties
		$asset_struct->properties = $asset->getPropertiesRaw()->toString();
/*ob_start();
print_r($asset);
print_r($asset_struct);
print_r($asset->getPropertiesRaw());

mail('rjones@corporatewebimage.com', 'Asset', ob_get_contents());
ob_end_clean();
*/
		$asset_struct = AssetManagerLogic::save($asset_struct);
		// Update Asset ID for new saves
		$asset->setId($asset_struct->id);
		
		return $asset;
	}
	
	public static function getSpaceUsed() {
		$dir = ConfigurationManager::get('DIR_FS_ASSETS');
		if (!empty($dir) && file_exists($dir)) {
			$output = shell_exec('du -s ' . $dir);
			if (preg_match('#^([0-9]+)#', $output, $matches)) {
				return $matches[1];
			}
		}
		return -1;
	}
	
	public static function delete($asset_id) {
		FrameworkManager::loadDAO('asset');
		$asset_dao = new AssetDAO();
		if ($asset = $asset_dao->load($asset_id)) {
			$file_location = ConfigurationManager::get('DIR_FS_HOME') . substr($asset->file_src, 1);
			@unlink($file_location);
			
			AssetManagerLogic::deleteAssetParametersByAssetId($asset_id);
			
			return $asset_dao->delete($asset_id);
		} else {
			return false;
		}
	}
	
	public static function getAllFolders() {
		FrameworkManager::loadDAO('assetfolder');
		$folder_dao = new AssetFolderDAO();
		return $folder_dao->loadAll();
	}
	/**
	 * @deprecated
	 **/
	public static function getAllCategories() {
		error_log('Using deprecated version of AssetManagerLogic::getAllCategories().  Use AssetManagerLogic::getAllFolders()');
		return self::getAllFolders();
	}
	
	public static function getFolders() {
		FrameworkManager::loadDAO('assetfolder');
		$folder_dao = new AssetFolderDAO();
		return $folder_dao->getFolders();
	}
	/**
	 * @deprecated
	 **/
	public static function getCategories() {
		error_log('Using deprecated version of AssetManagerLogic::getCategories().  Use AssetManagerLogic::getFolders()');
		return self::getFolders();
	}
	
	public static function createFolderIfNotAvailable($name) {
		if (!$folder = self::getFolderByName($name)) {
			FrameworkManager::loadStruct('assetfolder');
			$folder = new AssetFolderStruct();
			$folder->name = $name;
			self::saveFolder($folder);
		}
		return $folder;
	}
	/**
	 * @deprecated
	 **/
	public static function createCategoryIfNotAvailable($name) {
		error_log('Using deprecated version of AssetManagerLogic::createCategoryIfNotAvailable().  Use AssetManagerLogic::createFolderIfNotAvailable()');
		return self::createFolderIfNotAvailable($name);
	}
	
	
	public static function saveFolder($asset_folder_struct) {
		FrameworkManager::loadDAO('assetfolder');
		$folder_dao = new AssetFolderDAO();
		
		// Removed on 12/12/2008 because of problems created when SAFE_MODE was in effect
		if (empty($asset_folder_struct->folder)) {
			
			// Only create directory if it hasn't already been created
			
			$folder_name = strtolower($asset_folder_struct->name); // Lowercase
			$folder_name = preg_replace('/[^a-z0-9\-]+/', '-', $folder_name) . '/'; // System friendly folder name
			$system_folder = ConfigurationManager::get('DIR_FS_ASSETS') . $folder_name;

			if (!file_exists($system_folder)) {
				
				if (mkdir($system_folder, 0777, true)) { // Attempt to create the folder
					$asset_folder_struct->folder = '/' . $folder_name; // Only save the folder name if the folder creation was successfull.
				}
			}
			
		}
		
		#$test = $folder_dao->save($asset_folder_struct);
		return $folder_dao->save($asset_folder_struct);
	}
	
	/**
	 * @deprecated 
	 **/
	public static function saveCategory($asset_category_struct) {
		error_log('Using deprecated version of AssetManagerLogic::saveCategory().  Use AssetManagerLogic::saveFolder()');
		return self::saveFolder($asset_category_struct);
	}
	
	public static function getFolderById($folder_id) {
		FrameworkManager::loadDAO('assetfolder');
		$folder_dao = new AssetFolderDAO();
		return $folder_dao->load($folder_id);
	}
	/**
	 * @deprecated
	 **/
	public static function getCategoryById($category_id) {
		error_log('Using deprecated version of AssetManagerLogic::getCategoryById().  Use AssetManagerLogic::getFolderById()');
		return self::getFolderById($category_id);
	}
	
	public static function deleteFolder($folder_id) {
		FrameworkManager::loadDAO('assetfolder');
		$folder_dao = new AssetFolderDAO();
		return $folder_dao->delete($folder_id);
	}
	/**
	 * @deprecated
	 **/
	public static function deleteCategory($category_id) {
		error_log('Using deprecated version of AssetManagerLogic::deleteCategory().  Use AssetManagerLogic::deleteFolder()');
		return self::deleteFolder($category_id);
	}
	
	public static function getFolderByName($name) {
		FrameworkManager::loadDAO('assetfolder');
		$folder_dao = new AssetFolderDAO();
		return $folder_dao->getFolderByName($name);
	}
	/**
	 * @deprecated
	 **/
	public static function getCategoryByName($name) {
		error_log('Using deprecated version of AssetManagerLogic::getCategoryByName().  Use AssetManagerLogic::getFolderByName()');
		return self::getFolderByName($name);
	}
	
	// Asset Types
	public static function getAllAssetTypes() {
		FrameworkManager::loadDAO('assettype');
		$type_dao = new AssetTypeDAO();
		return $type_dao->loadAll();
	}
	
	public static function getAssetTypeByName($name) {
		FrameworkManager::loadDAO('assettype');
		$type_dao = new AssetTypeDAO();
		return $type_dao->getAssetTypeByName($name);
	}
	
	public static function getAssetTypeFromFileName($file_name) {
		$decimal_point = strrpos($file_name, '.');
		$file_extension = substr($file_name, $decimal_point + 1, strlen($file_name) - $decimal_point + 1);

		$asset_types = AssetManagerLogic::getAllAssetTypes();
		while ($type = $asset_types->getNext()) {
			$extensions = explode(',', $type->extensions);
			foreach($extensions as $extension) {
				$extension = trim($extension);
				if ($file_extension == $extension) {
					return $type;
				}
			}
		}
		// If we have gotten this far then we have failed to locate the correct file extension
		return false;
	}
	
	// Asset Parameters
	public static function getAssetParametersByAssetId($asset_id) {
		FrameworkManager::loadDAO('assetparameter');
		$asset_parameter_dao = new AssetParameterDAO();
		return $asset_parameter_dao->getAssetParametersByAssetId($asset_id);
	}
	public static function getAssetParameter($asset_id, $parameter) {
		FrameworkManager::loadDAO('assetparameter');
		$asset_parameter_dao = new AssetParameterDAO();
		return $asset_parameter_dao->getAssetParameter($asset_id, $parameter);
	}
	public static function getAssetParameterValue($asset_id, $parameter) {
		FrameworkManager::loadDAO('assetparameter');
		if ($asset_parameter = AssetManagerLogic::getAssetParameter($asset_id, $parameter)) {
			return $asset_parameter->value;
		} else return false;
	}
	public static function setAssetParameter($asset_id, $parameter, $value) {
		FrameworkManager::loadDAO('assetparameter');
		$asset_parameter_dao = new AssetParameterDAO();
		return $asset_parameter_dao->setAssetParameter($asset_id, $parameter, $value);
	}
	public static function deleteAssetParametersByAssetId($asset_id) {
		FrameworkManager::loadDAO('assetparameter');
		$asset_parameter_dao = new AssetParameterDAO();
		return $asset_parameter_dao->deleteAssetParametersByAssetId($asset_id);
	}
}

?>