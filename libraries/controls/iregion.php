<?php

interface CWI_CONTROLS_IRegion {
	// Returns a friendly name for the control that can be 
	public function getFriendlyName();
	public function setFriendlyName($friendly_name);
	// Get region controls
	public function getRegionControls();
}

?>