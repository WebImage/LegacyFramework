/*
 * Version 1.0 (08-18-2010)
 *
 * Loosely based on Jeditable by Mika Tuupola, Dylan Verheul, MIT License, http://www.appelsiini.net/projects/jeditable - which was based on editable by Dylan Verheul <dylan_at_dyve.net>:
 *	http://www.dyve.net/jquery/?editable
 */
wysiwyg_settings = {
	theme : "advanced",
	skin : "athena",
	plugins	: "table,media,assetmanager,inlinepopups",
	theme_advanced_buttons1 : "formatselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull",
	theme_advanced_buttons2 : "link,anchor,image,|,forecolor,backcolor,|,charmap,|,cleanup,assetmanager",
	theme_advanced_buttons3 : "", theme_advanced_buttons4:"",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_blockformats : "p,h1,h2,h3,h4,h5,h6",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,
	theme_advanced_resize_horizontal : false,
	extended_valid_elements : "iframe[src|width|height|name|align|scrolling|frameborder],div[align|class|style],img[src|align|width|height|border|style|class|assetId|assetVariation]",
	convert_urls : false,
	init_instance_callback : function() { /* loaded */ },
	inlinepopups_skin : "athenacms"
};

button_groups = [
	{
		width:120, /* Approx... */
		buttons: ['formatselect']
	},
	{
		buttons: ['bold','italic','underline','strikethrough','blockquote']
	},
	{
		buttons: ['forecolor','backcolor']
	},
	{
		buttons: ['justifyleft','justifycenter','justifyright','justifyfull']
	},
	{
		buttons: ['bullist','numlist']
	},
	{
		buttons: ['link','unlink','anchor','image']
	},
	{
		buttons: ['hr','visualaid']
	},
	{
		buttons: ['sub','sup']
	},
	{
		width : ((11 * 22) + (4 * 12)), /* 11 Elements x 22 pixels + 4 spaces * 12 pixels) */
		buttons: ['tablecontrols']
	},
	{
		buttons: ['charmap']
	},
	{
		buttons: ['cleanup','removeformat','code']
	},
	{
		buttons : ['assetmanager','media']
	}
];

wysiwyg_buttons = {
	full : {
		buttons1 : "formatselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,link,unlink,anchor,image,|,hr,removeformat,visualaid,|,sub,sup",
		buttons2 : "tablecontrols,|,forecolor,backcolor,|,charmap,|,cleanup,code",
		buttons3 : "",
		buttons4 : ""
	}
}
function debug(str) {
	var debug = $('#debug');
	debug.html( debug.html() + str + '<br />' );
}
function get_wysiwyg_buttons(width) {
	if (wysiwyg_buttons['buttonset'+width]) return wysiwyg_buttons['buttonset'+width];
	
	wysiwyg_buttons['buttonset'+width] = {};
	
	var row = 1;
	var row_width = 0;
	
	for (i=0; i < button_groups.length; i++) {
		if (button_groups[i].width) {
			group_width = button_groups[i].width;
		} else {
			group_width = button_groups[i].buttons.length * (25+2);
		}
		if (group_width > width) continue;
		if ((row_width + group_width) > width) {
			row ++;
			row_width = 0;
		}
		
		if (!wysiwyg_buttons['buttonset'+width]['buttons'+row]) wysiwyg_buttons['buttonset'+width]['buttons'+row] = '';
		
		row_width += group_width;
		
		if (wysiwyg_buttons['buttonset'+width]['buttons'+row].length > 0) {
			wysiwyg_buttons['buttonset'+width]['buttons'+row] += ',|,';
		}
		
		wysiwyg_buttons['buttonset'+width]['buttons'+row] += button_groups[i].buttons.join(',');
		
		//debug('Row: ' + row + ' - ' + wysiwyg_buttons['buttonset'+width]['buttons'+row]);
	}
	
	return wysiwyg_buttons['buttonset'+width];
}
$.fn.clickedit = function(options) {
	return this.each(function() {
		var t = this;
		
		if (typeof(t.revert) != 'undefined') return true; // already defined/initiated, stop from running again
		
		var settings = $.extend({}, $.fn.clickedit.defaults, options);
		
		t.revert = $(t).html();
		t.settings = settings;
		t.width = null;
		t.height = null;
		t.keepHidden = false; // Allows the main content to remain hidden
		t.showContent = function() {
			if (!this.keepHidden) $(t).show();
		}
		t.hideContent = function() { $(t).hide(); }
		
		t.setKeepHidden = function(b_true_false) { // Allows the content to remain hidden on committ
			t.keepHidden = b_true_false;
		}
		
		t.element = $.clickedit.types[t.settings.type].element || $.clickedit.types['defaults'].element; // Add hidden elements
		t.content = $.clickedit.types[t.settings.type].content || $.clickedit.types['defaults'].content; // Set content of input controls
		t.prepare = $.clickedit.types[t.settings.type].prepare || $.clickedit.types['defaults'].prepare; // Prepare sizing and other input control details
		t.buttons = $.clickedit.types[t.settings.type].buttons || $.clickedit.types['defaults'].buttons; // Prepare sizing and other input control details
		t.commit = $.clickedit.types[t.settings.type].commit || $.clickedit.types['defaults'].commit; // Commit changes
		
		t.form = $(this).parent('form');
		
		$(t.form).bind('submit', function() {
			t.commit();
		});
		if (t.settings.inputControlDisplay == 'inline') t.inputControl = $('<span class="inputcontrol" />'); // inline
		else t.inputControl = $('<div class="inputcontrol" />'); // block
		
		t.inputControl.hide();
		$(t).after(t.inputControl);
		
		t.element();
		if (settings.showButtons) t.buttons();
		t.content();
		
		if (typeof(t.settings.onInit) == 'function') t.settings.onInit.apply(t);
		
		$(t, settings).bind('click', function(ev) {
			ev.preventDefault();
			t.inputControl.input.keydown(function(ev) {
				if (ev.keyCode == 27) {
					t.reset();
				}
			});
			
			t.content();
			t.prepare();
			t.hideContent();
			t.inputControl.show();
			t.inputControl.children(':input:first').focus();
			t.inputControl.children(':input:first').select();
		});
		
		
		
		t.reset = function() {
			$(t).html(t.revert);
			t.content(t.revert);
			t.inputControl.hide();
			t.showContent();
		}
		
	});
}
$.clickedit = {
	types : {
		defaults : {
			element : function() {
				var t = this;
				var input = $('<input type="text" />', t);
				if (t.settings.id) input.attr('id', t.settings.id);
				if (t.settings.name) input.attr('name', t.settings.name);
				
				input.keypress(function(e) {
							if (e.keyCode == 13) {
								e.preventDefault();
								t.commit();
							}
							});
				t.inputControl.input = input;
				$(t.inputControl).append(t.inputControl.input);
			},
			content : function(str) {
				var t = this;
				if (typeof(str) == 'undefined') {
					str = $(t).html();
					if (str.length == 0) {
						$(t).html(t.settings.placeholder);
					}
				}
				
				t.revert = str;
				//alert(t.settings.type + ' - ' + t.settings.placeholder);
				if (str.toUpperCase() == t.settings.placeholder.toUpperCase()) str = '';
				
				t.inputControl.input.val( str );
			},
			prepare : function() {
				var t = this;
				
				var parentWidth = $(this).parent().width();
				
				if (t.settings.fullWidthInput) {
					t.width = parentWidth; // See if this version of width works better
				} else {
					// Get width of element to be edited
					var width = $(this).width();
					// Add padding
					width += 30;
					// Make sure added padding does not make the element longer than the parent container
					if (width > parentWidth) width = parentWidth;
					// Set width
					t.width = width;//$(this).width();
				}
				
				t.height = $(this).height();
				t.fontSize = $(this).css('font-size');
				t.inputControl.input.css({
					width:t.width,
					fontSize:t.fontSize
					});
			},
			buttons : function() {
				var t = this;
				var save = $('<input type="button" value="Preview" />', t);
				var cancel = $('<input type="button" value="Cancel" />', t);
				save.click(function(e) { t.commit(); });
				cancel.click(function(e) { t.reset(); });
				
				t.inputControl.append(save);
				t.inputControl.append(cancel);
			},

			commit : function() {
				var t = this;
				revert = t.revert;
				if (revert.toUpperCase() == t.settings.placeholder.toUpperCase()) revert = '';
				if (revert.toUpperCase() != t.inputControl.input.val().toUpperCase()) $(t).trigger('contentchanged', {oldValue:revert,newValue:t.inputControl.input.val()});

				var new_value = t.inputControl.input.val();
				if (new_value.length == 0) new_value = t.settings.placeholder;
				$(t).html(new_value);
				t.inputControl.hide();
				t.showContent();
			}
		},
		text : {},
		textarea : {
			element : function() {
				var t = this;
				var textarea = $('<textarea />');
				if (t.settings.id) textarea.attr('id', t.settings.id);
				if (t.settings.name) textarea.attr('name', t.settings.name);
				
				t.inputControl.input = textarea;
				t.inputControl.append(t.inputControl.input);
			}
		},
		wysiwyg : {
			element : function() {
				var t = this;
				$.clickedit.types.textarea.element.apply(t);
				t.inputControl.input.css('visibility', 'hidden');
			},
			prepare : function() {
				var t = this;
				
				$.clickedit.types.defaults.prepare.apply(t);
				var new_height = t.height + 150;
				t.inputControl.input.css('height', new_height + 'px');
				
				var wysiwyg_config = {
					theme : "advanced",
					skin : "athena",
					plugins	: "table,media,assetmanager,inlinepopups",
					theme_advanced_buttons1 : '',
					theme_advanced_buttons2 : '',
					theme_advanced_buttons3 : '',
					theme_advanced_buttons4 : '',
					theme_advanced_toolbar_location : "top",
					_theme_advanced_toolbar_location : "external",
					theme_advanced_toolbar_align : "left",
					theme_advanced_blockformats : "p,h1,h2,h3,h4,h5,h6",
					theme_advanced_statusbar_location : "bottom",
					theme_advanced_resizing : true,
					theme_advanced_resize_horizontal : false,
					extended_valid_elements : "iframe[src|width|height|name|align|scrolling|frameborder],div[align|class|style],img[src|width|height|border|style|class|assetId|align|assetVariation]",
					convert_urls : false,
					setup : function(ed) {
						
						ed.onInit.add(function(ed) {
							// Focus editor
							ed.focus();
							/*
							// Add toolbar
							ed.onPostRender.add(function(ed, cm) {
								alert('add');
							});
							// Remove toolbar
							ed.onRemove.add(function(ed) {
								alert('remove');
							});
							*/
						});
						/*
						ed.onKeyDown.add(function(ed, ev) {
									if (ev.keyCode == 27) t.reset();
									});
						*/
					},
					inlinepopups_skin : "athenacms"
				};
				
				var button_rows = get_wysiwyg_buttons(t.width);
				for(var button_row in button_rows) {
					wysiwyg_config['theme_advanced_' + button_row] = button_rows[button_row];
				}
				
				t.inputControl.input.tinymce(wysiwyg_config);
/*
				setTimeout(function() {
						    t.inputControl.input.tinymce().focus();
						    }, 50);
*/
			},
			commit : function() {
				var t = this;
				var new_value;

				var mce = t.inputControl.input.tinymce();
				if (mce) {
					new_value = t.inputControl.input.html(); // Get value from WYSIWYG editor
					mce.hide();
				} else { // MCE never rendered
					new_value = t.inputControl.input.val(); // Get value from input field
				}
				
				t.inputControl.input.val(new_value);
				
				if (new_value.length == 0) new_value = t.settings.placeholder; // Put something clickable in the original element
				$(t).html(new_value);
				
				$(t).trigger('contentchanged');
				
				t.inputControl.hide();
				t.showContent();
			}
		}
	}
}
$.fn.clickedit.defaults = {
	inputControlDisplay : 'block',
	type : 'text',
	placeholder : '<em>Click to edit</em>',
	onInit : function() {},
	showButtons:true,// Whether Preview and Cancel buttons will be used
	fullWidthInput:false // Whether the input control width will be 100%; false means the width will be the same as the element being edited; true means the parent width will be used
}