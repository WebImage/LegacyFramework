<?php

FrameworkManager::loadLibrary('html.form.imagefileupload');

FrameworkManager::loadLogic('assetmanager');

$asset = Page::getStruct('asset');

if (!Page::isPostBack() && $folder_id = Page::get('folderid')) {
	$asset->folder_id = $folder_id;
} else if (!empty($asset->folder_id)) {
	Page::set('folderid', $asset->folder_id);
}

// gets and sets url paths
include('global.php');

$rs_folders = AssetManagerLogic::getAllFolders();

/*define('ASSETMANAGER_AUTORENAME', true);
define('ASSETMANAGER_', false);

class AssetManager {
	var $m_assetLocation;
	function __construct($file_system_path) {
		$this->m_assetLocation = $file_system_path;
	}

//http://www.php.net/imagecopyresized
}
*/
// Check if assets folder exists
if (!file_exists(ConfigurationManager::get('DIR_FS_ASSETS'))) {
	
	// Attempt to create the folder
	if (@mkdir( ConfigurationManager::get('DIR_FS_ASSETS'), 0755, true )) ErrorManager::addError('The upload directory on the server is missing.  You will NOT be able to upload any new files.  Please contact your support rep and let them know about this error.');
	
	
// If so, make sure it is writable
} 

if (!ErrorManager::anyDisplayErrors() && !is_writable(ConfigurationManager::get('DIR_FS_ASSETS'))) {
	
	// Attempt to make directory writable
	if (!@chmod( ConfigurationManager::get('DIR_FS_ASSETS'), 0755)) ErrorManager::addError('There was an internal error that will prevent you from uploading image.  Please contact your support rep and let them your assets folder is not accessible.');
	
}

// Need at least one folder to upload to
if ($rs_folders->getCount() == 0) {
	ErrorManager::addError('You need at least one folder to upload to.  Please click the "Folders" tab and add a folder first.');
}

if (Page::isPostBack()) {
	
	#if (empty($asset->asset_type_id)) ErrorManager::addError('Please select an asset type.');
	if (empty($asset->folder_id)) ErrorManager::addError('A folder must be selected before you can upload a file.');
				
	if (!ErrorManager::anyDisplayErrors()) {
		$upload_folder = ConfigurationManager::get('DIR_FS_ASSETS');
		
		// Get selected folder
		$folder = AssetManagerLogic::getFolderById($asset->folder_id);
		
		// If folder's folder is set then append that to the path
		if (!empty($folder->folder)) {
			$upload_folder .= substr($folder->folder, 1);
		}
		
		$upload = new CWI_HTML_FORM_ImageFileUpload('assetupload', $upload_folder);
		
		if ($upload->handleUpload()) {

			$asset->file_src	= $upload->getWSPath();
			$asset->width		= $upload->getWidth();
			$asset->height		= $upload->getHeight();
			
			$asset->manageable	= 1;

			FrameworkManager::loadManager('asset');
			$extension = substr($asset->file_src, strrpos($asset->file_src, '.')+1);
			
			// CWI_ASSET_AssetFileType
			$asset_file_type = CWI_MANAGER_AssetManager::getAssetFileTypeByExtension( $extension );
						
			if ($asset_file_type) { #if ($asset_type = AssetManagerLogic::getAssetTypeFromFileName($asset->file_src)) {
			
				#$asset->asset_type_id = $asset_type->id;
				$save_result = AssetManagerLogic::saveUpload($upload, $asset);
				
				Page::redirect($home_url);
				
			} else {
				ErrorManager::addError('Unable to determine a file type for this file.'); 
			}
			
		} else {
			if (!empty($asset->id)) { // Update folder and asset type
				$submitted_asset = $asset;
				$asset = $asset = AssetManagerLogic::getAssetById($asset->id);
				$asset->asset_type_id = $submitted_asset->asset_type_id;
				$asset->folder_id = $submitted_asset->folder_id;
				$asset->caption = $submitted_asset->caption;
				AssetManagerLogic::save($asset);
				Page::redirect($home_url);
			} else {
				ErrorManager::addError('An error occurred while uploading your file: ' . $upload->getError());
			}
		}
		
	}
} else {
	if ($asset_id = Page::get('assetid')) {
		$asset = AssetManagerLogic::getAssetById($asset_id);
	}
}

Page::setStruct('asset', $asset);

// HTML to display image preview - if there is one
$show_image_html = '';

if (strlen($asset->file_src) > 0) {
	if ($asset->asset_type_id == 1) {
		if ($asset->width > 500) {
			
			$show_image_html = '<p><img src="' . $asset->file_src . '" width="450" border="0" /></p>';
		} else {
			$show_image_html = '<p><img src="' . $asset->file_src . '" width="' . $asset->width . '" height="' . $asset->height . '" border="0" /></p>';
		}
	}
}

if ($ctl_folder = Page::getControlById('folder_id')) {
	$ctl_folder->setData($rs_folders);
}
?>
