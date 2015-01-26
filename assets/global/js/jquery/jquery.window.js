/**
 * Copyright 2012 Corporate Web Image, Inc.
 **/
if (typeof CWI === 'undefined') CWI = {};

// Singleton
CWI.WindowManager = function() { this.init(); }

CWI.WindowManager.LAYERS = {
	CONTROL : 1,
	WINDOW : 500,
	MODAL : 1000
};

CWI.WindowManager.prototype = {
		
	init : function() {
		this.windowGroups = [];
		this.overlays = [];
		this.totalAnonymous = 0;
	},
	
	_getAnonymousGroupName : function() {
		this.totalAnonymous++;
		return 'anonymous' + this.totalAnonymous;
	},
	
	getWindowGroupName : function(w) {
		return  w.options.group ? w.options.group : this._getAnonymousGroupName();
	},
	
	getWindowGroup : function(w) {
		
		groupName = this.getWindowGroupName(w);
		if (typeof(this.windowGroups[groupName]) === 'undefined') {
			this.windowGroups[groupName] = [];
		}
		
		return this.windowGroups[groupName];
	},
	
	getWindowGroupIndex : function(w, g) {
		for(var i=0,j=g.length; i < j; i++) {
			if (w === g[i]) return i;
		}
		
		return -1;
	},
	
	isWindowInGroup : function(w, g) {
		
		var ix = this.getWindowGroupIndex(w, g);
		
		return (ix >= 0);
		
	},
	
	pushOverlay : function(ov) {

		if (this.overlays.length > 0) {
			this.overlays[this.overlays.length-1].hide();//fadeOut('slow');
			ov.show();
		} else {
			ov.fadeIn('medium');
		}
				
		this.overlays.push(ov);
		// Return reference to overlay
		return ov;
		
	},
	
	popOverlay : function() {

		if (this.overlays.length > 0) {
			var self = this;
			
			if (this.overlays.length > 1) {
				this.overlays[this.overlays.length-2].show();
				this.overlays[this.overlays.length-1].hide();
			} else {
				this.overlays[this.overlays.length-1].fadeOut();
			}
			
			this.overlays.pop();
		}
	},
	
	registerWindow : function(w) {
		var windowGroup = this.getWindowGroup(w);
		
		if (!this.isWindowInGroup(w, windowGroup)) {
			windowGroup.push(w);
		}
		
		if (w.options.modal && windowGroup.length == 1) {
			//w.overlay.fadeIn('slow');
			var ov = this.pushOverlay(w.overlay);
		}
	},
	
	unregisterWindow : function(w) {
		
		var windowGroup = this.getWindowGroup(w);
		
		if (this.isWindowInGroup(w, windowGroup)) {

			windowGroup.splice(this.getWindowGroupIndex(w,windowGroup),1);

		}
		
		if (w.options.modal) {

			this.popOverlay();
			//windowGroup.length == 0) w.overlay.fadeOut('slow');
		}
	}
	
};
CWI.WindowManager.getInstance = function() {
	if (typeof(wm) === 'undefined') {
		wm = new CWI.WindowManager();
	}
	return wm;
}

CWI.wm = CWI.WindowManager.getInstance();

(function($) {
	
$.extend($.expr[':'], {
	collapsed : function(el, i, match, arr) { return $(el).hasClass('ui-collapsed'); },
	docked : function(el, i, match, arr) { return $(el).hasClass('ui-docked'); }
});

/**
 * Creates windows that are meant to float above main content areas
 **/
$.widget('cwi.window', {
	
	options : {
		overlayClass : null,
		/* Whether a modal box can be closed by clicking on the overlay */
		closeOnOverlayClick : true,
		/*closeOnEscape:true,*/
		
		anchor : null,
		/* A selector for a dock that can be used to dock this window - not ready for production */
		dock : null,
		/* Whether or not this window should be treated like a modal box */
		modal : false,
		loadContent : false,
		/* An arbitrary string value that can be used to group windows together */
		group : null,
		/* Status bar settings */
		statusBar : {
			visible : false,
			text : ''
		},
		/* Title bar settings */
		titleBar : {
			visible : true,
			text : ''
		},
		/* Action bar settings */
		actionBar : {
			visible : false,
			buttons : {}
		},
		/* Whether the window can be moved or not */
		canMove : false,
		/* Whether the window can be resized or not */
		canResize : false,
		/* Keep track of whether the window is minimized */
		minimized : false,
		/* The width the element should be when minimized, default:auto */
		minimizedWidth : 'auto',
		/* Visible on start?  auto = don't do anything; true = show; false = don't show */
		visible : 'auto'
		
	},
	_restoreState : {
		x:null,
		y:null,
		w:null,
		h:null,
		statusBar:null,
		actionBar:null,
		parent:null
	},

	anchorLeft : function() {
		alert('anchor left');
	},
	
	_create : function() {
		
		var self = this;

		// Create central window manager
		this.windowManager = CWI.wm;
		
		var startUpState = {
			css:{}, /* Keep track of CSS elements that need to be restored on startup */
			attr:{}, /* Keep track of element attributes that need to be restored on startup */
			parent:null, /* Keep track of the previous parent */
			removeElements:[] /* Keep track of elements that are created so that they can be removed */
		};
		this.element.data('startUpState', startUpState);
		
		// Capture startup state so that it can be restored if this object is destroyed via _destroy
		startUpState.css = {
			visibility : this.element.css('visibility'),
			display : this.element.css('display'),
			position : this.element.css('position')
		};
		startUpState.attr = {
			class : this.element.attr('class'),
			title : this.element.attr('title')
		};
		
		var cssObj = {
			visibility:'hidden',
			display:'block',
			position:'absolute',
			_boxShadow:'1px 1px 5px 5px #ccc'
		};
		
		if (this.options.modal) this.element.addClass('ui-modal-window');
		else this.element.addClass('ui-window');
		this.element.css(cssObj);
		
		this.options.titleBar.text = this.options.titleBar.text || this.element.attr('title') || '';
		// Remove text so that it does not appear annoyingly over interface
		this.element.attr('title', '');
		
		//var children = this.element.children();

		this.body = $('<div />').addClass('window-body');
		this.body.html(this.element.html());
		this.element.html('');
		this.element.append(this.body);
		
		startUpState.removeElements.push(this.body);
		
		
		// Title bar icon
		this.icon = $('<span />').addClass('ui-icon');
		startUpState.removeElements.push(this.icon);
		
		// Title bar text
		this.title = $('<span />').addClass('window-title').text(this.options.titleBar.text);
		startUpState.removeElements.push(this.title);
		
		// Title bar actions
		this.btnMin = $('<div />').attr('data-action', 'minimize').addClass('window-btn btn-minimize').text('Mn');
		this.btnMax = $('<div />').attr('data-action', 'maximize').addClass('window-btn btn-maximize').text('Mx');
		this.btnRestore = $('<div />').attr('data-action', 'restore').addClass('window-btn btn-restore').text('R');
		this.btnClose = $('<div />').attr('data-action', 'close').addClass('window-btn btn-close').text('X').click(function() {self.close(); });
		
		startUpState.removeElements.push(this.btnMin, this.btnMax, this.btnRestore, this.btnClose);
		
		// Title actions
		this.headerActions = $('<div />').addClass('title-actions');
		this.headerActions.append(this.btnMin, this.btnMax, this.btnRestore, this.btnClose);
		startUpState.removeElements.push(this.headerActions);
		
		// Title bar
		/*
		this.titleBar = $('<div />').addClass('window-titlebar').append(
			this.icon,
			this.title,
			this.titleActions
		);
		*/
		this.titleBar = $('<div />').addClass('window-titlebar').append(
			this.icon,
			this.title
		);
				
		startUpState.removeElements.push(this.titleBar);
	
		// Action bar
		this.actionBar = $('<div />').addClass('window-actionbar');
		startUpState.removeElements.push(this.actionBar);
		
		// Status bar
		this.statusBar = $('<div />').addClass('window-statusbar');
		startUpState.removeElements.push(this.statusBar);
		
		// Status text
		this.status = $('<span />').addClass('window-status').text(this.options.statusBar.text);
		startUpState.removeElements.push(this.status);
		this.statusBar.append(this.status);
		
		if (this.options.actionBar.buttons) {
			
			for (var button in this.options.actionBar.buttons) {
				
				var fnClick, btnClass=false;
				var btn = this.options.actionBar.buttons[button];
				
				if (typeof(btn) == 'object') {
					fnClick = btn.click;
					btnClass = typeof(btn.class)=='undefined'?false:btn.class;
				} else if (typeof(btn) == 'function') {
					fnClick = btn;
				}
				
				var $a = $('<a href="#" />').text(button).appendTo(this.actionBar).click({
					self:self,
					fnClick:fnClick
				}, function(ev) {
					//ev.data.fnClick.apply(ev.data.self, ev);
					ev.data.fnClick.apply(ev.data.self, ev);
				});
				
				startUpState.removeElements.push($a);
				if (btnClass) $a.addClass(btnClass);
				
			}
			
		}
		
		this.element.prepend(this.headerActions);
		this.element.prepend(this.titleBar);
		this.element.append(this.actionBar);
		this.element.append(this.statusBar);
				
		var zIndex = 1000;
		
		this.element.appendTo($('body'));
		
		if (this.options.group) {
			
			$('.'+this.options.group).each(function() {
				if ($(this).css('z-index') == 'auto') {
					zIndex++;
					$(this).css('z-index', zIndex);
				}
			});
		}
		
		this.overlay = null;
		if (this.options.modal) {
			this.overlay = $('<div />').hide().appendTo($('body'));
			
			this.overlay.css({
				position:'fixed',
				left:0,
				top:0,
				right:0,
				bottom:0,
				zIndex:2000
			});
			
			// If override class found then use that
			if (this.options.overlayClass) {
				this.overlay.addClass(this.options.overlayClass);
			// Otherwise use default css 
			} else {
				this.overlay.css({
					backgroundColor:'#000',
					opacity:.3
				});
			}

			this.element.css({
				zIndex:2001,
				left:'50%',
				marginLeft:-(this.element.outerWidth()/2),
				top:'50%',
				marginTop:-(this.element.outerHeight()/2)
			});
		}
		
		
		
		this.titleBar.disableSelection();
		//this.element.disableSelection();
		
		this.element.mousedown(function() {
			if (self.options.group) {
				var $windows = $('.'+self.options.group);
				var z = self.element.css('z-index');
				
				$windows.each(function(ix) {
					if ($(this).css('z-index') > z) {
						$(this).css('z-index', $(this).css('z-index')-1);
					}
				});
				self.element.css('z-index', 1000+$windows.length+1);
			}
		});
		
		
		
	},
	_init : function() {
		
		var self = this;
		
		if (this.options.modal) {
			this.option('statusBar.visible', false);
			
			//this.titleActions.hide();
			this.icon.hide();
			//if (!this.options.titleBar.text) this.option('titleBar.visible', false);
			this.btnMax.hide();
			this.btnMin.hide();
			//this.btnClose.hide();
			this.btnRestore.hide();
			
			//this.overlay.show();
			//this.show();
			
		} else {
			this.titleBar.click(function(ev, ui) {
				if (!self.titleBar.hasClass('noclick')) {
					self.toggleCollapsed();
				}
				self.titleBar.removeClass('noclick');
				/*
				if (self.element.is(':docked')) {
					self.restoreState();
					self.element.removeClass('ui-docked');
				}
				*/
			})
		}
		
		this.element.css('visibility','visible');
		
		// Set any required dimensions on window elements
		this.refreshElementPositions();
		
		if (this.options.visible === true) {
			this.show();
		} else if (this.options.visible === false) {
			this.element.hide();
			//this.hide(); // can't run hide because it might incorrectly drop an overlay that is needed
		}
	},
	
	getNonBodyHeight : function() {
		var nonBodyHeight = 0;
		if (this.options.minimized || this.titleBar.is(':visible')) nonBodyHeight += this.titleBar.outerHeight();
		if (this.options.minimized || this.actionBar.is(':visible')) nonBodyHeight += this.actionBar.outerHeight();
		if (this.options.minimized || this.statusBar.is(':visible')) nonBodyHeight += this.statusBar.outerHeight();
		return nonBodyHeight;
	},
	
	refreshElementPositions : function() {
		if (this.element.is(':collapsed')) {
			this.option('minimized', true);
		}
		
		if (this.options.minimized) {
			this.element.css('height','auto');
			this.element.css('width',this.options.minimizedWidth);
			this.body.hide();
			this.actionBar.hide();
			this.statusBar.hide();
		} else {
			if (!this.options.statusBar.visible) this.statusBar.hide();
			if (!this.options.actionBar.visible) this.actionBar.hide();
		}
		
		if (this.options.titleBar.visible && this.options.titleBar.text.length > 0) {
			this.titleBar.show();
		} else {
			this.titleBar.hide();
		}
		
		var bodyTop = 0;
		var bodyMargin = parseInt(this.body.css('margin-top')) + parseInt(this.body.css('margin-bottom'));
		var bodyBorder = parseInt(this.body.css('border-top-width')) + parseInt(this.body.css('border-bottom-width'));
		var bodyPadding = parseInt(this.body.css('padding-top')) + parseInt(this.body.css('padding-bottom'));
		
		// var bodyHeight = this.element.height() - bodyMargin - bodyBorder - bodyPadding;
		var bodyHeight = this.element.outerHeight() - bodyMargin - bodyBorder - bodyPadding;

		bodyHeight -= this.getNonBodyHeight();
		
		this.body.css({
			height:bodyHeight
		});
		
		this.refreshResizable();
		this.refreshMovable();
	},
	
	refreshResizable : function() {
		
		if (this.options.canResize && !this.options.minimized) {
			
			var self = this;
			var possibleHandles = this.options.handles || ['n','ne','e','se','s','sw','w','nw'];
			var handles = [];
			handles = possibleHandles;
			/*
			this.element.resizable({
				handles:handles.toString(),
				autoHide:true,
				minWidth : this.element.attr('data-minwidth') || null,
				minHeight : this.element.attr('data-minheight') || (this.getNonBodyHeight()+40),
				maxWidth : this.element.attr('data-maxwidth') || null,
				maxHeight : this.element.attr('data-maxheight') || null,
				ghost : false,
				
				stop : function() {
					
				},
				resize:function() {
					self.refreshElementPositions();
				}
			});
			*/
			//this.element.resizable('option', 'minHeight', this.getNonBodyHeight()+40);
		} else {
			
			if (this.element.data('resizable')) {
				// Make sure to remove resizable from minimized elements (or elements that can no longer be resized)
				this.element.resizable('destroy');
			}
			
		}
		
	},
	
	refreshMovable : function() {
		if (this.options.canMove) {
			var self = this;
			this.element.draggable({
				handle:this.options.moveHandle || null, /*'.window-titlebar,.window-statusbar,.window-actionbar',*/
				_containment:[0,0, 600, 500],
				containment : 'window',
				start : function(ev, ui) {
					$('body').disableSelection();
					self.titleBar.addClass('noclick');
				},
				cancel:'.window-body',
				stop : function() {
					$('body').enableSelection();
					//self.titleBar.removeClass('noclick');
				}
			});
		}
	},
	
	minimize : function() {
		if (!this.element.hasClass('ui-collapsed')) {
			this._captureRestoreState();
			this.option('minimized', true);
			this.element.addClass('ui-collapsed');
			this.refreshElementPositions();
			
			// Store on dock
			if (this.options.dock) {
				var $dock = $(this.options.dock);
				
				if ($dock.length > 0) {
					
					/*
					if (!$dock.hasClass('ui-draggable')) {
						$dock.draggable();
					}
					*/
					
					// Whether the dock is horizontal or vertical
					var dockHorizontal = ($dock.width() >= $dock.height());
					
					var cssContainer = {
						position:'static',
						height:this.element.height(),
						width:this.element.width()
					};
					
					if (dockHorizontal) {
						var maxWidth = 200;
						if (cssContainer.width > maxWidth) cssContainer.width = maxWidth;
						cssContainer.float = 'left';
						cssContainer.margin = '0 1px 0 0';
					} else {
						var maxHeight = 30;
						if (cssContainer.height > maxHeight) cssContainer.height = maxHeight;
						cssContainer.margin = '0 0 1px 0';
					}
					
					var cssPlaceHolder = $.extend({}, cssContainer, {
						backgroundColor:'#ccc',
						border:'1px solid #000',
						opacity:.5
						
					});
						
					var $div = $('<div />').css(cssPlaceHolder).appendTo($dock);
					
					if (this.element.css('top') == 'auto') this.element.css('top', this.element.offset().top);
					
					/*
					this.element.css({
						width:cssContainer.width,
						height:cssContainer.height
					});
					*/
					
					this.element.animate({
						left:$div.offset().left,
						top:$div.offset().top
					}, 250, function() {
						$div.remove();
						$(this).css(cssContainer).appendTo($dock).addClass('ui-docked');
					});
					
				}
			}
		}
	},
	
	_captureRestoreState : function() {
		this._restoreState = {
			left:this.element.position().left,
			top:this.element.position().top,
			width:this.element.width(),
			height:this.element.height(),
			position:this.element.css('position'),
			margin:this.element.css('margin'),
			statusBar:this.statusBar.is(':visible'),
			actionBar:this.actionBar.is(':visible'),
			parent:this.element.parent()
		};
	},
	
	restoreState : function() {
		this.option('minimized', false);
		this.element.removeClass('ui-collapsed');
		
		this.element.width(this._restoreState.width || 'auto');
		this.element.height(this._restoreState.height || 'auto');
		this.element.css({
			position : this._restoreState.position,
			left : this.element.offset().left,
			top : this.element.offset().top
		});
		
		if (this.element.parent() == this._restoreState.parent) {
			//alert('same');
		} else {
			this._restoreState.parent.append(this.element);
		}
		
		// Hide element
		//this.element.css('opacity', 0);
		
		this.body.show();
		if (this.options.statusBar.visible) this.statusBar.show();
		if (this.options.actionBar.visible) this.actionBar.show();
		
		this.refreshElementPositions();
		
		this.element.animate({
			left:this._restoreState.left,
			top:this._restoreState.top
		}, 250);
	},
		
	toggleCollapsed : function() {
		if (this.element.is(':collapsed')) this.restoreState();
		else this.minimize();
	},
	
	_handleModalClick : function(ev, obj2) {
		ev.data.window.close();
		//this.close();
	},
	
	show : function() {
		
		this.windowManager.registerWindow(this);
		
		if (this.options.modal && this.options.closeOnOverlayClick) {
			$(this.overlay).bind('click', {window:this}, this._handleModalClick);
		}
		this.element.show();
		this.refreshElementPositions();
	},
	
	hide : function() {
		this.windowManager.unregisterWindow(this);
		//if (this.options.modal) this.overlay.fadeOut('fast');
		if (this.options.modal) {
			$(this.overlay).unbind('click', this._handleModalClick);
		}
		this.element.hide();
	},
	
	close : function() {
		this.hide();
		//this.destroy();
	},
	
	_setOption : function(key, value) {
		// Call super
		$.Widget.prototype._setOption.apply(this,arguments);
		
		switch (key) {
			case 'actionBar.visible':
				if (value) {
					if (!this.options.minimized) this.actionBar.show();
				} else {
					this.actionBar.hide();
				}
				this.options.actionBar.visible = value;
				this.refreshElementPositions();
				break;
				
			case 'statusBar.visible':
				if (value) {
					if (!this.options.minimized) this.statusBar.show();
				} else {
					this.statusBar.hide();
				}
				this.options.statusBar.visible = value;
				this.refreshElementPositions();
				break;
				
			case 'statusBar.text':
				this.status.text(value);
				this.options.statusBar.text = value;
				break;
			
			case 'title':	
			case 'titleBar.text':
				this.title.text(value);
				this.options.titleBar.text = value;
				break;
		}
	},
	_destroy : function() {
	}
});

//$.widget('cwi.modalWindow', $.cwi.window, {
$.widget('cwi.modal', $.cwi.window, {
	options: { 
		modal : true,
		visible : true,
		canMove : false
	}
});

})(jQuery);