/*
 * Version 1.0 (08-18-2010)
 *
 * Loosely based on Jeditable by Mika Tuupola, Dylan Verheul, MIT License, http://www.appelsiini.net/projects/jeditable - which was based on editable by Dylan Verheul <dylan_at_dyve.net>:
 *	http://www.dyve.net/jquery/?editable
 */

$.fn.clickedit = function(options) {
	return this.each(function() {
		var t = this;
		t.input = null;
		t.buttons = null;
		t.revert = $(t).html();
		t.settings = $.extend({}, $.fn.clickedit.defaults, options);
		
		t.showContent = function() { $(t).show(); }
		t.hideContent = function() { $(t).hide(); }
		
		t.element = $.clickedit.types[t.settings.type].element || $.clickedit.types['defaults'].element; // Add hidden elements
		t.content = $.clickedit.types[t.settings.type].content || $.clickedit.types['defaults'].content; // Set content of input controls
		t.prepare = $.clickedit.types[t.settings.type].prepare || $.clickedit.types['defaults'].prepare; // Prepare sizing and other input control details
		var buttons = $.clickedit.types[t.settings.type].buttons || $.clickedit.types['defaults'].buttons; // Prepare sizing and other input control details
		t.commit = $.clickedit.types[t.settings.type].commit || $.clickedit.types['defaults'].commit; // Commit changes
		
		t.form = $(this).parent('form');
		t.form.bind('submit', function() {
			t.commit();
		});
		if (t.settings.inputControlDisplay == 'inline') t.inputControl = $('<span>'); // inline
		else t.inputControl = $('<div>'); // block
		t.inputControl.hide();
		$(t).after(t.inputControl);
		
		t.element();
		buttons.apply(t);
		
		t.content();
		
		$(this).bind('click', function() {
			t.inputControl.children(':input:first').focus();
			t.inputControl.input.keydown(function(e) {
				if (e.keyCode == 27) {
					t.reset();
				}
			});
			
			t.content();
			t.prepare();
			t.hideContent();
			t.inputControl.show();
		});
		
		
		
		this.reset = function() {
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
				t = this;
				var input = $('<input />');
				t.inputControl.input = input;
				$(t.inputControl).append(t.inputControl.input);
			},
			content : function(str) {
				t = this;
				if (typeof(str) == 'undefined') {
					str = $(t).html();
					if (str.length == 0) {
						$(t).html(t.settings.placeholder);
					}
				}
				
				t.revert = str;
				
				if (str == t.settings.placeholder) str = '';
				
				t.inputControl.input.val( str );
			},
			prepare : function() {
				this.inputControl.input.css({
					width:$(this).width(),
					height:$(this).height()
					});
			},
			buttons : function() {
				t = this;
				
				var save = $('<input type="button" value="Preview" />');
				var cancel = $('<input type="button" value="Cancel" />');
				save.click(function(e) { t.commit.apply(t); });
				cancel.click(function(e) { t.reset(); });
				
				t.inputControl.append(save);
				t.inputControl.append(cancel);
			},

			commit : function() {
				t = this;
				
				if (t.revert != t.inputControl.input.val()) $(t).trigger('contentchanged');

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
				
				t.inputControl.input.tinymce({
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
					extended_valid_elements : "iframe[src|width|height|name|align|scrolling|frameborder],div[align|class|style],img[src|width|height|border|style|class|assetId|assetVariation]",
					convert_urls : false,
					init_instance_callback : function() { /* loaded */ },
					inlinepopups_skin : "athenacms"
				});
			},
			commit : function() {
				var t = this;
				
				var new_value = t.inputControl.input.html();
				
				t.inputControl.input.tinymce().hide();
				t.inputControl.input.val(new_value);
				$(t).html(new_value);
				
				t.inputControl.hide();
				t.showContent();
			}
		}
	}
}
$.fn.clickedit.defaults = {
	inputControlDisplay : 'block',
	type : 'text',
	placeholder : '<em>Click to edit</em>'
}