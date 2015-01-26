(function($) {
	
$.widget('cwi.multiaccordion', {
	options : {
		header : 'h1,h2,h3,h4,h5,h6',
		body : 'div',
		
	},
	_create : function() {
		var self = this;
		
		this.element.addClass('ui-accordion ui-widget');
		
		this.element.children(this.options.header).each(function() {
			var $icon = $('<span />').addClass('ui-icon');
			$(this).addClass('ui-accordion-header');
			$(this).click(function() {
				$(this).toggleClass('ui-state-active');
				$(this).next(self.options.body).slideToggle('fast');
				//$(this).next().toggleClass('ui-accordion-content-open');
			});
		});
		this.element.children(this.options.body).each(function(ix) {
			$(this).addClass('ui-accordion-content');
		});
	},
	
	/*addElement : function(html) {}*/
});

})(jQuery);