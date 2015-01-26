<?php

include('global.php');

FrameworkManager::loadLogic('assetmanager');

$asset_folder = Page::getStruct('assetfolder');

if (Page::isPostBack())
{
	if (!empty($asset_folder->name)) {
		AssetManagerLogic::saveFolder($asset_folder);
		Page::redirect('folders.html');
	}
}
else
{
	if ($folder_id = Page::get('folderid'))
	{
		$asset_folder = AssetManagerLogic::getFolderById($folder_id);
	}
}

Page::setStruct('assetfolder', $asset_folder);

?>