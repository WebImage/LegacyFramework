// JavaScript Document
if (typeof(CWI) == 'undefined') var CWI = {};

/* Simple JavaScript Inheritance
 * By John Resig http://ejohn.org/
 * MIT Licensed.
 */
// Inspired by base2 and Prototype
// CWI - Modifications: (1) renamed "init" method to "__construct" and (2) renamed "this._super" to "this._parent"; both to work a little closer to PHP's function
//(function(){
	var initializing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\b_parent\b/ : /.*/;
	// The base Class implementation (does nothing)
	this.Class = function(){};
	
	// Create a new Class that inherits from this class
	Class.extend = function(prop) {
		var _parent = this.prototype;
		
		// Instantiate a base class (but only create the instance,
		// don't run the init constructor)
		initializing = true;
		var prototype = new this();
		initializing = false;
		
		// Copy the properties over onto the new prototype
		for (var name in prop) {
			// Check if we're overwriting an existing function
			prototype[name] = typeof prop[name] == "function" && 
				typeof _parent[name] == "function" && fnTest.test(prop[name]) ?
				(function(name, fn){
					return function() {
						var tmp = this._parent;
						
						// Add a new ._parent() method that is the same method
						// but on the super-class
						this._parent = _parent[name];
						
						// The method only need to be bound temporarily, so we
						// remove it when we're done executing
						var ret = fn.apply(this, arguments);        
						this._parent = tmp;
						
						return ret;
					};
				})(name, prop[name]) :
				prop[name];
		}
		
		// The dummy class constructor
		function Class() {
			// All construction is actually done in the init method
			if ( !initializing && this.__construct )
				this.__construct.apply(this, arguments);
		}
		
		// Populate our constructed prototype object
		Class.prototype = prototype;
		
		// Enforce the constructor to be what we expect
		Class.prototype.constructor = Class;

		// And make this class extendable
		Class.extend = arguments.callee;
		
		return Class;
	};
//})();

function createJQueryPlugin(methods) {
	
	return function(method) {
		
		$.extend(this, methods);
		
		if (methods[method]) {
			
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
			
		} else if (typeof method === 'object' || typeof method === 'function' || !method) {
			
			return methods.init.apply(this, arguments);
		
		} else {
			
			$.error('Method ' + method + ' does not exist');
			
		}
	};
}

CWI.Size = function(w,h) {
	this.w = this.width = w;
	this.h = this.height = h;
};

CWI.WM = { // Window manager
	activeModal:null
};
CWI.WM.getInstance = function() {
	// Mimicks singleton functionality
	if (typeof(__cwi_window_manager__) == 'undefined') __cwi_window_manager__ = new CWI.WM.WindowManager();
	return __cwi_window_manager__;
}
CWI.WM.Window = Class.extend({
	__construct : function(id, size) {
		this.id = id;
		this.size = size;
		this.content = null;
		//this.opts.allowClickOutside
	},
	
	display : function(opts) {
		var width = this.size.width;
		var height = this.size.height;
		
		if (typeof(opts.animate) == 'undefined') opts.animate = false;
		
		animateSpeed = null;
		if (opts.animate) {
			animateSpeed = 'fast';
		}
		
		/*if (!width) width = jQuery(window).width() - 50;
		if (!width) width = 800;
		if (!height) height = jQuery(window).height() - 50;
		if (!height) height = 500;}
		*/
		var css_obj = {
			zIndex:opts.zIndex
		};
		
		if (width) {
			css_obj.width = width + 'px';
			css_obj.marginLeft = -(width/2) + 'px'
		}
		if (height) {
			css_obj.height = height + 'px';
			css_obj.marginTop = -(height/2) + 'px';
		}
	
		if ($('#'+this.id).length > 0) { // Already exists
			// prepare window object
			var window_obj = $('#' + this.id);
			if (!window_obj.hasClass('window-view')) window_obj.addClass('window-view');
			
			window_obj.css(css_obj);
			if (this.content) {
				window_obj.html(this.content);
			}
			$('body').append(window_obj);
			window_obj.show(animateSpeed); //
		} else { // Create from scratch
			/*
			window_obj = $('<div></div>').
					hide().
					attr('id', this.id).
					addClass('window-view').
					css(css_obj).
					css('width', css_obj.width).
					html(this.content).
					show(animateSpeed);
			*/
			window_obj = $('<div></div>').
					css('visibility', 'hidden').
					attr('id', this.id).
					addClass('window-view').
					html(this.content).
					hide();
			$('body').append(window_obj);
			window_obj.css('visibility', 'visible');
			window_obj.css(css_obj);
			window_obj.show(animateSpeed);
		}
	}
});

CWI.WM.ModalWindow = CWI.WM.Window.extend({
	__construct : function(id, size) {
		this._parent(id, size);
		alert('third');
	}
	/*
	CWI.WM.ModalWindow = function(cwi_wm_window, opts) { // allow escape close, whether ESC or clicking outside
		this.window = cwi_wm_window;
		//this.allowClickOutside = allow_click_outside;
		this.opts = opts;
	}
	*/
});
/*CWI.WM.ModalWindow.prototype.getWindow = function() { return this.window; }
CWI.WM.ModalWindow.prototype.allowClickOutside = function() { return this.opts.allowClickOutside; }
*/

CWI.WM.Window.createWindowWithContent = function(id, width, height, content) {
	window_size = CWI.Size;
	window_size.width = width;
	window_size.height = height;
	the_window = new CWI.WM.Window(id, window_size);
	the_window.content = content;
	return the_window;
}

CWI.WM.Window.makeContainerAWindow = function(id, width, height) {
	window_size = CWI.Size;
	window_size.width = width;
	window_size.height = height;
	the_window = new CWI.WM.Window(id, window_size);
	the_window.content = $('#' + id).html();
	return the_window;
}

CWI.WM.Modal = function() {
	this.windowStack = [];
	this.overlayObj = null;
}
CWI.WM.Modal.prototype.close = function() {
	this.popAllWindows();
	jQuery(".modal-overlay").remove();
	CWI.WM.activeModal = null;
}

CWI.WM.Modal.prototype.getCurrentWindowIndex = function() { return this.windowStack.length-1; }

CWI.WM.Modal.prototype.removeWindowAtIndex = function(index) {
	if (index > 0 && index  < this.windowStack.length) {
		this.windowStack.splice(index, 1);
	}
}

CWI.WM.Modal.prototype.popAllWindows = function() {
	if (this.windowStack.length > 0) {
		for(i=this.windowStack.length-1; i >= 0; i--) {
			jQuery('#' + this.windowStack[i].getWindow().id).hide();
		}
		this.windowStack = [];
	}
	return true;
}
CWI.WM.Modal.prototype.getOverlayObj = function() { return this.overlayObj; }

CWI.WM.Modal.prototype.addOverlay = function(allow_click_outside) {
	if ($('.modal-overlay').length == 0) {
		var overlay = $('<div></div>');
		overlay.addClass('modal-overlay');
		if (allow_click_outside) {
			overlay.click(function() {
					CWI.WM.activeModal.close();
				});
		}
		this.overlayObj = overlay;
		$('body').append(overlay);
	}
}

CWI.WM.Modal.prototype.pushWindow = function(the_window, append_options, animate) {
	var opts = {
		allowClickOutside : false,
		addOverlay : true
	}
	jQuery.extend(opts, append_options);
	
	if (typeof(opts.allowClickOutside) == 'undefined') opts.allowClickOutside = false;
	if (typeof(opts.addOverlay) == 'undefined') opts.addOverlay = true;
	if (typeof(animate) == 'undefined') animate = true;
	
	var displayOptions = {
		animate : animate
	};
		
	
	var modal_window = new CWI.WM.ModalWindow(the_window, opts)
	if (opts.addOverlay) this.addOverlay(opts.allowClickOutside);
	/*if ($('.modal-overlay').length == 0) {
		$('body').append(
			$('<div></div>')
				.addClass('modal-overlay')
				.click(function() {
					CWI.WM.activeModal.close();
				})
		);
	}*/	

	
	if (this.windowStack.length > 0) {
		$('#' + this.windowStack[this.windowStack.length-1].getWindow().id).hide(); 
	}
	this.windowStack.push(modal_window);
	
	if (CWI.WM.activeModal) {
		displayOptions.zIndex = 101 + this.windowStack.length;
		modal_window.getWindow().display(displayOptions);
		//$("body").append(modal_window.getWindow().display(displayOptions));
	}
}
CWI.WM.Modal.prototype.pushWindows = function(the_windows, append_options) {
	if (typeof(the_windows) == 'object') {
		for(i=0; i < the_windows.length; i++) {
			var animate = (i == the_windows.length-1);
			this.pushWindow(the_windows[i], append_options, animate);
		}
	}
}
CWI.WM.Modal.prototype.popWindow = function(close_overlay) {
	var length = this.windowStack.length;
	if (length > 0) {
		$('#' + this.windowStack[length-1].getWindow().id).hide('fast');
		
		this.windowStack.pop();
		length--;
		
		if (length > 0) {
			$('#' + this.windowStack[length-1].getWindow().id).show();
		} else {
			if (typeof(close_overlay) == 'undefined' || (typeof(close_overlay) != 'undefined' && close_overlay))
				this.close(); // Close modal view
		}
	}

}
CWI.WM.getModalManager = function() {
	if (!CWI.WM.activeModal) {
		CWI.WM.activeModal = new CWI.WM.Modal();
	}
	return CWI.WM.activeModal;
}
CWI.Request = {
	buildResponseOptions: function(opts) {
		// Move success and error callbacks from opts temporarily so that we only send success/error when there is not a server error
		var success = function() {};
		var error = function() {};
		
		if (opts) {
			
			if (typeof opts.success !== 'undefined') {
				success = opts.success;
				delete opts[success];
			}
			if (typeof opts.error !== 'undefined') {
				error = opts.error;
				delete opts[error];
			}
			function unknownError(response) {
				return {
					success : false,
					message : 'We apologize, but there was an error with your request.  Please try again later',
					originalResponse : response
				};
			}
			function handleError(response) {
				if (typeof error === 'function') {
					error.call(null, response);
				} else {
					alert('Error: ' + response.message);
				}
			}
			opts.success = function(response) {
				if (response.success) {
					if (typeof success === 'function') {
						success.call(null, response);
					}
				} else {
					if (typeof(response) == 'string') response = unknownError(response);
					handleError(response);
				}
			}
			opts.error = function(response) {
				response = unknownError(response);
				handleError(response);
			}
			
		} else {
			opts = {};
		}
		if (typeof(opts.type) === 'undefined') opts.type = 'POST';
		if (typeof(opts.dataType) == 'undefined') opts.dataType = 'json';
		return opts;
	},
	sendRequest: function(opts) {
		var opts = CWI.Request.buildResponseOptions(opts);
		jQuery.ajax(opts);
	},
	/*
	prepareFormSubmission: function(formObj, opts) {
		var $formObj = $(formObj);
		var dataArr = $(formObj).serializeArray();
		dataArr.push({name:'responseType',value:'json'});
		
		// If data is defined... assume it's an object
		if (typeof opts['data'] === 'object') {
			for (var i in opts.data) {
				dataArr.push({name:i, value:opts.data[i]});
			}
		}
		
		var rtn = {
			url: $formObj.attr('action'),
			type: $formObj.attr('method'),
			dataType:'json',
			success:function() {},
			error:function() {
				alert('We apologize, but there was an error with your request.  Please try again later');
			}
		};
		
		
		jQuery.extend(rtn, opts);
		rtn.data = dataArr;// Apply all form fields + passed data
		return rtn;
	},
	sendForm: function(formObj, opts) {
		var obj = CWI.Request.prepareFormSubmission(formObj, CWI.Request.buildResponseOptions(opts));
		jQuery.ajax(obj);
	}
	*/
}
