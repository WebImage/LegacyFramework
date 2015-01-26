// JavaScript Document
/**
 * <ul>
 *	<li class="folder"></li>
 * </ul>
 **/
jQuery.fn.folderfy = function(options) {
	return this.each(function() {
		var selected_li = null;
		//var selected_a = null;
		function makeClickable(t) {
			if (!t.hasClass('folderfied')) {
				t.addClass('folderfied');
			
				t.children('li.folder').each(function() {
					var t_li = $(this);
					t_li.children('a').click(function() {
						var t_a = $(this);
						if (selected_li) {
							selected_li.removeClass('selected');
						}
						selected_li = t_li;
						selected_li.addClass('selected');
						if (t_li.has('ul')) {
							t_li.children('ul').each(function() {
								var t_ul = $(this);
								makeClickable(t_ul);
								if (t_ul.is(':visible')) {
									t_ul.hide();
								} else {
									t_ul.show();
								}
							});
						}
					});
				});
			}
		}
		var t = $(this);
		makeClickable(t);
		
		$(this).show();
	})
}