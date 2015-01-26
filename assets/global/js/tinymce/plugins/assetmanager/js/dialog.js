tinyMCEPopup.requireLangPack();

var AssetManagerDialog = {
	init : function() {
		var f = document.forms[0], nl = f.elements, ed = tinyMCEPopup.editor, dom = ed.dom, n = ed.selection.getNode();
		//var f = document.forms[0];
		tinyMCEPopup.restoreSelection();
		if (tinymce.isWebKit) ed.getWin().focus();
		asset_id = '';
		asset_variation = '';
		if (n.nodeName == 'IMG') {
			var asset_id = dom.getAttrib(n, 'assetId');
			var asset_variation = dom.getAttrib(n, 'assetVariation');
		}
	
		// Get the selected contents as text and place it in the input
		//f.someval.value = tinyMCEPopup.editor.selection.getContent({format : 'text'});
		/*
		f.assetid.value = asset_id;
		f.assetvariation.value = asset_variation;
		f.somearg.value = tinyMCEPopup.getWindowArg('some_custom_arg');
		*/
	},

	insert : function(asset) {
		// Insert the contents from the input into the document
		//tinyMCEPopup.editor.execCommand('mceInsertContent', false, document.forms[0].someval.value);
		//tinyMCEPopup.close();
		
		var ed = tinyMCEPopup.editor;
		tinyMCEPopup.restoreSelection();
		if (tinymce.isWebKit) ed.getWin().focus();

		var args = {
			width:asset.getParameter('width'),
			height:asset.getParameter('height'),
			src:asset.getFileSrc(),
			assetId:asset.getId(),
			assetVariation:'simple'
		}
		
		ed.execCommand('mceInsertContent', false, '<img id="__mce_tmp" />');
		ed.dom.setAttribs('__mce_tmp', args);
		ed.dom.setAttrib('__mce_tmp', 'id', '');
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(AssetManagerDialog.init, AssetManagerDialog);