var EditTextControl = Control.extend({
	onConfigValueChanged : function(ev, data) {
		this._parent(ev, data);

		switch (data.configName) {}
	},
	onSaving : function(ev, data) {
		var description = this.getObj('description_field');
		data.submitValues.description = description.val();
	}
});