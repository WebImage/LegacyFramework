var EditTextControl = Control.extend({
	onConfigValueChanged : function(ev, data) {
		switch (data.configName) {
			
		}
	},
	onSaving : function(ev, data) {
		var description = this.getObj('description_field');
		data.submitValues.description = description.val();
	}
});