var DIR_WS_HOME = '/';

var editControlObject = null;
function strToSeoTag(url_to_use) {
	url_to_use = url_to_use.replace(/^the | is | of | a | am /ig, " ");
	url_to_use = url_to_use.replace("_", "-");
	url_to_use = url_to_use.toLowerCase();
	url_to_use = url_to_use.replace(/[^0-9a-zA-z \-]+/g, "");
	url_to_use = url_to_use.replace(/[ ]+/g, "-");
	url_to_use = url_to_use.replace(/^-+/g, ""); // Remove prepended dashes
	return url_to_use;
}
function strToMachineKey(str) {
	maching_key = str.toLowerCase();
	maching_key = maching_key.replace(/[^0-9a-z \-]+/g, "");
	maching_key = maching_key.replace(/[ ]+/g, "_");
	return maching_key;
}
var loadScripts = []; // Keep track of javascript files being loaded so that we do not load the same file twice (for example, if the same control class is loaded twice)

/*
function editControl(control_to_update, page_control_id, edit_mode) {
	var tm = new Date();
	var url = '/admin/controlaction.html?pagecontrol=' + page_control_id + '&editmode='+edit_mode;
	url += '&controlid='+control_to_update;
	url += '&accesstime='+tm;
	editControlObject = control_to_update;
	getContentFromUrl(url, handleControlEdit);
}
*/
/*
function handleControlEdit() {
	if(this.readyState == 4 && this.status == 200 && this.responseText != null) {
		var control = document.getElementById(editControlObject);
		editControlObject = null;
		control.outerHTML = this.responseText;
	}
}
function getContentFromUrl(url, handler) {
	// Handler: if(this.readyState == 4 && this.status == 200 && this.responseXML != null) {
	var client = new XMLHttpRequest();
	if (handler) client.onreadystatechange = handler;
	client.open("GET", url);
	client.setRequestHeader("Cache-Control", "no-cache");
	client.send();
}
*/
/*
var saveAndUpdateControlForm_replaceObj;
function saveAndUpdateControlForm(formId, replaceObj) {

	var formObj = document.getElementById(formId);
	var params = buildQueryString(formObj);
	var saveAndUpdateControlForm_replaceObj = document.getElementById(replaceObj);
	var url = '/admin/controlaction.html';
	var client = new XMLHttpRequest();
	client.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
	
		}
	}
}

function buildQueryString(formObject) {
	frm		= formObject
	var query	= "";
	for (e=0;e<frm.elements.length;e++) {
		if (frm.elements[e].name!='') {
			//query += (query=='') ? '?':'&';
			query += (query=='') ? '':'&';
			query += frm.elements[e].name + '=' + escape(frm.elements[e].value);
		}
	}
	
	return query;
}
*/
if (typeof(CWI) == 'undefined') var CWI = {};
/*
CWI.Window = Class.extend({
	openPanel : function() {},

	addPanel : function() {}
});

CWI.Window.Panel = Class.extend({
	__construct : function() {
		 this.canMove = false;
	}
});
*/

/*
 * fixed panel
 * floating panel (can be attached to element and possibly move with the top or bottom of attached object as the page is scrolled)
 * can be launched from control?  control_obj.createPanel('obj').
 * resizable
 * draggable
 * docked
 */
$(document).ready(function() {
	/*
	//var wm = CWI.Window.getWindowManager();

	$body = $('body');
	
	var restore, $placeholder, $test, $overlay;
	
	function isolate() {
		$overlay = $('<div />').css({
			position:'fixed',
			top:0,
			right:0,
			bottom:0,
			left:0,
			backgroundColor:'#fff',
			opacity:.9
		});
		$body.append($overlay);
		
		$test = $('#ph_main_22');
		var p = $test.position();
		
		restore = {
			parent:$test.parent(),
			position:$test.css('position'),
			left:$test.css('left'),
			top:$test.css('top'),
			width:$test.css('width'),
			height:$test.css('height')
		};
		
		$placeholder = $('<div id="placeholder" />').css({
			position:$test.css('position'),
			left:$test.css('left'),
			top:$test.css('top'),
			width:$test.outerWidth(),
			height:$test.outerHeight()
		});
		
		$test.before($placeholder);
		
		$test.css({
			position:'fixed',
			left:p.left,
			top:p.top,
			width:$test.outerWidth(),
			height:$test.outerHeight()
		});
		$test.appendTo($body);
	}
	var $panel = $('<div />').css({
		position:'fixed',
		top:0,
		color:'#fff',
		right:0,
		width:'150px',
		height:'500px',
		backgroundColor:'#000'
	});
	$body.append($panel);
	////////////////////////////////////
	var $a = $('<div />').text('Toggle Content').click(function() {
		$('#ph_main_22').toggle();
	}).css('color','#fff');
	$panel.append($a);
	////////////////////////////////////
	var $a = $('<div />').text('Toggle Form').click(function() {
		$('#ph_main_32').toggle();
	}).css('color','#fff');
	$panel.append($a);
	////////////////////////////////////
	var $a = $('<div />').text('Isolate').click(function() {
		isolate();
		$body.append($panel);
	}).css('color','#fff');
	$panel.append($a);
	////////////////////////////////////
	var $a = $('<div />').text('Restore').click(function() {
		$test.css(restore);
		$placeholder.before($test);
		$placeholder.remove();
		$overlay.remove();
	}).css('color','#fff');
	$panel.append($a);
	////////////////////////////////////
	var $a = $('<div />').text('Get Config Value').click(function() {
		alert('Currently: ' + ph_main_22.getConfigValue('headerFormat'));
	}).css('color','#fff');
	$panel.append($a);
	////////////////////////////////////
	
	$debug = $('<div />');
	$panel.append($debug);
	$panel.draggable();
	
	$test = $('#ph_main_32');
	$(window).bind('scroll', function() {
		
		var elTop = $test.offset().top;
		var elHeight = $test.outerHeight();
		var elBottom = elTop + elHeight;
		
		var scrollTop = Math.abs($(window).scrollTop());
		var scrollBottom = scrollTop + $(window).height();
		
		var onScreen = (elTop < scrollBottom && elBottom > scrollTop);
		
		//$debug.html(elTop + '-' + elBottom + '<br />' + scrollBottom + '-'+scrollTop + '<br />onScreen: ' + (onScreen?'Yes':'No'));
		if (onScreen) $panel.show();
		else $panel.hide();
		
	});
	*/
});
CWI.Page = Class.extend({
	
	__construct : function() {
		// Keep track of objects pending save
		this.pendingSaves = [];
	},
	/**
	 * Notify the page that some content has changed
	 **/
	contentPendingSave : function() {
		this.pendingSaves.push('save');
	},
	
	/**
	 * Notify the page that a piece of content that needed to saved has been saved
	 **/
	contentSaved : function() {
		this.pendingSaves.pop();
	},
	
	onBeforeUnload : function() {
		if (this.pendingSaves.length > 0) {
			return 'WARNING: You may have made changes on this page that will be lost.  Are you sure you want to leave this page?';
		}
		return;
	}
	
	
});

var current_page = new CWI.Page();

var ControlContainer = Class.extend({
	
	__construct : function(container_id, page_id) {
		
		this.containerId = container_id;
		this.pageId = page_id;
		this.controls = new Array();
		//this.obj = document.getElementById(container_id);
		this.addMenuShown = false;
		this.addMenuTimer = null;
		this.overNewOptions = false;
		this.controlContainerId = container_id; // Temporary - this should be replaced by the code that constructs this ControlContainer
		//this.maxSortorder = 0; // Keeps track of the maximum sort order for a control
		this.totalNew = 0; // Generally will equal this.controls.length+1; however, this number may be larger if a new control has been added but not saved - in which case maxSequence is increased by 1
		this.templateId = null;
	},
	
	getContainerId : function() { return this.containerId; },
	getControlContainerId : function() { return this.controlContainerId; },
	getPageId : function() { return this.pageId; },
	getObj : function() { return document.getElementById(this.getContainerId()) },
	getTemplateId : function() { return this.templateId; }, // Only used when editing templates
	setTemplateId : function(template_id) { this.templateId = template_id; }, // Only used when editing templates
	addNewControl : function(control_type, edit_context) {
		
		this.totalNew ++;
		
		var url = DIR_WS_HOME + 'controlaction.html';
		var values = {}
		values.pageid = this.getPageId();
		values.placeholder = this.getContainerId();
		values.editmode = 'Admin';
		values.editcontext = edit_context;
		values.windowmode = 'Inline';
		values.controltype = control_type;
		values.sortorder = this.controls.length + 1;
		//values.newsequenceid = 'new'+this.totalNew; // Unique ID generated for new controls
		values.newcontrolid = values.placeholder + '_new' + this.totalNew;
		values.templateid = this.getTemplateId();
	
		var control_container = $('#' + this.getControlContainerId());
		
		/*
		jQuery.get(url, values, function(data) {
			control_container.append(data);
		});
		*/
	
		var self = this;
		var responseType = Control.RESPONSE_TYPE_JSON;
	
		jQuery.ajax({
			url : DIR_WS_HOME + 'controlaction.html',
			data : values,
			dataType:responseType,
			type:'get',
			success : function(data) {
				
				/**
				 * Attaches HTML to container
				 **/
				function attach_html() {
					// Add anchor to new control
					//data.html = '<a name="' + values.newcontrolid + '"></a>' + data.html;
					// Add new control to page
					//control_container.append($(data.html).css('visibility','hidden'));
					control_container.append($(data.html));
					// Scroll to new control
					setTimeout(function() {
						var $newControl = jQuery('#'+values.newcontrolid);
						
						if ($newControl.length > 0) {
							$div = jQuery('<div />').css({
								position:'absolute',
								left:$newControl.position().left,
								width:$newControl.width(),
								height:$newControl.height(),
								top:$newControl.position().top,
								backgroundColor:'#fff',
								opacity:0
							}).addClass('editable-highlight').appendTo(jQuery('body'));
							
							//alert($newControl.position().top + ' - ' + 
							var topVisible = $(window).scrollTop();
							var bottomVisible = topVisible + $(window).height();
							var newControlTop = $newControl.position().top;
							
							// The length of time the scroll animation should take - by default assume no time
							var scrollAnimateTime = 0;
							
							// Determine whether we need to scroll in order to see the new control
							if (newControlTop < topVisible || newControlTop > bottomVisible) {
								scrollAnimateTime = 500;
								
								jQuery('body').animate({
									scrollTop: $newControl.offset().top-50
								}, scrollAnimateTime, 'linear');
								
							}
							// The "highlight" control animation should take place 80% through the scroll to animation above
							var runNextAnimation = scrollAnimateTime * .8; // Will equal zero if no scrolling is required (i.e. the control is already on the screen
							
							// Highlight jcontrol
							setTimeout(function() {
								// Fade "highlight" in
								$div.animate({
									opacity:.8
								}, 250, function() {
									
									// Fade overlay out
									$div.animate({
										opacity:0
									}, 750, function() {
										$div.remove();
									});
								});
								
							}, runNextAnimation);
						}
						
					},50);
					
				}
				// Load any required javascript files
				
				if (data.javascriptFiles) {
					
					var txt = '';
					
					var num_remaining = 0;
					for(var i in data.javascriptFiles) {
						var include_file = true;
						
						//if (document.scripts) {
							var num_scripts = loadScripts.length;
							for (var s=0; s < num_scripts; s++) {
								if(data.javascriptFiles[i] == loadScripts[s].src) include_file = false;
							}
						//}
						
						//txt += data.javascriptFiles[i] + ' - ' + (include_file?'Yes':'No') + '\n';
						
						if (include_file) {
							num_remaining ++;
							loadScripts[loadScripts.length] = {
								loaded : false,
								src: data.javascriptFiles[i] // Add script to download queue
							};
						}
					}
					var already_attached = false;
					// Count down the number of items remaining to be loaded and attach the HTML only after all events have been loaded
					function one_less_script(status) {
						// Decrease remaining scripts by one
						num_remaining --;
						
						// If all scripts have been loaded then attach the HTML
						if (num_remaining == 0) {
							if (!already_attached) {
								already_attached = true; // Make sure this doesn't get called twice if two "loaded" events fire at the same time (only found the problem in IE)
								attach_html();
							}
						}
					}
					
					if (num_remaining == 0) attach_html();
					
					for(var i in loadScripts) {
						if (!loadScripts[i].loaded) { // Mark script as loaded so that it will not be loaded twice
							loadScripts[i].loaded = true;
							// Load required scripts
							jQuery.ajax({
								dataType:'script', 
								cache:true,
								url:loadScripts[i].src,
								success:function() {
									one_less_script('success')
								},
								error:function() {
									one_less_script('error')
								}
							});
						}
					}
				// Otherwise call attach_html directly
				} else {
					attach_html();
				}
	
			},
			error : function(data, textStatus, errorThrown) {
				alert('Unable to load.  Please contact support: ' + textStatus);
				//failed_callback.call(self, data);
			}
		});
	},
	
	addFavorite : function(duplicate_page_control_id) {
		
		this.totalNew ++;
		
		var url = DIR_WS_HOME + 'controlaction.html';
		var values = {}
		values.duplicatepagecontrolid = duplicate_page_control_id;
		values.pageid = this.getPageId();
		values.placeholder = this.getContainerId();
		values.editmode = 'Admin';
		//values.editcontext = edit_context;
		values.windowmode = 'Inline';
		values.sortorder = this.controls.length + 1;
		
		//values.newsequenceid = 'new'+this.totalNew; // Unique ID generated for new controls
		values.newcontrolid = values.placeholder + '_new' + this.totalNew;
		values.templateid = this.getTemplateId();
	
		var control_container = $('#' + this.getControlContainerId());
		
		/*
		jQuery.get(url, values, function(data) {
			control_container.append(data);
		});
		*/
	
		var self = this;
		var responseType = Control.RESPONSE_TYPE_JSON;
		jQuery.ajax({
			url : DIR_WS_HOME + 'controlaction.html',
			data : values,
			dataType:responseType,
			type:'get',
			success : function(data) {
				control_container.append(data.html);
				//alert('success: ' + data.html);
				
				//success_callback.call(self, data);
			},
			error : function(data) {
				alert('Unable to load.  Please contact support: ' + data);
				//failed_callback.call(self, data);
			}
		});	
	},

	getWidth : function() {
		//return $('#' + this.getContainerId;
		return $('#' + this.containerId).width();
	},

	addControl : function(control) {
		//control_id, page_control_id
		var count = this.controls.length;
		control.setOrder(count);
		control.setParent(this);
		this.controls[count] = control;
	},
	
	setControlContainerId : function(control_container_id) {
		this.controlContainerId = control_container_id;
	},

	toggleNewContentMenu : function() {
		jQuery('.editable-region-add-options:first', $(this.getObj())).toggle();
	},
	
	moveUp : function(index) {
		
		// On screen shift
		var top_control = this.controls[index-1];
		var bottom_control = this.controls[index]; // Selected
		//alert(bottom_control.getPageControlId());return;
		top_control.order += 1;
		bottom_control.order -= 1;
	
		/*
		var top_object = document.getElementById(top_control.controlId);
		var bottom_object = document.getElementById(bottom_control.controlId);
		*/
		var top_object = $('#' + top_control.controlId);
		var bottom_object = $('#' + bottom_control.controlId);
	
		
		// Swap
		this.controls[index] = top_control;
		this.controls[index-1] = bottom_control;
	
		//var parentDiv = bottom_object.parentNode;
		//parentDiv.insertBefore(bottom_object, top_object);
		bottom_object.insertBefore(top_object);
		
		bottom_object.insertBefore(top_object);
		
		// Post db change
		var url = DIR_WS_HOME + 'admin/pagecontrols/changeorder.html?pagecontrolid=' + bottom_control.getPageControlId() +'&direction=up';
		//alert(url);return;
		/*var client = new XMLHttpRequest();
		client.control = this;
		client.open("GET", url);
		client.send();
		*/
		$.ajax({
		       type:"GET",
		       url:url
		       });
	
	},
	
	moveDown : function(index) {
	
		// On screen shift	
		var top_control = this.controls[index];
		var bottom_control = this.controls[index+1]; // Selected
		//alert(top_control.getPageControlId());return;
		top_control.order += 1;
		bottom_control.order -= 1;
	
		/*
		var top_object = document.getElementById(top_control.controlId);
		var bottom_object = document.getElementById(bottom_control.controlId);
		*/
		var top_object = $('#' + top_control.controlId);
		var bottom_object = $('#' + bottom_control.controlId);
	
		// Swap
		this.controls[index+1] = top_control;
		this.controls[index] = bottom_control;
	
		//var parentDiv = bottom_object.parentNode;
	
		//parentDiv.insertBefore(bottom_object, top_object);
		bottom_object.insertBefore(top_object);
	
		// Post db change
		var url = DIR_WS_HOME + 'admin/pagecontrols/changeorder.html?pagecontrolid=' + top_control.getPageControlId() +'&direction=down';
		/*
		var client = new XMLHttpRequest();
		client.control = this;
		client.open("GET", url);
		client.send();
		*/
		$.ajax({
		       type:"GET",
		       url:url
		       });
	}
	
});

/**
 * A control class to manage an individual control and to allow it to communicate back with the PHP control class
 *
 * The most important methods are those that handle the control events: onConfigValueChanged(...), onSaving(...), and onSaved(...).  When these are overridden by an overriding class the method should always call this._parent(...) to make sure that parent class methods are executed
 * Called when a config value has changed in real-time.
 * @var jQueryEvent event
 * @var object data {
 * 	@var string configName The config value key for the value that has changed
 *	@var string oldValue The value before the change
 * 	@var string newValue The value now that it has changed
 * }
 onConfigValueChanged(event, data);
 * Not fully implemented.  Currently called when Control::sendForm() is called
 * @var jQueryEvent event
 * @var object data {
 * 	@var object submitValues An object of values to be saved
 *	@var bool save Whether the object should be saved or not
 * }
 onSaving(event, data);
 * Called after a control's form data is saved.
 * @var jQueryEvent event
 * @var object data {
 *	@var object data An object as returned by PHP
 * }
 */
onSaved(event, data);
var Control = Class.extend({
	__construct : function(control_id, page_control_id, edit_mode, edit_context, window_mode, control_type) {
		
		this.debugMode = false;
		this.setControlId(control_id);
		this.formId = this.getObjName('config_form');
		//this.pageControlId;
		//this.controlType = null;
		this.setPageControlId(page_control_id);
		this.setEditMode(edit_mode);
		this.setEditContext(edit_context);
		this.setWindowMode(window_mode);
		this.setControlType(control_type);
		this.busy = false;
		this.editableFields = new Array();
		this.config = new Array();
		this.origConfig = new Array(); // Keep track of original config values
		this._configTriggers = new Array(); // Private keep track of callbacks to make when a config value is changed
		//this._saveTriggers = new Array(); // Private keep track of callbacks to make when a form is submitted
		this.templateId = null;
		
		// Bind events to handlers (auto binded here for convenience)
		$(this).bind('saving', this.onSaving);
		$(this).bind('saved', this.onSaved);
		
		// Need more testing on this... $(this).bind('configValueChanging', this.onConfigValueChanging);
		$(this).bind('configValueChanged', this.onConfigValueChanged);
		
		this.pendingSave = false; // Whether this is control is pending any saves
		// Run initialization function 
		this.init();
	},
	
	// Overridden to provide any require functionality at startup
	init : function() {},
	/**
	 * Allows communication with the PHP control class
	 * @param string action An action to be called on the PHP control class
	 * @param object valueObj An object full of values to be communicated back to the PHP control class
	 * @param function success_callback What to do when the call is successful
	 * @param function failed_callback What to do if the call fails
	 */
	callAction : function(action, valueObj, success_callback, failed_callback) {
		var responseType = Control.RESPONSE_TYPE_JSON;
		if (!success_callback) success_callback = function() {};
		if (!failed_callback) failed_callback = function() { alert('There was a problem processing your request.  Please contact support if you continue to experience problems.'); }
		if (!valueObj) valueObj = {};
		/**
		 * Combine all values that are required to be posted back to the control class
		 */
		var values = jQuery.extend(
			{
				'callaction':action,
				'responsetype':responseType
			},
			this.getControlFields(),
			valueObj
		);
		/*
		var control_fields = this.getControlFields();
		var form_fields = this.getFormFields(document.getElementById(this.getObjName('form')));
		var values = jQuery.extend(submitValues, control_fields, form_fields);
		*/
		var self = this;
		jQuery.ajax({
			url : DIR_WS_HOME + 'controlaction.html',
			data : values,
			dataType:responseType,
			type:'post',
			success : function(data) {
				if (data.success) {
					success_callback.call(self, data); // same as: success_callback(data) <- self becomes "this" in function
				} else {
					failed_callback.call(self, data);
				}
			},
			error : function(data) {
				failed_callback.call(self, data);
			}
		});
	},

	getPageControlId : function() { return this.pageControlId; },
	setPageControlId : function(page_control_id) { this.pageControlId = page_control_id; },
	
	getEditMode : function() { return this.editMode; },
	setEditMode : function(edit_mode) { this.editMode = edit_mode; },
	
	getEditContext : function() { return this.editContext; },
	setEditContext : function(edit_context) { this.editContext = edit_context; },
	
	getWindowMode : function() { return this.windowMode; },
	setWindowMode : function(window_mode) { this.windowMode = window_mode; },
	
	getTemplateId : function() { return this.templateId; },
	setTemplateId : function(template_id) { this.templateId = template_id; },
	
	getControlId : function() { return this.controlId; },
	setControlId : function(control_id) { this.controlId = control_id; },
	
	getControlType : function() { return this.controlType; },
	setControlType : function(control_type) { this.controlType = control_type; },
	
	getConfig : function() { return this.config; },
	getConfigValue : function(name) { return this.config[name]; },
	initConfigValue : function(name, value) { // same as setConfigValue except that it won't trigger a change event
		this.origConfig[name] = value;
		this.config[name] = value;
	},
	setConfigValue : function(name, value) {
	
		if (value != this.origConfig[name]) {
			this.contentChanged();
		}
		var old_value = this.config[name];
		var new_value = value;
		/*
		var response = this.configValueChange(name, old_value, new_value); // Trigger event to call all attached event listeners
		
		this.config[name] = value;
		*/
		var args = {
			configName	: name,
			oldValue	: old_value,
			newValue	: new_value,
			change		: true /* Can be set to false to block the change from taking place */
		};
		//Need more testing on this... $(this).trigger('configValueChanging', args);
		
		// Only change the value if it has not been blocked
		if (args.change) {
			this.config[name] = args.newValue;
			$(this).trigger('configValueChanged', args);
		}
		
	},
	/*
	onConfigValueChange : function (name, handler) { // Add handler for when a specific config value is changed
		if (!this._configTriggers[name]) this._configTriggers[name] = new Array();
		this._configTriggers[name].push(handler);
	},
	onSave : function (handler) {
		//this._saveTriggers.push(handler);
	},
	*/
	
	configValueChange : function(name, old_value, new_value) {
		if (this._configTriggers[name]) {
			for(i=0; i < this._configTriggers[name].length; i++) {
				var func = this._configTriggers[name][i];
				var response = func({
					control		: this,
					configName	: name,
					oldValue	: old_value,
					newValue	: new_value
				}); // func(this control object, config field)
				/*
				var response = func.call(this, {
					name		: name,
					oldValue	: oldValue,
					newValue	: newValue
				});
				*/
				if (response == false) return false;
	
			}
		}
		return true;
	},
	copyControl : function() {
		alert('Copy page control: ' + this.getPageControlId());
		// DIR_WS_HOME + 'admin/pagecontrols/copytoclipboard
	},
	
	getParent : function() { return this.parent; },
	setParent : function(parent) { this.parent = parent; },
	
	getOrder : function() { return this.order; },
	setOrder : function(order) { 	this.order = order; },
	
	moveUp : function() { if (this.order > 0) this.parent.moveUp(this.order); },
	moveDown : function() { if (this.order < (this.parent.controls.length-1)) this.parent.moveDown(this.order); },
	
	// Make sure that all field values are committed,
	commitAllEditableFields : function() {
		for (i = 0; i < this.editableFields.length; i++) {
			var obj = document.getElementById(this.editableFields[i][0]);
			obj.commit();
		}
	},
	revertAllEditableFields : function() {
		for (i = 0; i < this.editableFields.length; i++) {
			var obj = document.getElementById(this.editableFields[i][0]);
			obj.reset();
		}
	},
	showActionBar : function() {
		this.getObj('actionbar').slideDown();
	},
	hideActionBar : function() {
		this.getObj('actionbar').slideUp('fast');
	},
	/**
	 * Call this method to indicate that the control has changed and needs to be saved
	 **/
	contentChanged : function() {
		this.showActionBar();
		// Notify the current page that it has content pending save - only notify once
		if (!this.pendingSave) {
			this.pendingSave = true;
			current_page.contentPendingSave();
		}
	},
	
	/**
	 * Adds field to stack of editable fields.  Can be called twice on the same object - the effect is that the object is re-iniated, as might be needed when swapping out one element for a new element
	 */
	addEditableField : function(field_name, opts) { // Called from jquery.clickedit.js
		var t = this;
		var alreadyExists = false;
		
		for (i=0; i < t.editableFields.length; i++) if (t.editableFields[i][0] == field_name) {
			alreadyExists = true; // Don't re-add existing object to stack if it was already added
			opts = t.editableFields[i][1];
		}
		
		$('#' + field_name).clickedit(opts);
		
		$('#' + field_name).bind('contentchanged', function() {
			t.contentChanged();
		});
		
		if (!alreadyExists) this.editableFields.push(new Array(field_name, opts));
	},
	
	/*
	removePageControl : function() {
		var control = document.getElementById(this.getControlId());
		control.outerHTML = '';
	
		var tm = new Date();
		var url = DIR_WS_HOME + 'admin/pagecontrols/remove.html?pagecontrolid=' + this.getPageControlId();
	
		$.ajax({
		       type:"GET",
		       url:url
		       });
		
	}
	*/
	toggleShowConfig : function() {
		var configBar = this.getObj('configbar');
		
		if (configBar.slideUp) { // Make sure slide up functionality exists
			if (configBar.is(':visible')) {
				configBar.slideUp();
			} else {
				configBar.slideDown();
			}
		} else {
			configBar.toggle();
		}
	},
	revertChanges : function() {
		this.revertAllEditableFields();
	},
	
	reload : function() {
		this.setBusy('Would reload control...');
		var t = this;
		setTimeout(function() { t.doneBusy(); }, 1000);
	},
	isNew : function() { return (this.getPageControlId().length == 0); },
	getFormFields : function(formObj) {
		var values = {};
		if (formObj) {
			for (e=0; e < formObj.elements.length; e++) {
	// Removed because it was messing up fields where the value had to be blanked out:	if (formObj.elements[e].name != '') {
					values[formObj.elements[e].name] = formObj.elements[e].value;
	//			}
			}
		}
		return values;
	},
	getControlFields : function() {
		var values = {};
		values.pagecontrolid = this.getPageControlId();
		values.pageid = this.getParent().getPageId();
		values.templateid = this.getTemplateId();
		values.placeholder = this.getParent().getContainerId();
		values.controltype = this.getControlType();
		values.editmode = this.getEditMode();
		values.windowmode = this.getWindowMode();
		
		if (values.pagecontrolid.length == 0) { // new control
			values.newcontrolid = this.getControlId();
		}
		
		var config = this.getConfig();
		for(var config_name in config) {
			values['config[' + config_name + ']'] = config[config_name];
		}
	
		return values;
	},
	
	/**
	 * Cancelling a saving process can be achieved using:
	 * ev.cancelPropagation();
	 * data.save = false;
	 **/
	onSaving : function(ev, data) {},
	onSaved : function(ev, data) {},
	/**
	 * Called before the config value is committed
	 * Can be cancelled via:
	 * ev.cancelPropagation();
	 * data.change = false;
	 **/
	onConfigValueChanging : function(ev, data) {},
	/**
	 * Called after a config value has been changed
	 **/
	onConfigValueChanged : function(ev, data) {
		switch (data.configName) {
			case 'class':
				oldValue = data.oldValue;
				newValue = data.newValue;

				if (oldValue) this.getObj().removeClass(oldValue);
				this.getObj().addClass(newValue);
				break;
		}
	},
	
	sendForm : function() {
		this.commitAllEditableFields();
		
		var submitValues = {'callaction':'postback'};
		
		var saveData = {
			submitValues : submitValues,
			save : true /* Set to false to cancel save */
		};
		
		$(this).trigger('saving', saveData);

		if (!saveData.save) return false;
		
		this.setBusy('Saving...');
		//var formObj = document.getElementById(this.getControlId()+'_form');
		//if (formObj) {
			var control_fields = this.getControlFields();
			var form_fields = this.getFormFields(document.getElementById(this.getObjName('config_form')));
			var values = jQuery.extend(submitValues, control_fields, form_fields);
	
	
			var t = this;
			if (!this.debugMode) t.hideActionBar();
			jQuery.ajax({
				url : DIR_WS_HOME + 'controlaction.html',
				data : values,
				dataType:'json',
				type:'post',
				success : function(data) {
					//alert(data);return;
					if (data.success) {
						if (data.onPostSuccess) {
							eval('var callback_function = ' + data.onPostSuccess); // Retrieve callback function
							//data.callback.apply(t); // Call function using local context
							var return_value = callback_function.apply(t);
	
							if (typeof(return_value) == 'undefined') return_value = true;
	
							if (return_value) { // Mark done busy unless function returns false
								t.doneBusy();
							}
						} else t.doneBusy();// Mark done editing
	
						if (data.pageControlId) {
							t.setPageControlId(data.pageControlId);
						}
						for (var name in t.config) {
							t.origConfig[name] = t.config[name]; // Reset configuration values to newly saved values so that we can keep track of when new changes are made
						}
						
						// Notify the current page that the content has been successfully saved
						current_page.contentSaved();
						this.pendingSave = false;
						
						// Let everyone know that we have been saved
						$(t).trigger('saved', data);
					} else {
						if (data.errors) { // array
							if (data.errors.length > 0) {
								var error_message = '';
								for(i=0; i < data.errors.length; i++) {
									error_message += data.errors[i] + "\n";
								}
								alert(error_message);
							}
	
						} else {
							alert('The request failed for some reason.');
						}
					}
					if (data.debug) { // array
						if (data.debug.length > 0) {
							var debug_message = '';
							for(i=0; i < data.debug.length; i++) {
								debug_message += data.debug[i] + "\n";
							}
							alert(debug_message);
						}
					}
				},
				error : function() {
					alert('There was an error sending your request.  Please contact support.');
					t.setBusy('An error occurred.  Please contact support.');
				}
			});
	
		//} else alert('An error occured.  Please contact support.');
	
	},
	getObjName : function(name) {
		var objName = this.getControlId();
		if (name) objName += '_' + name;
		return objName;
	},
	getObj : function(name) {
	//	alert('#' + this.getControlId() + '-' + name);
		return $('#' + this.getObjName(name));
	},
	
	doneBusy : function(closing_message) {
		if (!closing_message) closing_message = 'Done';
		var t = this;
		this.getObj('message').text(closing_message);
		setTimeout(function() { t.getObj('waiting').slideUp(); }, 500);
		this.busy = false;
	},
	closeBusy : function() {
		this.getObj('message').text('Done');
		this.getObj('waiting').hide();
		this.busy = false;
	},
	
	setBusy : function(msg) {
		if (!msg) msg = 'Waiting...';
		this.busy = true;
		this.getObj('message').text(msg);
		this.getObj('waiting').show();
		/*$('#' + this.getControlId() + '-message').text(msg);
		$('#' + this.getControlId() + '-waiting').show();
		*/
	},
	
	remove : function() {
		if (confirm('Are you sure you want to delete this content?')) {
			var t = this;
			this.setBusy('Deleting...');
			
			var url = DIR_WS_HOME + 'admin/pagecontrols/remove.html?pagecontrolid=' + this.getPageControlId();
		
			$.ajax({
			       type:"GET",
			       url:url,
			       success:function() {
				       var $control = $('#' + t.getControlId());
					$control.hide('slow',function() {
						$control.remove();
					});
					t.doneBusy();
			       },
			       error:function() {
					alert('There was an internal error removing this content.  Please contact support.');
			       }
			});
		}
	},
	favorite : function() {
		var t = this;
		this.setBusy('Saving as favorite...');
		setTimeout(function() {
			t.doneBusy();
		},1000);
		
	},
	
	openEditWindow : function() {
		//var open_window = window.open('', this.getControlId(), 'status=0,toolbar=0,menubar=0,resizable=1,width=800,height=480,location=0,titlebar=0,scrollbars=1');
		//window.setTimeout(50, open_window.document.body.innerHTML = 'Loading Content...');
		//open_window.window.location.replace(DIR_WS_HOME + 'admin/controlaction.html?pagecontrol=' + this.getPageControlId() + '&editmode=Admin&windowmode=Full&htmlouterid=' + this.getControlId());
	//	var open_url = DIR_WS_HOME + 'admin/controlaction.html?pagecontrol=' + this.getPageControlId() + '&editmode=Admin&windowmode=Full&htmlouterid=' + this.getControlId();
	//	window.open(open_url, this.getControlId(), 'status=0,toolbar=0,menubar=0,resizable=1,width=800,height=480,location=0,titlebar=0,scrollbars=1')
		//alert('admin/controlaction.html?pagecontrol=' + this.getPageControlId() + '&editmode=Admin&windowmode=Full&htmlouterid=' + this.getControlId());
		//. '\', \'editControl\', \'status=0,toolbar=0,menubar=0,resizable=1,width=800,height=480,location=0,titlebar=0,scrollbars=1\')
	},
	reloadControl : function() { this.changeEditMode(this.getEditMode()); },
	handleFormSubmitResponse : function(responseText) {
		this.changeEditMode('Default');
		//editControl(this.controlId, this.pageControlId, 'Default');
		//editControl(\''. $this->getOuterId() .'\', \'' . $this->getPageControlId() . '\', \'Admin\');
		//editControl(this.controlId, 'Default');
	},
	
	changeEditMode : function(edit_mode) {
		/*
		var tm = new Date();
		var url = DIR_WS_HOME + 'admin/controlaction.html?pagecontrol=' + this.getPageControlId() + '&editmode='+edit_mode;
		url += '&controlid='+this.getControlId();
		url += '&accesstime='+tm;
		
	
		var loading = document.getElementById(this.getControlId());
		var div = document.createElement('DIV');
		div.style.width = loading.offsetWidth;
		div.style.height = loading.offsetHeight;
		div.className = 'loading';
		div.innerHTML = 'Changing edit mode...';
		
		loading.innerHTML = div.outerHTML;
		//alert(loading.offsetWidth + 'x' + loading.offsetHeight);	
		var client = new XMLHttpRequest();
		client.control = this;
		client.onreadystatechange : function() {
			if (this.readyState == 4 && this.status == 200) {
				client.control.handleChangeEditMode(this.responseText);
			}
		}
		client.open("GET", url);
		client.setRequestHeader("Cache-Control", "no-cache");
		client.send();
		*/
	},
	
	handleChangeEditMode : function(responseText) {
		var control = document.getElementById(this.getControlId());
		control.outerHTML = responseText;
	},
	
	submitForm : function() {
		/*
		var formObj = document.getElementById(this.formId);
		var params = buildQueryString(formObj);
		
		var url = '/admin/controlaction.html';
		client = new XMLHttpRequest();
		client.control = this;
		client.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				client.control.handleFormSubmitResponse(this.responseText);
			}
		}
		client.open("POST", url, true);
		client.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		client.setRequestHeader("Content-length", params.length);
		client.setRequestHeader("Connection", "close");
		client.setRequestHeader("Cache-Control", "no-cache");
		client.send(params);
		*/
	}
});

Control.RESPONSE_TYPE_HTML = 'html';
Control.RESPONSE_TYPE_JSON = 'json';


/**
 * makeContainerEditableField makes a container - requires jquery.js and jquery.editable.js (and jquery.tinymce.js)
 * @param string field_id elements id
 * @param string field_type
 * field_types
 * 	- text
 *	- textarea (not implemented)
 * 	- wysiwyg
 */
function makeContainerEditableField(container_id, field_type, field_id) {
	switch (field_type) {
		case 'text':
			$('#' + container_id).clickedit(function(content) { return content; }, {type:'text',name:field_id});
			break;
		case 'wysiwyg':
			$('#' + container_id).clickedit(function(content) { return content; }, {type:'wysiwyg',name:field_id,submit:'Save',cancel:'Cancel'});
			break;
	}			
}



/* Main Menu */
/*
var selected_menu = '';
function showRibbon(menu_key) {
	var ribbon_id = 'menu-ribbon-' + menu_key;
	var menu_id = 'menu-' + menu_key;
	if (menu_key != selected_menu) {
		if (selected_menu.length > 0) hideRibbon(selected_menu);
		$('#' + ribbon_id).fadeIn('fast');
		$('#' + menu_id).addClass('active');
		selected_menu = menu_key;
	} else {
		hideRibbon(menu_key, 'fade');
	}
}
function hideRibbon(menu_key, hide_type) {
	var ribbon_id = 'menu-ribbon-' + menu_key;
	var menu_id = 'menu-' + menu_key;
	
	if (hide_type == 'fade') {
		$('#' + ribbon_id).fadeOut();
	} else {
		$('#' + ribbon_id).hide();
	}
	$('#' + menu_id).removeClass('active');
	selected_menu = '';
}
*/
//$(document).ready(function() {
			   /*
	$('.editableregionbody').sortable({
		connectWith: '.editableregionbody',
		items: '.editablecontrol',
		tolerance:'pointer',
		handle: '.handle'
	});*/
//});

(function($) {
$.fn.clickOutside = createJQueryPlugin({
	remove : function() {
		var $this = $(this);
		$this.unbind('click', $this.data('clickOutside').insideHandler);
		$(document).unbind('click', $this.data('clickOutside').outsiderHandler);
		$this.removeData('clickOutside');
		
	},
	init : function(clickOutsideHandler) {
		
		return this.each(function() {
			
			var $this = $(this);
			var data = $this.data('clickOutside');
			if (!data) { // Initialize for first time
				$this.data('clickOutside', {
					outsideHandler:clickOutsideHandler,
					insideHandler:function(ev) {
						ev.stopPropagation();
					}
				});
				$(document).bind('click', $this.data('clickOutside').outsideHandler);
				
				$this.click(function() {
					$this.data('clickOutside').insideHandler;
				});
				
			}
		});
	}
});
})(jQuery);

window.onload = function() {
	/*
	if (!$.browser.msie) { // IE's sortable support is buggy at best, don't include
	*/
		$('.editable-control-move-handle').show();
		$('.editable-region').each(function() {
			$(this).css('padding-bottom', '20px');
			/*
			$(this).sortable({
				connectWith : '.editable-region',
				items : '.editablecontrol,.editable-control',
				handle : '.editable-control-bar,.editable-control-move-handle',
				placeholder : 'placeholder',
				_tolerance : 'pointer'
			});
			*/
			$(this).disableSelection();
		});
	/*
	} else {
		$('.editable-control-move').show();
	}
	*/
	$('.editable-control-move').show();
}

window.onbeforeunload = function() { return current_page.onBeforeUnload(); }