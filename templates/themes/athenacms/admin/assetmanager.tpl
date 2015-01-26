<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<cms:PageHeader debug="true" />
<!--
<script type="text/javascript" src="<?php echo ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS') ?>global.js"></script>
<script type="text/javascript" src="<?php echo ConfigurationManager::get('DIR_WS_GASSETS_JS') ?>jquery/jquery.js"></script>
<script type="text/javascript" src="<?php echo ConfigurationManager::get('DIR_WS_GASSETS_JS') ?>jquery/jquery-ui.min.js"></script>
-->
<script type="text/javascript">
var current_folder_id = null;
var DIR_WS_ADMIN_ROOT = '<?php echo ConfigurationManager::get('DIR_WS_ADMIN') ?>';
function insertImage(asset_id, asset_src, asset_width, asset_height) {
	<?php
	if (strlen($callback) > 0) {
		echo $callback . ';';
		?>
		window.close();
		<?php
	} else {
	?>
	return false;
	<?php
	}
	?>
	/**
	 In the future we'll add the ability to return an Asset object:
	var asset_object = new Asset();
	*/
}
function handleReceiveUpdateAssets(data, textStatus) {
	$('#assets').html(data);
	$('#assets').show();//('fast');
}
function handleFolderAdded(data, textStatus) {
	if (data == 'false') {
		alert('There was an error addding your folder.  Please contact support.');
	} else {
		current_folder_id = data;
		alert('handleFolderAdded: ' + current_folder_id + ' ---' + data);
		$.ajax({
			type:"GET",
			url:refresh_url,
			success:handleReceiveUpdateFolders
			});
	}
}
function handleReceiveUpdateFolders(data, textStatus) {
	$('#folders').html(data);
	changeFolder(current_folder_id);
}
function changeFolder(folder_id, name) {
	if (folder_id != current_folder_id) { // Prevent from refreshing if it is the same folder
		$('#folderid').val(folder_id);
		if ($('#upload-form').is(':hidden')) {
			$('#upload-select-folder').hide();
			$('#upload-form').show();
			jQuery('#folder_folder_'+folder_id).addClass('selected');
		}
		current_folder_id = folder_id;
		url = DIR_WS_ADMIN_ROOT + 'assetmanager/ajax_assets.html?folderid=' + folder_id;
		//$('#assets').html('Loading...');
		$('#assets').hide();
		$('#folder-name').html(name);
		$.ajax({
			type:"GET",
			url:url,
			success:handleReceiveUpdateAssets
			});
	}
}
function addFolder(folder_name) {
	if (folder_name.length > 0) {
		add_url = DIR_WS_ADMIN_ROOT + 'assetmanager/ajax_addfolder.html?name=' + escape(folder_name);
		refresh_url = DIR_WS_ADMIN_ROOT + 'assetmanager/ajax_folders.html';
		$('#folders').html('Refreshing...');
		
		$.ajax({
			type:"GET",
			url:add_url,
			success:handleFolderAdded,
			error:function(){
				alert('possible error');
			}
			});
	} else {
		alert('You must specify a folder name.');
	}
}
/*
$(document).ready(function(){
	changeFolder(0, 'Unassigned Assets');
	//changeFolder(1, 'Test Load MISC');
	
	$('#folders a').click(function() {
		
		$('#folders li').removeClass('selected');
		
		_this = $(this);
		_this.parent('li').addClass('click-confirm');
		setTimeout(function() {
			 _this.parent('li').removeClass('click-confirm');
		 }, 100);
		 setTimeout(function() {
			 _this.parent('li').addClass('selected');
		 }, 200);
		
	});
	
	//if (!use_google_gears) alert('Did you know that you can use Google Gears to drag and drop files?');
});
*/
function enableAssetEditMode() {
	$('#assets').hide('fast');
	//$('#folder-window').hide();
	$('#folders').animate({
	      opacity:.20
	});
	$('#asset-change-window').show();
	/*
	$('#asset-window').animate({
		left:'8px'
		}, 300);
	$('#upload-window').animate({
		left:'8px'
		}, 300);
	*/
	setTimeout(function() {
		disableAssetEditMode();
		}, 2000);
	
}
function disableAssetEditMode() {
	/*
	$('#asset-window').animate({
		left:'216px'
		}, 200);
	$('#upload-window').animate({
		left:'216px'
		}, 200);
	*/
	$('#asset-change-window').hide();
	$('#assets').show('fast');
	
	setTimeout(function() {
		//$('#folder-window').show();
		$('#folders').animate({
		      opacity:1
		});
	}, 220);
}
</script>
</head>
<body>
<cms:PlaceHolder id="ph_admin_main" />
</body>
</html>