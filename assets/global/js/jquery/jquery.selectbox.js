/**
 * Copyright 2012 Corporate Web Image, Inc.
 **/
//http://labs.abeautifulsite.net/jquery-selectBox/
(function($) {

$.widget('cwi.selectBox', {
	
	options : {},
	
	_create : function() {
		
		var self = this;
		
		this.element.addClass('ui-selectbox ui-widget');
		this.element.css('opacity', .5);

		this.selectBox = $('<a />')
			.addClass('ui-selectbox')
			.addClass(this.element.attr('class'))
			.css('display','inline-block')
			
			.html('Select')
			.disableSelection()
			.click(function() {
				self.choices.appendTo($('body'));
				self.choices.css({
					position:'absolute',
					left:self.selectBox.offset().left,
					top:self.selectBox.offset().top + self.selectBox.outerHeight()
				});			
				self.choices.width(self.element.outerWidth())		
				self.choices.toggle();
			});
		
		//var $li = $('<li />').text('test').appendTo(this.choices);
		this.element.after(this.selectBox);

		
		this.choices = $('<ul />').addClass('ui-options');
		//this.selectBox.after(this.choices);
		$('body').append(this.choices);
		
		
		this.choices.hide();
		this.element.hide();
		this.refresh();
		
	},
	
	addOption : function(key, val) {
		this.refresh();
	},
	
	refresh : function() {
		
		var self = this;
		
		var attrSize = this.element.attr('size');
		//var attrDisabled = this.element.attr('disabled');
		var attrMultiple = this.element.attr('multiple');
				
		if (true) {
			
			this.choices.empty();
			this.choices.height(150).css('overflow','auto');
			
			var ix = 0;
			var showSelected = []; // Keep track of the options that need to be selected
			this.element.find('option,optgroup').each(function() {
				
				var li = $('<li />');
				self.choices.append(li);
				
				if (this.tagName.toLowerCase() == 'optgroup') {
					li.addClass('ui-option-group');
					li.text(this.getAttribute('label'));
					
				} else {
					
					var option = this;
					//self.choices.append('Options<br />');
					li.addClass('ui-option');
					li.attr('data-index', ix);
					li.text(this.innerText);
					
					li.css('cursor', 'pointer');
					li.hover(function() {
						if (!$(this).hasClass('ui-option-selected')) $(this).addClass('ui-option-hover');
						self.element.trigger('option-mouseover', {
							el : li,
							key : option.value,
							text : option.innerText,
							selected : option.selected,
							help : $(option).attr('label')
						});
					}, function() {
						$(this).removeClass('ui-option-hover');
						self.element.trigger('option-mouseout');
						/*
						self.element.trigger('option-mouseout', {
							el : li,
							key : this.value,
							text : this.innerText,
							selected : this.selected
						});
						*/
					});
					
					if (this.selected) {
						li.addClass('ui-option-selected');
						str = this.innerText;
						str = str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
						showSelected[showSelected.length] = str;
					}
					
					li.click(function() {
						var $li = $(this);
						
						if (!attrMultiple) self.resetSelection();
						
						self.element[0].options[$li.attr('data-index')].selected = !self.element[0].options[$li.attr('data-index')].selected;
						$li.toggleClass('ui-option-selected');
						self.element.trigger('change');
						self.refresh();
						
						if (!attrMultiple) self.choices.hide();
						
					});
					
					ix++;
					
				}
				
			});
			
			if (showSelected.length > 0) {
				var str = showSelected.join(', ');
				this.selectBox.html(str);
			} else {
				this.selectBox.html('&nbsp;');
			}
			
			
		}
/*		$('body').click(function() {
			var eq_this = (this == document.activeElement + ' - ' + document.activeElement);
		});
*/
		
	},
	
	resetSelection : function() {
		for(var i=0,j=this.element[0].options.length; i < j; i++) {
			this.element[0].options[i].selected = false;
		}
		this.choices.children().removeClass('ui-option-selected');
		this.selectBox.html('<em>None</em>');
		
	},
	
	destroy : function() {
		this.selectBox.remove();
	}
	
});

})(jQuery);