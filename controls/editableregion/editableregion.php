<?php
/**
 * 01/22/2010	(Robert Jones) Changed the wrap to place the admin region code inside the wrap instead outside, e.g. <div id="ph_main"><div class="editableregion"></div></div> instead of <div class="editableregion"><div id="ph_main"></div></div> - primarily because CSS formatting is sometimes applied to the container based on the region DIVs id
 * 06/22/2011	(Robert Jones) Added "friendlyName" option so that the default generated name doesn't have to be used.
 * 09/02/2011	(Robert Jones) Add new interface CWI_CONTROLS_IRegion and new abstract class CWI_CONTROLS_AbstractRegion  and moved getFriendlyName() and isAdminRequest() to the abstract class
 */
FrameworkManager::loadLibrary('controls.abstractregion');

class EditableRegionControl extends CWI_CONTROLS_AbstractRegion {
	var $m_renderNoContent = true;

	function prepareContent() {
		
		$child_controls = $this->getRegionControls();
		while ($child_control = $child_controls->getNext()) {
			$this->addControl($child_control);
		}
		
		if ($this->isAdminRequest()) {
			
			// Current context
			FrameworkManager::loadLibrary('controls.editable.general');
			
			$context = Page::getCurrentPageRequest()->getPageResponse()->getContext();
			if (!$control_edit_context = $context->get('control_edit_context')) $control_edit_context = EDITABLE_EDITCONTEXT_PAGE;
					
			FrameworkManager::loadDAO('control');
			FrameworkManager::loadLogic('pagecontrol'); // Used for favorites
			
			$control_dao = new ControlDAO();
			$controls = $control_dao->getControls();
			$wrap_output = '';

			#$script = '<script type="text/javascript">' . "\r\n";
			$script = '';
			$script .= 'var ' . $this->getId() . ' = new ControlContainer(\'' . $this->getId() . '\', \'' . Page::getPageId() . '\');'."\r\n";
			$script .= $this->getId() . '.setControlContainerId(\'' . $this->getId() . '-controlbody\');' . "\r\n";

			if ($this->getContext()->get('control_edit_context') == EDITABLE_EDITCONTEXT_TEMPLATE) {
				if ($template_id = $this->getContext()->get('control_edit_context_template_id')) {
					$script .= $this->getId() . '.setTemplateId(' . $template_id . ');';
				}
			}


/*
// May be used in template mode when the user needs to select a specific region for a type of content - say a blog post....
if ($this->getContext()->get('control_edit_context_template_select_region')) {
	$wrap_output .= '<div %s><div style="background-color:#000;border-radius:5px;padding:10px;"><a href="#" style="font-size:24px;color:#e1e1e1;">Select Region</a></div>%s</div>';
	$this->setWrapOutput($wrap_output);
	return;
}
*/
			/*$script .= '</script>' . "\r\n";
			$wrap_output .= $script;*/
			Page::addScriptText($script);
			
			$wrap_output .= '<div class="editable-region">';
			
				// Region Bar Name
				$wrap_output .= '<div class="editable-region-bar">';
					
					$wrap_output .= '<div class="editable-region-title">' . $this->getFriendlyName() . '</div>';
					
					$wrap_output .= '<div class="editable-region-addcontent-container" onclick="' . $this->getId() . '.toggleNewContentMenu()">';
						$wrap_output .= '<a href="#" onclick="return false;" class="editable-region-addcontent-button"></a>';
			
						// Add Control
						if ($controls->getCount() > 0) {
							$favorite_controls = PageControlLogic::getFavoritePageControls();
							#$add_content_id = $this->getOuterId() . '_newcontent';
							$wrap_output .= '<div class="editable-region-add-options">';
								$wrap_output .= '<ul>';
									#$wrap_output .= '<li><div class="editable-region-add-section">Content</div>';
									#$wrap_output .= '<ul>';
										while ($type = $controls->getNext()) {
											$wrap_output .= '<li><a href="#" onclick="' . $this->getId() . '.addNewControl(' . $type->id . ', \'' . $control_edit_context . '\');return false;">' . $type->label . '</a></li>';
										}
									#$wrap_output .= '</ul>';
									#$wrap_output .= '</li>';
									
								if ($favorite_controls->getCount() > 0) {
									$wrap_output .= '<li><div class="editable-region-add-section">Favorites</div>';
									$wrap_output .= '<ul>';
										while ($favorite_control = $favorite_controls->getNext()) {
											$wrap_output .= '<li><a href="#" onclick="' . $this->getId() . '.addFavorite(' . $favorite_control->id . ');return false;">' . $favorite_control->favorite_title . '</a></li>';
										}
									$wrap_output .= '</ul>';
									$wrap_output .= '</li>';
								}
								
								$wrap_output .= '</ul>';
								#$wrap_output .= '<form method="get" action="' . Page::getPath() . '" style="margin:0;">';
									#$wrap_output .= '<input type="hidden" name="pageid" value="' . Page::getPageId() . '" />';
									#$wrap_output .= '<input type="hidden" name="placeholder" value="' . $this->getOuterId() . '" />';
									#$wrap_output .= '<div class="editableregionnewcontent">';
										#$wrap_output .= '<a href="#" onclick="$(\'#'.$add_content_id.'\').toggle();return false;" class="tab"><span><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_plus.gif" width="14" height="14" border="0" align="absmiddle" /> Add New Content</span></a>';
										/*
										$wrap_output .= '<script type="text/javascript">
										function create_new_control_'.$this->getOuterId().'() {
											var controlid = document.getElementById(\'newcontent_' . $this->getOuterId() . '\');
			
											if (controlid.value != \'\') {
												if (controlid.value.substr(0, 1) == \'x\') {
													var link_copy = \'' . ConfigurationManager::get('DIR_WS_ADMIN') . 'pagecontrols/copy.html?controlid=\' + controlid.value.substr(1) + \'&pageid=' . Page::getPageId() . '&placeholder=' . $this->getOuterId() .'\';
													window.location.href = link_copy;
												} else {
													var link_new = \'' . ConfigurationManager::get('DIR_WS_ADMIN') . 'pagecontrols/edit.html?controltype=\'+controlid.value+\'&pageid=' . Page::getPageId() . '&placeholder=' . $this->getOuterId() .'\';
													window.location.href = link_new;
												}
											}
											return;
											
											if (controlid.value != \'\') {
												//window.open(\'' . ConfigurationManager::get('DIR_WS_ADMIN') . 'admin/getcontrolmode.html?pageid=' . Page::getPageId() . '&placeholder=' . $this->getOuterId() . '&controltype=\'+controlid.value+\'&pagecontrol=&editmode=Admin&windowmode=Full\', \'editControl\', \'status=0,toolbar=0,menubar=0,resizable=1,width=800,height=480,location=0,titlebar=0,scrollbars=1\');
											}
										}
									
										</script>';
										*/
										
									#$wrap_output .= '</div>';
		
									/*
									$wrap_output .= '<div class="editableaddcontrol-newcontent" id="' . $add_content_id . '">';
										// Drop Down
										$wrap_output .= '<strong>Select content type: </strong><select id="newcontent_' . $this->getOuterId() . '" name="controlid" onchange="create_new_control_' . $this->getOuterId() . '();">';
										$wrap_output .= '<option value="">-- Select --</option>';
										$wrap_output .= '<optgroup label="Content">';
										while ($type = $controls->getNext()) {
											$wrap_output .= '<option value="' . $type->id . '">' . $type->label . '</option>';
										}
										$wrap_output .= '</optgroup>';
										
										if ($favorite_controls->getCount() > 0) {
											$wrap_output .= '<optgroup label="Favorites">';
											while ($favorite_control = $favorite_controls->getNext()) {
												$wrap_output .= '<option value="x' . $favorite_control->id . '">' . $favorite_control->favorite_title . '</option>';
											}
											$wrap_output .= '</optgroup>';
			
										}
										
										$wrap_output .= '</select>';
										$wrap_output .= '<br />New content will be added to the bottom of this region.';
										#$wrap_output .= '<ul><li><a href="' . ConfigurationManager::get('DIR_WS_ADMIN') .'pagecontrols/duplicate.html?pageid=' . Page::getPageId() . '&placeholder=' . $this->getOuterId() . '">Copy content from another page.</a></li></ul>';
									$wrap_output .= '</div>';
									*/
								#$wrap_output .= '</form>';
							$wrap_output .= '</div>';
						}/* else {
							$wrap_output .= '<div class="editableaddcontrol">';
							$wrap_output .= 'No Available Controls';
							$wrap_output .= '</div>';
						}*/
					$wrap_output .= '</div>';
				//
				$wrap_output .= '</div>';
				// Include Region Controls
				#$wrap_output .= '<div class="editableregionbody">'.$this->getWrapOutput().'</div>';
				$wrap_output .= '<div id="' . $this->getId() . '-controlbody" class="editable-region-body">%s</div>';
			
			//$wrap_output .= '<div class="editableregionbar">} End Content Region</div>';
			$wrap_output .= '</div>';

			// Alter the wrapper to place the admin elements inside the container
			$wrap_output = sprintf($this->getWrapOutput(), '%s', $wrap_output);
			
			$this->setWrapOutput($wrap_output);
		}
		
	}
}

?>