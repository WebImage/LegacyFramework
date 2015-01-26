<?php

FrameworkManager::loadLibrary('controls.iregion');

class CWI_CONTROLS_AbstractRegion extends WebControl implements CWI_CONTROLS_IRegion {
	
	var $m_friendlyName;
	
	public function getFriendlyName() {
		$friendly_name = '';
		
		// Check if a friendly name has been defined
		if (!is_null($this->m_friendlyName)) {
			
			$friendly_name = $this->m_friendlyName;
			
		// Otherwise try to generate a friendly name
		} else {
			
			$outer_name = $this->getOuterId();
			if (substr($outer_name, 0, 3) == 'ph_') $outer_name = substr($outer_name, 3, strlen($outer_name) - 3);
			
			$elements = explode('_', $outer_name);
			for ($i=0; $i<count($elements); $i++) {
				if ($elements[$i] != 'of') $elements[$i] = strtoupper(substr($elements[$i], 0, 1)) . substr($elements[$i], 1, strlen($elements[$i])-1);
			}
			$outer_name = implode(' ', $elements);
			$friendly_name = $outer_name;
			
		}
		return $friendly_name;	
	}
	
	protected function isAdminRequest() {
		#$current_context = Page::getCurrentPageRequest()->getPageResponse()->getContext();
		#$control_edit_context = $current_context->get('control_edit_context');
		return (Page::isAdminRequest() && Roles::isUserInRole('AdmBase'));
	}
	/**
	 * @return Collection
	 **/
	public function getRegionControls() {
		return Page::getRegionControls( $this->getId() );
	}
	
	public function setFriendlyName($friendly_name) { $this->m_friendlyName = $friendly_name; }
}

?>