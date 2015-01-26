<?php

include('global.php');

FrameworkManager::loadLogic('assetmanager');

$asset_folder = Page::getStruct('assetfolder');

if (Page::isPostBack()) {
	if (!empty($asset_folder->name)) {
		// Make sure folder does not already exist
		if (empty($asset_folder->id) && AssetManagerLogic::getFolderByName($asset_folder->name)) { 
			ErrorManager::addError('The folder "' . $asset_folder->name . '" already exists');
		} else {		
			AssetManagerLogic::saveFolder($asset_folder);
		}
	}
} else if (Page::get('delete')) {
	AssetManagerLogic::deleteFolder(Page::get('delete'));
}


if ($dl_folders = Page::getControlById('dl_folder')) {
	$rs_folders = AssetManagerLogic::getFolders();
	
	while ($folder = $rs_folders->getNext()) {
		$folder->folder_link = CWI_STRING_UrlManipulator::appendUrl($home_url, 'folderid', $folder->id);;
	}
	
	$dl_folders->setData($rs_folders);
}

?>