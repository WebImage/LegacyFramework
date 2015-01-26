<?php

FrameworkManager::loadLibrary('controls.editable.abstracteditinplacecontrol');

class EditFormControl extends CWI_CONTROLS_EDITABLE_AbstractEditInPlaceControl {
	
	public function handleAdminRequest() {
		$this->loadView('templates/default.tpl');
	}
	
	public function handleDefaultRequest() {
		$this->loadView('templates/default.tpl');
	}
}

?>