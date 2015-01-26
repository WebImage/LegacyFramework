(function($) {
	
	var formCount = 0;
	
	function FormBuilderFieldChoice(value, label, order) {
		this.value = value; // Value of the object
		this.label = label;
		this.order = order; // Sorting order
	}
	
	var FormBuilderField = Class.extend({
		__construct : function(obj_ref, id, var_key, label, type_id, config, order) {
			if (!config) config = [];
	
			this.objRef = obj_ref;
			this.id = id;
			this.label = label;
			this.varKey = var_key; // Can be used to reference field in email templates
			this.typeId = type_id;
			this.config = config;
			this.choices = [];
			this.order = order;
		},
		// Get a jQuery object reference to the selected field
		getObj : function() { return this.objRef; },
		
		// Getters
		getId : function() { return this.id; },
		
		getVarKey : function() { return this.varKey; },
		
		getTypeId : function() { return this.typeId; },
		
		getConfig : function() { return this.config; },
		
		getChoices : function() { return this.choices; },
		
		getOrder : function() { return this.order; },
		
		// Store an option value
		addChoice : function(value, label) {
			if (!label) label = value;
			
			var field_option = new FormBuilderFieldChoice(value, label, this.choices.length);
			this.choices[this.choices.length] = field_option;
			return field_option;
		},
		/**
		 * Gets the option index for a value
		 **/
		getChoiceIndexByLabel : function(label) {
			for(var i=0,j=this.choices.length; i < j; i++) {
				if (this.choices[i].label == label) return i;
			}
			return -1;
		},
		getChoiceIndexByOrder : function(order) {
			for(var i=0,j=this.choices.length; i < j; i++) {
				if (this.choices[i].order == order) return i;
			}
			return -1;
		},
		
		getChoiceByLabel : function(label) {
			var ix = this.getChoiceIndexByLabel(label);
			if (ix > -1) {
				return this.choices[ix];
			} else return false;
		},
		
		getChoice : function(ix) { return this.choices[ix]; },
		
		setOrder : function(order) { this.order = order; },
		
		removeOptionAtIndex : function(index) {
			
			this.choices.splice(index, 1);
			
			for (var i=0, j=this.choices.length; i < j; i++) {
				// Update sort order of other elements
				/*
				if (this.choices[i].order > index) {
					this.choices[i].order --;
				}
				*/
				this.choices[i].order = i;
				
			}
			
		},
		
		setOptionOrder : function(arr_field_ids) {
			alert('setOptionOrder');
		},
		
		resetChoices : function() {
			this.choices = [];
		},
		
		setVarKey : function(var_key) {
			this.varKey = var_key;
		}
		
		
	});
	
	function FormBuilder(id, options) { this.init(id, options); }
	
	FormBuilder.prototype = {
		init : function(id, options) {
			this.id = id;
			this.fields = [];
			this.title = options.formTitle;
			this.eventsActive = false;
		},
		
		getId : function() { return this.id; },
		
		getField : function(id) {
			var fix = this.getFieldIndex(id);
			if (fix > -1) return this.fields[fix];
			else return false;
		},
		
		getFieldIndex : function(id) {
			for(var i=0, j=this.fields.length; i < j; i++) {
				if (this.fields[i].getId() == id) return i;
			}
			return -1;
		},
		
		getFields : function() { return this.fields; },
		
		getTitle : function() { return this.title; },
	
		/**
		 * Setters
		 **/
		setId : function(id) { this.id = id; },
		
		setTitle : function(title) { this.title = title; },
		
		addField : function(obj_ref, id, label, type_id, config) {
			var field = new FormBuilderField(obj_ref, id, id, label, type_id, config, this.fields.length);
			this.fields[this.fields.length] = field;
			return field;
		},
		
		removeField : function(id) {
			var fix = this.getFieldIndex(id);
			if (fix > -1) {
				// Remove field from index
				this.fields.splice(fix, 1);
				// Update index/sortorder of other fields
				for (var i=0, j=this.fields.length; i < j; i++) {
					this.fields.setOrder(i);
				}
			}
		},
		
		setFieldOrder : function(arr_container_field_ids) {
			
			var fields = this.getFields();
			
			for (var i=0,j=arr_container_field_ids.length; i < j; i++) {

				var field_id = arr_container_field_ids[i].substr(0, arr_container_field_ids[i].length-10);
				
				if (field = this.getField(field_id)) {
					field.setOrder(i);
				}
			}			
			
		},
		
		export : function() {
			var fields = this.getFields();
			/**
			 * Export object
			 **/
			var vars = {
				id : this.getId(),
				title: this.getTitle(),
				num_fields: fields.length				
			};
			for (i=0, j=fields.length; i < j; i++) {
				var var_base= 'field'+i+'_';
				vars[var_base+'id'] = fields[i].getId();
				vars[var_base+'var_key'] = fields[i].getVarKey();
				vars[var_base+'type_id'] = fields[i].getTypeId();
				vars[var_base+'order'] = fields[i].getOrder();
				vars[var_base+'num_choices'] = fields[i].choices.length;
				vars[var_base+'num_config'] = fields[i].config.length;
				
				/**
				 * field0_choice0_value
				 * field0_choice0_label
				 *
				 * field0_config0_name
				 * field0_config0_value
				 **/
				// Choices
				for(x=0, y=fields[i].choices.length; x < y; x++) {
					var choice_var_base = var_base + 'choice'+x+'_';
					vars[choice_var_base+'value'] = fields[i].choices[x].value;
					vars[choice_var_base+'label'] = fields[i].choices[x].label;
				}
				
				// Config settings
				//for(x=0, y=fields[i].config.length; x < y; x++) {
				for (var opt_key in fields[i].config) {
					var config_var_base = var_base + 'config'+x+'_';
					vars[config_var_base+'name'] = opt_key;
					vars[config_var_base+'value'] = fields[i].config[opt_key];
				}
			}
			
			/*
			var str = '';
			for (var v in vars) {
				str += v + '=' + vars[v] + '\n';
			}
			alert(str);
			*/
			/*
			$('#debug').text(str);
			*/
			return vars;
			
		}

	}
	
	function _Template(raw) {
		this.raw = raw;
	}
	_Template.prototype.render = function(objs) {
		var tmpl = this.raw;

		for (var key in objs) {
			var rex = new RegExp('\\${' + key + '}', 'g');
			// Replace field id placeholders with field id
			tmpl = tmpl.replace(rex, objs[key]);
		}
		return tmpl;
	}
	
	var methods = {
		
		init : function(options) {
			// Make sure required option fields is set
			if (!options.fieldTypes) return alert('fieldTypes is a required option');
			
			return this.each(function() {
				var $form = $(this);
				
				if (typeof($form.attr('formid')) == 'undefined') return alert('formid is a required attribute');
				
				data = $form.data('formedit');
				
				// If the plugin hasn't been initialized yet
				if (!data) {
					//$form.data('formedit', new FormBuilder($form, options.fieldTypes));
					formCount ++;
					
					//var form_id = ($form.attr('id') ? $form.attr('id') : 'newform'+formCount);
					var formId = $form.attr('formid');
					
					//alert('form count: ' + formCount + '; ID: ' + formId);
					var form_builder = new FormBuilder(formId, options);

					$form.data('formedit', {
						formBuilder:form_builder,
						fieldTypes:options.fieldTypes,
						fieldIndex:0
					});
					
					$form.css('position','relative');
					// Add form controls
					var $show_controls = $('<div />').css({
						position:'absolute',
						top:0,
						right:0,
						backgroundColor:'#e1e1e1'
					}).text('Controls').click(function() {
						$form.formedit('toggleControls');
					});
					$form.append($show_controls);
					
					// Add editable title
					var h2 = $('<h2 class="form-title editable-highlight editable-clickable" />').text(form_builder.getTitle());
					$form.append(h2);
					h2.clickedit({placeholder:'<em>Form Title</em>'});
					h2.bind('contentchanged', function(ev, data) {
						form_builder.setTitle(data.newValue);
						$form.trigger('formChanged');
					});
					
					// Add submit button
					/*
					var $sbmt = $('<input type="submit" />').val('Save');
					$form.append($sbmt);
					$sbmt.click({$form:$form}, function(ev) {
						ev.preventDefault();
						$form.formedit('serializeData')
					});
					*/
					
					// Make fields sortable
			
					$form.sortable({
						items:'div.field-container',
						handle:'.action-move,label:first',
						placeholder:'field-drop-container',
						forcePlaceholderSize:true,
						update:function(ev) {
							var fieldOrder = $(this).sortable('toArray');
							form_builder.setFieldOrder(fieldOrder);
							$form.trigger('formChanged');
						}
					}).addClass('form-container');
					
					// Activate events (such as "fieldAdded") only after everything has been initialized
					form_builder.eventsActive = true;
					
					if (formId == 'new') {
						$form.formedit('showControls');
					}
				}
				
				//$form.formedit('_initControls');
				//$form.formedit('showControls');
			});
		},
		serializeData : function() {
			var $form = $(this);
			data = $form.data('formedit');
			formBuilder = data.formBuilder;
			return formBuilder.export();
		},
		addFieldOption : function(field_id, value, label) {
			
			var $form = $(this);
			data = $form.data('formedit');
			fieldTypes = data.fieldTypes;
			formBuilder = data.formBuilder;
			
			if (field = formBuilder.getField(field_id)) {
				
				var field_option = field.addChoice(value, label);
				
				var tmpl = new _Template(fieldTypes[field.getTypeId()].option_template);
				var tmpl_type = fieldTypes[field.getTypeId()].option_template_type;
				
				var tmpl_objs = {
					field_id	: field.getId(),
					id		: 'field-option-' + field.getId() + '-' + field_option.order,
					value		: field_option.value,
					label		: field_option.label
				};
				
				if (tmpl_type == 'external') {
					// Create jQuery object from template
					var $new_option = $(tmpl.render(tmpl_objs)).attr('id', tmpl_objs.id + '-container');
					// Make the label of the new option clickable
					var click_opt_lbl = $new_option.find('label');
					click_opt_lbl.addClass('editable-highlight editable-clickable');
					click_opt_lbl.clickedit({inputControlDisplay:'inline'});
					click_opt_lbl.bind('contentchanged', function(ev, data) {
						$form.trigger('formChanged');
					});
					
					$new_option.append($('<a href="#">Delete</a>').click(function(ev) {
						ev.preventDefault();
						var test = formBuilder.getField(field.getId()).getChoiceByLabel(field_option.label);
						field.removeOptionAtIndex(field_option.order);
						// Remove element
						$new_option.remove();
						
						var remaining_options = field.getChoices();
						
					}));
					
					$(click_opt_lbl).bind('contentchanged', {field:field}, function(ev, data) {
						
						var field = ev.data.field;
						
						if (option = field.getChoiceByLabel(data.oldValue)) {
							option.value = data.newValue;
							option.label = data.newValue;
						}
						
						$form.trigger('formChanged');
						
					});
					// Add the new option
					field.getObj().find('.field-options').append($new_option);
				} else if (tmpl_type == 'internal') {
					// Add the new option to the existing element
					field.getObj().find('#' + field.getId()).append(tmpl.render(tmpl_objs));
				}
				
				$form.trigger('formChanged');
				
				return field_option;	
				//alert(fieldTypes[field.getTypeId()].option_template);
			}
			//alert(field_id + ' = ' + value);
			//if (field_type.has_options) alert(field_type.option_template + ' - ' + field_type.option_template_type);
			//if (field_type = data.fieldTypes[field_type_id]) {
		},
		addField : function(field_type_id) {
			var $form = $(this);
			data = $form.data('formedit');
			formBuilder = data.formBuilder;
			
			if (field_type = data.fieldTypes[field_type_id]) {

				data.fieldIndex++;
				
				var id = data.fieldIndex;
				var f_id = 'field'+data.fieldIndex;
				var rex = new RegExp('\\${field_id}', 'g');
				// Replace field id placeholders with field id
				var tmpl = field_type.template.replace(rex, f_id);
				alert(field_type.template);
				// jQuery version
				$tmpl = $(tmpl);
				
				var field_label = $tmpl.find('label:first');
				field_label.addClass('editable-highlight editable-clickable');
				
				var form_field = formBuilder.addField($tmpl, f_id, field_label.html(), field_type_id);
				
				// Get submit button if there is one
				var btn_submit = $form.find('input[type="submit"]');
				// If there is a submit button insert this field before the submit button
				if (btn_submit.length > 0) {
					btn_submit.before($tmpl);
				// Otherwise append the field to the end of the form
				} else {
					$form.append($tmpl);
				}
				
				$tmpl.find('.action-remove').click(function() {
					if (confirm('Are you sure you want to delete this field?')) {
						var field_container = $(this).parents('.field-container');
						var field_container_id = field_container.attr('id');
						var field_id = field_container_id.substr(0, field_container_id.length-10);
						alert(field_container_id + ' - ' + field_id);
						$('#' + field_container_id).remove();
					}
				});
				
				var editable_label = $tmpl.children('label:first').clickedit({
					fullWidthInput:true
				});
				editable_label.bind('contentchanged', function() {
					alert('content changed: ' + tmpl);
					$form.trigger('formChanged');
				});
				
				if (field_type.has_choices) {
					
					// Add default options for field
					$form.formedit('addFieldOption', f_id, 'New Option');
					$form.formedit('addFieldOption', f_id, 'New Option');
					$form.formedit('addFieldOption', f_id, 'New Option');
					
					// Create an "Add Option" link for adding additional items
					var $a = $('<a href="#" />').text('Add Option').click({$form:$form,fieldId:f_id},function(ev) {
						ev.data.$form.formedit('addFieldOption', ev.data.fieldId, 'New Option');
					});
					//$a.wrap('<div class="field-option-add" />');
					
					/*
					 * Add the "Add Option" link to the control, dependent on the type of field we are working with
					 **/
					// Internal fields are simple elements, e.g. <select /> that need option added directly to the field
					if (field_type.option_template_type == 'internal') {
						
						/*$a2 = $('<a href="#" />').text('Items').click(function() {
							alert('clicked items');
						}).appendTo($tmpl);*/
						$tmpl.append($a);
					// External fields are fields have multiple selection options and are added independently of the original element, e.g. <input type="radio|checkbox" />
					} else if (field_type.option_template_type == 'external') {
						$tmpl.find('.field-options').after($a);
						
						// Make the fields sortable 
						var pre_sort = [];
						
						form_field.getObj().find('.field-options').sortable({
							items:'.option',
							handle:'label',
							start:function() {
								pre_sort = $(this).sortable('toArray');
							},
							update:function(ev, ui) {
								var choice_order = $(this).sortable('toArray');

								var readd_choices = [];
								
								for(var i=0,j=choice_order.length; i < j; i++) {
									readd_choices.push(form_field.getChoice(pre_sort.indexOf(choice_order[i])));
								}
								
								form_field.resetChoices();
								for(var i=0,j=readd_choices.length; i < j; i++) {
									form_field.addChoice(readd_choices[i].value, readd_choices[i].label);
								}
								$form.trigger('formChanged');
								
							}
						});
					}
				}
				
				$form.trigger('formChanged');

			} else alert('Could not find: ' + field_type_id);
		},
		toggleControls : function() {
			$(this).formedit('_initControls');
			$(this).data('formedit').$formControls.toggle();
		},
		_initControls : function() {
			
			var $form = $(this);
			var data = $form.data('formedit');
			var $formControls = data.$formControls;
			
			if (!$formControls) {
				
				var p = $form.position();
				var t = p.top, l = p.left, w = $form.outerWidth();
				var formBuilder = $form.data('formedit');
				var fieldTypes = formBuilder.fieldTypes;
				
				var $div_controls = $('<div />').css({
					position:'absolute',
					left:l+w,
					top:t,
					display:'none'
				}).addClass('form-controls');
				
				var $div_close = $('<div />').addClass('close').text('Close').click(function() {
					$form.formedit('hideControls');
				});
				$div_controls.append($div_close);
				
				$form.after($div_controls);
	
				for(var field_type_id in fieldTypes) {
					
					var field = fieldTypes[field_type_id];
					
					var $div_field = $('<a href="#" />').attr('id', field_type_id).text(field.label).click({$form:$form,field:field}, function(ev, attr) {
						ev.preventDefault();
						ev.data.$form.formedit('addField', ev.data.field.field_type_id);
	
					}).prepend($('<span class="icon" />'));
					$div_controls.append($div_field);
				
				}
				
				data.$formControls = $div_controls;
				
			}			
		},
		showControls : function() {
			$(this).formedit('_initControls');
			$(this).data('formedit').$formControls.show();
		},
		hideControls : function() {
			$(this).data('formedit').$formControls.hide();
		}
	};
	
	$.fn.formedit = function(method) {

		if (methods[method]) {

			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
			
		} else if (typeof method === 'object' || !method) {
			
			return methods.init.apply(this, arguments);
			
		} else {
			
			$.error('Method ' + method + ' does not exist on jQuery.formedit');
			
		}
		
	}
})(jQuery);
/*
var printObj = typeof JSON != "undefined" ? JSON.stringify : function(obj) {
  var arr = [];
  $.each(obj, function(key, val) {
    var next = key + ": ";
    next += $.isPlainObject(val) ? printObj(val) : val;
    arr.push( next );
  });
  return "{ " +  arr.join(", ") + " }";
};
*/
/**
 * Handle javascript event for Control object "saving"
 **/
var FormBuilderControl = Control.extend({
	
	// Get the object that implements jQuery.formedit()
	getFormEditObj : function() { return this.getObj('form'); },
	
	init : function() {
		var formObj = this.getFormEditObj();
		var self = this;
		formObj.live('formChanged', function() {
			self.contentChanged();
		}); // Notify control that it needs to be saved
		//alert('Initing form builder');
		
		var self = this;
		jQuery(document).ready(function() {
			self.debugMode = true;
			self.showActionBar();
		});
	},
	
	onSaving : function(ev, data) {
		/*
		var $sbmt = $('<input type="submit" />').val('Save');
					$form.append($sbmt);
					$sbmt.click({$form:$form}, function(ev) {
						ev.preventDefault();
						$form.formedit('serializeData')
					});
		*/
		var formData = this.getFormEditObj().formedit('serializeData');
		
		if (formData.title.length == 0) {
			alert('Form title is required.  Click the form title to name this form.');
			return false;
		}
		
		var dbg = '';
		for (var i in formData) {
			var varName = 'form_' + i;
			data.submitValues[varName] = formData[i];
			dbg += varName + ' = ' + formData[i] + '<br />';
		}
		
		var $debug = $('#debug');
		if ($debug.length == 0) {
			$debug = jQuery('<div />').attr('id', 'debug').css({
				backgroundColor:'#000',
				color:'#fff',
				padding:'10px',
				position:'fixed',
				right:0,
				top:0,
				overflow:'auto',
				width:'400px',
				height:'400px'
			}).appendTo(jQuery('body'));
		}
		$debug.html(dbg);
	}
	
});
/*
function handleFormControlSaving() {
	alert('Form control save!');
}
*/