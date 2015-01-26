<?php

FrameworkManager::loadLibrary('string.urlmanipulator');

$base_url	= 'index.html';

$folder		= Page::get('folderid');
$type		= Page::get('type');

if (strlen($folder) > 0) 
	SessionManager::set('am_folderid', $folder);
else {
	if ($folder = SessionManager::get('am_folderid'))
		Page::set('folderid', $folder);
}
/* 
// No longer needed since we removed the type filter
if (strlen($type) > 0)
	SessionManager::set('am_type', $type);
else {
	if ($type = SessionManager::get('am_type')) {
		Page::set('type', $type);
	}
}
*/
$page		= Page::get('p', 1);
$per_page	= Page::get('rpp', 12);

/**
 * @var string $callback Allows an calling page to request the selected asset be sent back.  Should generally be in the format "window.opener.[name_of_function]".  
 * 
 * NOTE: Do not include parenthesis, i.e window.opener.myfunction() is wrong, window.opener.myfunction is correct
 * 
 * The callback function will be called in this format window.opener.myfunction(asset);
 **/
$callback	= Page::get('callback'); // Javascript callback
#if (strlen($callback) > 0 && substr($callback, -1) != ';') $callback .= ';'; // Make sure to append semi-colon for JS

$base_url	= CWI_STRING_UrlManipulator::appendUrl($base_url, 'folderid', $folder);
$base_url	= CWI_STRING_UrlManipulator::appendUrl($base_url, 'p', $page);
$base_url	= CWI_STRING_UrlManipulator::appendUrl($base_url, 'rpp', $per_page);
$base_url	= CWI_STRING_UrlManipulator::appendUrl($base_url, 'type', $type);
$base_url	= CWI_STRING_UrlManipulator::appendUrl($base_url, 'callback', $callback);

$home_url	= $base_url;
$folder_url	= CWI_STRING_UrlManipulator::replaceBaseUrl($base_url, 'folders.html');
$upload_url	= CWI_STRING_UrlManipulator::replaceBaseUrl($base_url, 'upload.html');

?>