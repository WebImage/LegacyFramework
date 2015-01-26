(function($) {
	
	var formCount = 0;
	
	function FormBuilderFieldChoice(value, label, order) {
		this.value = value; // Value of the object
		this.label = label;
		this.order = order; // Sorting order
	}
	
	var FormBuilderField = Class.extend({
		__construct : function(obj_ref, id, element_id, var_key, label, type_id, config, order) {
			
			if (!config) config = {};
	
			this.objRef = obj_ref;
			this.id = id;
			this.elementId = element_id;
			this.label = label;
			this.varKey = var_key; // Can be used to reference field in email templates
			this.typeId = type_id;
			this._config = config;
			this.choices = [];
			this.order = order;
		},
		// Get a jQuery object reference to the selected field
		getObj : function() { return this.objRef; },
		
		// Getters
		getId : function() { return this.id; },
		
		getElementId : function() { return this.elementId; },
		
		getVarKey : function() { return this.varKey; },
		
		getLabel : function() { return this.label; },
		
		getTypeId : function() { return this.typeId; },
		
		getConfig : function() { return this._config; },
		
		config : function(name, value) {
			if (typeof(value) === 'undefined') { // Getter
				if (typeof(this._config[name]) === 'undefined') return false;
				else return this._config[name];
			}
			// Setter
			this._config[name] = value;
		},
		
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
		
		setLabel : function(label) {
			this.label = label;
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
	
	var FormBuilder = Class.extend({
		
		__construct : function(id, options) { this.init(id, options); },

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
		
		
		getFieldByElementId : function(element_id) {
			var fix = this.getFieldIndexByElementId(element_id);
			if (fix > -1) return this.fields[fix];
			else return false;
		},
		
		getFieldIndexByElementId : function(element_id) {
			for(var i=0, j=this.fields.length; i < j; i++) {
				if (this.fields[i].getElementId() == element_id) return i;
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
		
		/**
		 * @param Object obj_ref A reference to the object
		 * @param int id An internal identifier
		 * @param string element_id A unique name for the generated field
		 * @param string var_key A user friendly variable key that can be used referencing in templates
		 * @param string label The field's label
		 * @param string type_id Identifies the field type 
		 * @param Object config
		 **/
		addField : function(obj_ref, id, element_id, var_key, label, type_id, config, order) {
			if (!order) order = this.fields.length;
			
			var field = new FormBuilderField(obj_ref, id, element_id, var_key, label, type_id, config, order);
			this.fields[this.fields.length] = field;
			return field;
		},
		
		removeFieldByElementId : function(element_id) {
			var fix = this.getFieldIndexByElementId(element_id);
			if (fix > -1) {
				var removing_field = this.fields[fix];
				var deleted_field_order = removing_field.getOrder();
				
				// Remove field from index
				this.fields.splice(fix, 1);
				// Update index/sortorder of other fields
				for (var i=0, j=this.fields.length; i < j; i++) {
					if (this.fields[i].getOrder() > deleted_field_order) {
						this.fields[i].setOrder(this.fields[i].getOrder()-1);
					}
				}
			}
		},
		
		setFieldOrder : function(arr_container_field_ids) {
			
			var fields = this.getFields();
			
			for (var i=0,j=arr_container_field_ids.length; i < j; i++) {

				var element_id = arr_container_field_ids[i].substr(0, arr_container_field_ids[i].length-10); // Remove "-container" from id
				
				if (field = this.getFieldByElementId(element_id)) {
					field.setOrder(i+1);
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
				vars[var_base+'label'] = fields[i].getLabel();
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
				var config = fields[i].getConfig();
				
				for (var opt_key in config) {
					var config_var_base = var_base + 'config'+x+'_';
					var value = fields[i].config(opt_key);
					if (value === true) value = 'true';
					else if (value === false) value = 'false';
					
					vars[config_var_base+'name'] = opt_key;
					vars[config_var_base+'value'] = value;
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

	});
	
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
	
	var formedit_methods = {
		
		init : function(options) {
			// Make sure required option fields is set
			if (!options.fieldTypes) return alert('fieldTypes is a required option');
			
			return this.each(function() {
				var $form = $(this);
				
				if (typeof($form.attr('formid')) == 'undefined') return alert('formid is a required attribute');
				if (typeof($form.attr('fieldindex')) == 'undefined') return alert('fieldindex is a required attribute');
				
				data = $form.data('formedit');
				
				// If the plugin hasn't been initialized yet
				if (!data) {
					//$form.data('formedit', new FormBuilder($form, options.fieldTypes));
					formCount ++;
					
					//var form_id = ($form.attr('id') ? $form.attr('id') : 'newform'+formCount);
					var formId = $form.attr('formid');
					var fieldIndex = $form.attr('fieldindex');
					
					if (formId == 'new') {
						formId = 'new-'+formCount; // Make the form's ID unique
					}
					
					//alert('form count: ' + formCount + '; ID: ' + formId);
					var formBuilder = new FormBuilder(formId, options);

					$form.data('formedit', {
						formBuilder:formBuilder,
						fieldTypes:options.fieldTypes,
						fieldIndex:fieldIndex
					});
					
					$form.css('position','relative');
					// Add form controls
					var $show_controls = $('<div />').css({
						position:'absolute',
						top:0,
						right:0,
						backgroundColor:'#e1e1e1'
					}).text('Controls').click(function(ev) {
						$form.formedit('toggleControls', $(this));
						ev.stopPropagation();
					});
					$form.append($show_controls);
					
					// Add editable title
					var h2 = $('<h2 class="form-title editable-highlight editable-clickable" />').text(formBuilder.getTitle());
					$form.append(h2);
					h2.clickedit({placeholder:'<em>Form Title</em>'});
					h2.bind('contentchanged', function(ev, data) {
						formBuilder.setTitle(data.newValue);
						if (formBuilder.eventsActive) $form.trigger('formChanged');
					});
					
					/*
					var $new_field = $('<div class="form-builder-new-field editable-highlight editable-clickable">New Field</div>');
					$new_field.click(function() {
						alert('Add to top');
					});
					$form.append($new_field);
					*/
					
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
						/*handle:'.action-move,label:first',*/
						placeholder:'field-drop-container',
						forcePlaceholderSize:true,
						update:function(ev) {
							var fieldOrder = $(this).sortable('toArray');
							formBuilder.setFieldOrder(fieldOrder);
							if (formBuilder.eventsActive) $form.trigger('formChanged');
						}
					}).addClass('form-container');
					
					// Activate events (such as "fieldAdded") only after everything has been initialized
					
					if (formId == 'new') {
						$form.formedit('showControls');
					}
					
					// Restore previously saved field elements
					if (typeof(options.fields) == 'object' && options.fields.length > 0) {
						
						for(var f in options.fields) {
							$form.formedit('addField', options.fields[f].field_type, options.fields[f]);
						}
						
					}
					
					formBuilder.eventsActive = true;
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
						if (formBuilder.eventsActive) $form.trigger('formChanged');
					});
					
					$new_option.append($('<a href="#">Delete</a>').click(function(ev) {
						ev.preventDefault();
						//var test = formBuilder.getField(field.getId()).getChoiceByLabel(field_option.label);
						
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
						
						if (formBuilder.eventsActive) $form.trigger('formChanged');
						
					});
					// Add the new option
					field.getObj().find('.field-options').append($new_option);
				} else if (tmpl_type == 'internal') {
					// Add the new option to the existing element
					field.getObj().find('#' + field.getId()).append(tmpl.render(tmpl_objs));
				}
				
				if (formBuilder.eventsActive) $form.trigger('formChanged');
				
				return field_option;	
				//alert(fieldTypes[field.getTypeId()].option_template);
			}
			//alert(field_id + ' = ' + value);
			//if (field_type.has_options) alert(field_type.option_template + ' - ' + field_type.option_template_type);
			//if (field_type = data.fieldTypes[field_type_id]) {
		},
		/**
		 * @param string field_type_id Identifies the type of field to be added
		 * @param object field_obj Optional.  Allows a predefineed field to be added
		 **/
		addField : function(field_type_id, field_obj) {
			var $form = $(this);
			data = $form.data('formedit');
			formBuilder = data.formBuilder;
			
			if (field_type = data.fieldTypes[field_type_id]) {
				
				var generate_field = (typeof(field_obj) == 'undefined');
				
				/*
				$obj->config		= json_decode($field->config);
				$obj->id		= $field->field_id;
				$obj->varKey		= $field->key;
				$obj->label		= $field->label;
				$obj->order		= $field->sortorder;
				$obj->field_type	= $field->type_id;
				*/
				
				var f_id, element_id, var_key, order, rex, tmpl, $tmpl;
				
				if (generate_field) { // New field	
					data.fieldIndex++;
				}
				
				f_id		= generate_field ? data.fieldIndex : field_obj.id;
				element_id	= 'form' + formBuilder.getId() +'_field'+f_id;
				var_key		= generate_field ? 'field'+data.fieldIndex : field_obj.varKey;
				order		= generate_field ? null : field_obj.order;
				config		= generate_field ? {} : field_obj.config;
				rex		= new RegExp('\\${field_id}', 'g');
				// Replace field id placeholders with field id
				tmpl		= field_type.template.replace(rex, element_id);
				
				//alert(f_id + ' - ' + element_id + ' - ' + var_key + ' - ' + order + ' - ' + field_obj.label);
				
				// jQuery version
				$tmpl		= $(tmpl);
				
				var $field_label = $tmpl.find('label:first');
				$field_label.addClass('editable-highlight editable-clickable');
				
				// Restore the title if this is an existing field
				if (!generate_field) $field_label.html(field_obj.label);
				
				var form_field = formBuilder.addField($tmpl, f_id, element_id, var_key, $field_label.html(), field_type_id, config, order);
				
				// Get submit button if there is one
				var btn_submit = $form.find('input[type="submit"]');
				// If there is a submit button insert this field before the submit button
				if (btn_submit.length > 0) {
					btn_submit.before($tmpl);
				// Otherwise append the field to the end of the form
				} else {
					$form.append($tmpl);
					
					////////////////////////////////////////////////////////////////
					var self = this;
					var $new_field = $('<div />').html('<strong>Add New Field</strong>').addClass('form-builder-new-field editable-highlight editable-clickable');
					$new_field.click(function(ev) {
						ev.stopPropagation();
						self.toggleControls($(this));
						// Make sure the current click is registered, otherwise we could accidentally bind click events while the current click event is taking place
						/*
						setTimeout(function() {
							$new_field.clickOutside(function() {
								alert('close');
								$new_field.clickOutside('remove');
							});
						}, 100);
						*/
					});
					$tmpl.append($new_field);
					
					
					////////////////////////////////////////////////////////////////
				}
				
				var $field_controls = $('<div />').addClass('field-controls');
				var $input = $('<input />').attr({
					type:'checkbox',
					checked : (config.required),
					value:'1'
				}).change(function() {
					form_field.config('required', this.checked);
					if (formBuilder.eventsActive) $form.trigger('formChanged');
				});
				var $a = $('<a />').attr('href','#').addClass('action-remove').text('X');
			
				$field_controls.append('Required:', $input, '&nbsp;', $a);
				$tmpl.append($field_controls);
				
				$tmpl.find('.action-remove').click(function() {
					if (confirm('Are you sure you want to delete this field?')) {
						var field_container = $(this).parents('.field-container');
						var field_container_id = field_container.attr('id');
						var field_id = field_container_id.substr(0, field_container_id.length-10); // remove "-container" from field name
						//alert(field_container_id + ' - ' + field_id);
						formBuilder.removeFieldByElementId(field_id);
						$('#' + field_container_id).remove();
					}
				});
				
				var editable_label = $tmpl.children('label:first').clickedit({
					fullWidthInput:true
				});
				editable_label.bind('contentchanged', function(ev, data) {
					
					//alert('content changed: ' + tmpl);
					form_field.setLabel(data.newValue);
					if (formBuilder.eventsActive) $form.trigger('formChanged');
				});
				
				if (field_type.has_choices) {
					
					// Add default options for field
					if (generate_field) {
						$form.formedit('addFieldOption', f_id, 'New Option');
						$form.formedit('addFieldOption', f_id, 'New Option');
						$form.formedit('addFieldOption', f_id, 'New Option');
					} else {
						for(var i in field_obj.choices) {
							$form.formedit('addFieldOption', f_id, field_obj.choices[i].value, field_obj.choices[i].label);
						}
					}
					
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
								if (formBuilder.eventsActive) $form.trigger('formChanged');
								
							}
						});
					}
				}
				
				if (formBuilder.eventsActive) $form.trigger('formChanged');

			} else alert('Could not find: ' + field_type_id);
		},
		
		toggleControls : function($attachTo) {
			$(this).formedit('_initControls');
			if ($(this).data('formedit').$formControls.is(':visible')) {
				this.hideControls();
			} else {
				this.showControls($attachTo);
			}
		},
		_initControls : function() {
			
			var $form = $(this);
			var data = $form.data('formedit');
			var $formControls = data.$formControls;
			
			if (!$formControls) {
				
				//var p = $form.offset();
				//var t = p.top, l = p.left, w = $form.outerWidth();
				var formBuilder = $form.data('formedit');
				var fieldTypes = formBuilder.fieldTypes;
				
				var $div_controls = $('<div />').css({
					position:'absolute',
					display:'none'
				}).addClass('form-controls');
				
				var $div_close = $('<div />').addClass('close').text('Close').click(function(ev) {
					ev.stopPropagation();
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
		
		showControls : function($attachTo) {
			$(this).formedit('_initControls');
			var formControls = $(this).data('formedit').$formControls;
			var self = this;
			
			formControls.css({
				top:$attachTo.offset().top+$attachTo.outerHeight(),
				left:$attachTo.offset().left + Math.round($attachTo.outerWidth()/2)
			});
			/*
				formControls.click(function(ev) {
					ev.stopPropagation();
				});
				$(document).bind('click', self, self._handleClickOutsideControls);
			*/
			formControls.clickOutside(function() {
				self.hideControls();
			});
			formControls.show();
		},
		hideControls : function() {
			$(this).data('formedit').$formControls.clickOutside('remove'); //$(document).unbind('click', this.handleClickOutsideControls);
			$(this).data('formedit').$formControls.hide();
		}
	};
	
	$.fn.formedit = createJQueryPlugin(formedit_methods);
		
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
	getFormBuilder : function() {
		return this.getFormEditObj().data('formedit').formBuilder;
	},
	
	init : function() {
		var formObj = this.getFormEditObj();
		var self = this;
		formObj.live('formChanged', function() {
			self.contentChanged();
		}); // Notify control that it needs to be saved
		//alert('Initing form builder');
		
		/*
		var self = this;
		jQuery(document).ready(function() {
			self.debugMode = true;
			self.showActionBar();
		});
		*/
	},
	
	onSaving : function(ev, data) {

		var formData = this.getFormEditObj().formedit('serializeData');
		
		/*
		if (formData.title.length == 0) {
			alert('Form title is required.  Click the form title to name this form.');
			return false;
		}
		*/
		var dbg = '';
		for (var i in formData) {
			var varName = 'form_' + i;
			data.submitValues[varName] = formData[i];
			dbg += varName + ' = ' + formData[i] + '<br />';
		}
		/*
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
		*/
	},
	
	onSaved : function(ev, data) {
		var formBuilder = this.getFormBuilder();
		formBuilder.setId(data.formId);
	}
});
/*
function handleFormControlSaving() {
	alert('Form control save!');
}
*/