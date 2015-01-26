// JavaScript Document
// Requires: nestedsortable.js
(function($) {
	
	var menu_item_count = 0; // Global scope to guarantee that we do not get duplicate IDs for new menu items
	var clickedit_name = {
		inputControlDisplay:'inline',
		fullWidthInput:false,
		placeholder:'<em>Click to edit menu label</em>'
	};
	
	var clickedit_url = {
		inputControlDisplay:'inline',
		fullWidthInput:false,
		placeholder:'<em>Click to edit menu link</em>'
	};
	$.widget('cwi.editableMenu', {
		
		option : {
			maxLevels : 3,
			opacity : 1
		},
		
		_create : function() {
			
			var self = this;
			
			this.element.nestedSortable({
				forcePlaceholderSize: true,
				handle: 'div',
				helper:	'clone',
				items: 'li',
				maxLevels: this.options.maxLevels,
				opacity: this.options.opacity,
				placeholder: 'menuitem-placeholder',
				revert: 250,
				tabSize: 25,
				tolerance: 'pointer',
				toleranceElement: '> div',
				isAllowed : function(parentItem, placeholder) {
					
					if (parentItem) {
						var name = $(parentItem).find('.item-name:first').next().children('input:first').val();
						var url = $(parentItem).find('.item-url:first').next().children('input:first').val();
						//if (name.length == 0 || url.length == 0) {
						if (name.length == 0) {
							if (!placeholder.hasClass('menuitem-placeholder-nodrop')) placeholder.addClass('menuitem-placeholder-nodrop');
							
							//placeholder.text('Menu name and link must be set');
							return false;
						} else {
							placeholder.removeClass('menuitem-placeholder-nodrop');
						}
					}
					return true;
				}
			});
			
			this.element.find('div.item-name').clickedit(clickedit_name);
			this.element.find('div.item-url').clickedit(clickedit_url);
			
			/*			
			$('#serialize').click(function() {
				var serialized = self.element.nestedSortable('export');
				$('#serializeOutput').text(serialized+'\n\n');
			});
			*/
		},
		
		newItem : function() {
			menu_item_count++;
			$li = $('<li />').attr('id', 'new_' + menu_item_count).attr('data-id', 'new_' + menu_item_count);
			$row = $('<div />').attr('class', 'menu-row');
			$name = $('<div class="item-name" />');
			$url = $('<div class="item-url" />');
			$row.append($name).append($url);
			$li.append($row);
			this.element.append($li);
			$name.clickedit(clickedit_name);
			$url.clickedit(clickedit_url);
		},
		
		export : function() {
			//var $this = $(this);
			var self = this;
			
			parentLookup = this.element.nestedSortable('export');
			serialized = '';
			var exprt = {};
			
			var cnt = 0;
			this.element.find('li').each(function() {
				var ref = $(this).attr('id');
				var id = $(this).attr('data-id');
				var name = $(this).find('.item-name:first').next().children('input:first').val();
				var url = $(this).find('.item-url:first').next().children('input:first').val();
				
				if (name.length > 0) {
					cnt++;
					var base = 'item'+(cnt-1)+'_';
					
					exprt[base + 'ref'] = ref;
					exprt[base + 'id'] = id;
					exprt[base + 'name'] = name;
					exprt[base + 'url'] = url;
					exprt[base + 'parent'] = (typeof(parentLookup[ref]) == 'undefined' ? 0 : parentLookup[ref]);
				}
				
			});
			
			exprt.items = cnt;
			return exprt;

		}
	});
	
})(jQuery);