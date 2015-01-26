<?php

/**

assets
asset_versions
asset_variations
asset_tags
**/

FrameworkManager::loadManager('asset');
FrameworkManager::loadLogic('assetmanager');
FrameworkManager::loadStruct('assetfolder');


CWI_MANAGER_AssetManager::getVariations();

$action 	= Page::get('action');
$folder_id	= Page::get('folderid', 0);
$type		= Page::get('type');

if ($folder_id == 'none') $folder_id = 0;

function prepare_asset_file_struct($asset_struct) {
	$asset_struct->dimensions = '';
	
	$file_src = $asset_struct->file_src;
	$dot_pos = strrpos($file_src, '.');
		
	$asset_type_name = '';
	
	if ($dot_pos > 0) {
		$extension = substr($file_src, $dot_pos+1);
		if ($asset_file_type = CWI_MANAGER_AssetManager::getAssetFileTypeByExtension($extension)) {
			$asset_type_name = $asset_file_type->getName();
		}
	}
		
	switch ($asset_type_name) {
		case 'PDF':
			$asset_struct->thumbnail_image = ConfigurationManager::get('DIR_WS_GASSETS_IMG') . 'assetmanager/filetypes/pdf.png';
			$asset_struct->thumbnail_width = 64;
			break;
		case 'Word':
			$asset_struct->thumbnail_image = ConfigurationManager::get('DIR_WS_GASSETS_IMG') . 'assetmanager/filetypes/doc.png';
			$asset_struct->thumbnail_width = 64;
			break;
		case 'Excel':
			$asset_struct->thumbnail_image = ConfigurationManager::get('DIR_WS_GASSETS_IMG') . 'assetmanager/filetypes/spreadsheet.png';
			$asset_struct->thumbnail_width = 64;
			break;
		case 'Flash':
			$asset_struct->thumbnail_image = ConfigurationManager::get('DIR_WS_GASSETS_IMG') . 'assetmanager/filetypes/flash.png';
			$asset_struct->thumbnail_width = 64;
			break;
		case 'Image':
		default:
			$asset_struct->thumbnail_image = $asset_struct->file_src;
			$asset_struct->thumbnail_width = null;
			
			$config = ConfigDictionary::createFromString($asset_struct->properties);

			if ( ($width=$config->get('width')) && ($height=$config->get('height'))) {
				
				$asset_struct->dimensions = $width . ' x ' . $height . '<br />';
				
				$max_width = $max_height = 100;
					
				if (!empty($width) && !empty($height)) {
					
					if ($width > $max_width) {
						
						$height = ceil($max_width / $width * $height);
						$width = $max_width;
						
					}
					
					if ($height > $max_height) {
						
						$width = ceil($max_height / $height * $width);
						$height = $max_height;
						
					}
					
					$asset_struct->thumbnail_width = $width;
				}
				
			}
			
			if (empty($asset_struct->thumbnail_width)) $asset_struct->thumbnail_width = 100;
			break;
	}
	
	#if (!empty($asset_struct->width) && !empty($asset_struct->height)) $asset_struct->dimensions = $asset_struct->width . ' x ' . $asset_struct->height . '<br />';
	$asset_struct->display_file_name = $asset_struct->original_file_name;
	$max_text = 12;
	if (strlen($asset_struct->display_file_name) > $max_text) {
		$pos = strrpos($asset_struct->display_file_name, '.');
		$asset_struct->display_file_name = substr($asset_struct->display_file_name, 0, $max_text-3) . '...' . substr($asset_struct->display_file_name, $pos);
	}
	
}

FrameworkManager::loadLibrary('assets.assetfactory');

/**
 * Handle Uploads
 **/
if (Page::isPostBack()) {

	if ($action == 'savefolder') {
		
		$folder_name = Page::get('foldername');
		$parent_id = Page::get('parentid');
		$folder_struct = null;
		
		if (!empty($folder_id)) $folder_struct = AssetManagerLogic::getFolderById($folder_id);
		if (empty($folder_struct)) $folder_struct = new AssetFolderStruct();
		$folder_struct->name = $folder_name;
		$folder_struct->parent_id = $parent_id;
		
		$errors = array();
		$return = array();
		
		if (empty($folder_struct->name)) $errors[] = 'Name is required';
		if (strlen($folder_struct->parent_id) == 0) $errors[] = 'Parent folder was not set.  This is an internal error.  Please contact support';
		
		if (count($errors) == 0) {
			AssetManagerLogic::saveFolder($folder_struct);
			$return = array(
				'success' => true,
				'folder' => $folder_struct->folder,
				'id' => $folder_struct->id,
				'name' => $folder_struct->name,
				'parent_id' => $folder_struct->parent_id
			);
		} else {
			$return = array(
				'success' => false,
				'errors' => $errors
			);
		}
		
		header('Content-type: application/json');
		echo json_encode($return);
		exit;
		
	} else if ($action == 'uploadfile') {

		FrameworkManager::loadStruct('asset');
		
		$folder_id = Page::get('folderid');
		$asset_id = null;
		$error_message = '';
		
		if (!ErrorManager::anyDisplayErrors()) {
			
			if (!$asset = AssetManagerLogic::handleUploadToFolder('fileupload', $folder_id, $asset_id, true, $error_message)) {
				
				ErrorManager::addError($error_message);
				
			}
		}
		
		$return = array(
			'success' => true
		);

		if (ErrorManager::anyDisplayErrors()) {
			$return['success'] = false;
			$return['errors'] = array();
			while ($message = ErrorManager::getDisplayErrors()->getNext()) {
				$return['errors'][] = $message;
			}
		} else {
			$asset_struct = AssetManagerLogic::getAssetById($asset->getId());
			prepare_asset_file_struct($asset_struct);
			$return['asset'] = $asset_struct;
		}

#ob_start();
#print_r($return);
#mail('rjones@corporatewebimage.com', 'Upload Response', ob_get_contents());
#ob_end_clean();

		header('Content-type: application/json');
		echo json_encode($return);
		exit;
		

		
		#####################
		/*
		// Set the uplaod directory
		$uploadDir = '/uploads/';
		die( json_encode( array('name'=>'Robert') ) );
		// Set the allowed file extensions
		$fileTypes = array('jpg', 'jpeg', 'gif', 'png'); // Allowed file extensions
		
		$verifyToken = md5('unique_salt' . $_POST['timestamp']);
		
		if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
			$tempFile   = $_FILES['Filedata']['tmp_name'];
			$uploadDir  = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
			$targetFile = $uploadDir . $_FILES['Filedata']['name'];
		
			// Validate the filetype
			$fileParts = pathinfo($_FILES['Filedata']['name']);
			if (in_array(strtolower($fileParts['extension']), $fileTypes)) {
		
				// Save the file
				move_uploaded_file($tempFile, $targetFile);
				echo 1;
		
			} else {
		
				// The file type wasn't allowed
				echo 'Invalid file type.';
		
			}
		}
		*/
	}
	
} else {
	
	/**
	 * Handle asset deletion
	 **/
	switch ($action) {
		
		case 'deleteasset':
			
			$asset_id = Page::get('assetid');
			AssetManagerLogic::delete($asset_id);
			break;
			
	}

}

if ($type && $asset_type = AssetManagerLogic::getAssetTypeByName($type)) {
	$asset_type_id = $asset_type->id;
} else {
	$asset_type_id		= null;
}
$width_min		= null;
$width_max		= null;
$height_min		= null;
$height_max		= null;
$file_src		= null;

$search = AssetManagerLogic::getBaseSearch();
AssetManagerLogic::addSearchFolder($search, $folder_id);
$table_variations = AssetManagerLogic::addSearchVariations($search, array('am-thumbnail'=>'var1'));
$dao = new DataAccessObject();

/*echo 'Query: ' . $dao->getSearchQueryString($search);
echo '<pre>';
print_r($table_variations);
exit;
*/

#echo '<pre>';print_r($search);exit;

$rs_assets = AssetManagerLogic::searchManageableAssets($folder_id, $asset_type_id, $width_min, $width_max, $height_min, $height_max, $file_src);

while ($asset_struct = $rs_assets->getNext()) {
	
	prepare_asset_file_struct($asset_struct);
	
}

if ($action == 'getassets') {
	header('Content-type: application/json');
	echo json_encode( $rs_assets->getAll() );
	exit;
}
if ($ctl_json_assets = Page::getControlById('json_assets')) {
	
	$json_assets = array('parent_' . $folder_id => $rs_assets->getAll());
	$ctl_json_assets->setText( json_encode($json_assets) );
}

/**
 * Retreive folders
 **/
$rs_folders = AssetManagerLogic::getFolders();
$json_folders = array();
// Make sure parent id has a value
while ($folder = $rs_folders->getNext()) {
	if (empty($folder->parent_id)) $folder->parent_id = 0;
	
	$json_folders[] = array(
		'folder' => $folder->folder,
		'id' => $folder->id,
		'name' => $folder->name,
		'parent_id' => $folder->parent_id
		);
}
if ($ctl_json_folders = Page::getControlById('json_folders')) {
	$ctl_json_folders->setText( json_encode($json_folders) );
}

/**
 * Build up javascript folder/asset template functions
 **/
$js_folder_template = '<a class="folder-box pull-left" href="#" id="folder-<Data field="id" />" onclick="generateFolders(<Data field="id" />);return false;" data-id="<Data field="id" />">
	<span class="name"><i class="glyphicon glyphicon-folder-close icon-white"></i> <Data field="name" /></span>
</a>
';

$js_asset_template = '<div class="asset-box pull-left" data-id="<Data field="id" />">
	<a href="#" class="image">
		<img src="<Data field="thumbnail_image" />" width="<Data field="thumbnail_width" />" border="0" alt="<Data field="original_file_name" />" class="preview"  style="width:<Data field="thumbnail_width" />px;margin:0 auto;" />
	</a>
	<div class="thumbnail-info">
		<span class="name"><Data field="display_file_name" /></span><br />
		<span class="dimensions"><Data field="dimensions" /></span>
	</div>
	<div class="thumbnail-actions">
		<a href="upload.html?assetid=<Data field="id" />" class="btn"><i class="glyphicon glyphicon-pencil"></i></a> 
		<a href="index.html?delete=<Data field="id" />" class="btn btn-danger"><i class="glyphicon glyphicon-trash"></i></a>
	</div>
</div>';
				
function js_template_function($function_name, $template) {
	$function_body = 'return \'' . preg_replace('#<Data field="(.+?)" />#', "' + obj.$1 + '", str_replace('\'', '\\\'', $template)) . '\';';
	$function_body = preg_replace('#[\r\n\t]+#', ' ', $function_body);
	return 'function ' . $function_name . '(obj) {
		' . $function_body . '
	}';
}

if ($ctl_js_template_functions = Page::getControlById('js_template_functions')) {
	$folder = js_template_function('generateFolderHTML', $js_folder_template);
	$asset = js_template_function('generateAssetHTML', $js_asset_template);
	$ctl_js_template_functions->setText($folder . "\n" . $asset);
}

?>