(function() {
	// Load plugin specific language pack
	//tinymce.PluginManager.requireLangPack('assetmanager');

	tinymce.create('tinymce.plugins.AssetManagerPlugin', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceAssetManager');
			
			ed.addCommand('mceAssetManager', function() {
				/*
				ed.windowManager.open({
					file : url + '/dialog.htm',
					width : 320 + ed.getLang('assetmanager.delta_width', 0),
					height : 120 + ed.getLang('assetmanager.delta_height', 0),
					inline : 1
				}, {
					plugin_url : url, // Plugin absolute URL
					some_custom_arg : 'custom arg' // Custom argument
				});
				*/
				//alert(url);return;
				ed.windowManager.open({
					_file : url + '/dialog.html',
					file : '/admin/assetmanager/',
					width : 960,
					height : 500,
					inline : 1
				}, {
					plugin_url : url, // Plugin absolute URL
					some_custom_arg : 'custom arg' // Custom argument
				});
			});

			// Register assetmanager button
			ed.addButton('assetmanager', {
				title : 'Asset Manager',
				cmd : 'mceAssetManager',
				image : url + '/img/icon.gif'
			});

			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('assetmanager', n.nodeName == 'IMG');
			});
		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'AssetManager plugin',
				author : 'Robert Jones',
				authorurl : 'http://www.corporatewebimage.com',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/assetmanager',
				version : "1.0"
			};
		},
		
		update : function() {
			var f = document.forms[0], nl = f.elements, ed = tinyMCEPopup.editor, args = {}, el;
	
			tinyMCEPopup.restoreSelection();
	
			if (f.src.value === '') {
				if (ed.selection.getNode().nodeName == 'IMG') {
					ed.dom.remove(ed.selection.getNode());
					ed.execCommand('mceRepaint');
				}
	
				tinyMCEPopup.close();
				return;
			}
	
			if (!ed.settings.inline_styles) {
				args = tinymce.extend(args, {
					vspace : nl.vspace.value,
					hspace : nl.hspace.value,
					border : nl.border.value,
					align : getSelectValue(f, 'align')
				});
			} else
				args.style = this.styleVal;
	
			tinymce.extend(args, {
				src : f.src.value,
				alt : f.alt.value,
				width : f.width.value,
				height : f.height.value
			});
	
			el = ed.selection.getNode();
	
			if (el && el.nodeName == 'IMG') {
				ed.dom.setAttribs(el, args);
			} else {
				ed.execCommand('mceInsertContent', false, '<img id="__mce_tmp" />', {skip_undo : 1});
				ed.dom.setAttribs('__mce_tmp', args);
				ed.dom.setAttrib('__mce_tmp', 'id', '');
				ed.undoManager.add();
			}
	
			tinyMCEPopup.close();
		}

	});

	// Register plugin
	tinymce.PluginManager.add('assetmanager', tinymce.plugins.AssetManagerPlugin);
})();