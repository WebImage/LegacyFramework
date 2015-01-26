<?php

FrameworkManager::loadLibrary('controls.editable.abstracteditinplacecontrol');

class EditMultiRegionControl extends CWI_CONTROLS_EDITABLE_AbstractEditInPlaceControl  {
	protected $devMode = true;
	
	public function handleMultiRegionRequest() {

		$num_regions = $this->getConfigValue('numSubRegions');
		if (empty($num_regions)) $num_regions = 1;
		
		if (!$this->isNew() && is_numeric($num_regions)) {
			
			FrameworkManager::loadControl('editableregion');
			
			$percentage = floor(100 / $num_regions);
			$last_region_percentage = 100 - ($percentage * ($num_regions-1));
			
			$class = $this->getConfigValue('class');
			
			for ($i=1; $i <= $num_regions; $i++) {
				$is_first = ($i==1);
				$is_last = ($i == $num_regions);
				$is_even = (($i % 2) == 0);
				
				$classes = array();
				if ($is_first) array_push($classes, 'first');
				if ($is_last) array_push($classes, 'last');
				array_push($classes, ($is_even ? 'even' : 'odd'));
				array_push($classes, 'subregion subregion-' . $i);
				
			
				$e = new EditableRegionControl();
				$e->setRenderNoContent(true);
				$e->setId($this->getId() . '_subregion'  . $i);
				$e->setFriendlyName('Region #' . $i);
				$e->setClass( implode(' ', $classes) );
				/*
				if (empty($class)) {
					
					$style = 'float:left;';
					
					if ($is_last) {
						
						$style .= 'width:' . $last_region_percentage . '%';
						
					} else {
						
						$style .= 'width: '. $percentage . '%';
						
					}
					
					$e->setParam('style', $style);
					
				}
				*/
				#$l = new LiteralControl();
				#$l->setText('Content for #' . $i);
				#$e->addControl($l);
				
				$this->addControl($e);
			
			}
			
			
			/*$clear = new LiteralControl();
			$this->addControl($clear);
			
			if (empty($class)) {
				$clear->setText('<div style="clear:both;"></div>');
			} else {
				$clear->setText('<div class="clear"></div>');
			}
			*/
			
			$output_classes = $this->getClass();
			if (!empty($class)) $output_classes .= ' ';
			$output_classes .= 'control-multiregion';
			$output_classes .= ' control-multiregion-' . $num_regions;
			
			if (!empty($class)) $output_classes .= ' ' . $class;
			
			$this->setClass($output_classes);

			#$this->addControl($e);
			#$this->addControl($e2);
			
		}
	}
	
	public function handleAdminRequest() {
		$this->handleMultiRegionRequest();
	}
	public function handleDefaultRequest() {
		$this->handleMultiRegionRequest();
	}
/*
	public function handleAdminRequestPostBack() {
		if (!Roles::isUserInRole('AdmBase')) return false;
		
		FrameworkManager::loadLogic('content');
		if ($content_id = $this->getConfigValue('contentId')) {
			$content = ContentLogic::getContentById($content_id);
			$content->title = Page::get('title');
			$content->description = Page::get('description');
			$content = ContentLogic::save($content);
			
			$response = new CWI_CONTROLS_EDITABLE_EditableControlJsonResponse();
			return $response;
			//return '{"status":true}';
		} else {
			FrameworkManager::loadStruct('content');
			$content = new ContentStruct();
			$content->title = Page::get('title');
			$content->description = Page::get('description');
			if ($content = ContentLogic::save($content)) {
				$this->setConfigValue('contentId', $content->id);
				
				$response = new CWI_CONTROLS_EDITABLE_EditableControlJsonResponse();
				return $response;
				//return '{"status":true}';
			}
			
			$response = new CWI_CONTROLS_EDITABLE_EditableControlJsonResponse();
			$response->addError('Unable to retrieve content id from configuration.');
			return $response;
			//return '{"status":false,"error":"Unable to retrieve content id from configuration."}';
		}
	}
*/
/*	
	public function handleAdminRequest() {
		if (!Roles::isUserInRole('AdmBase')) return false;
		
		$this->loadView('templates/default.tpl');
		#CWI_EVENT_Manager::addEvent('AssetManager', 'onGetControlAssets', array($this, 'something'));
		//$this->getHeader()->addScriptText('
		Page::addScriptText('
			' . $this->getJsId() . '.onConfigValueChange(\'headerFormat\', function(control_obj, config_name, old_value, new_value) { 
		
				var current_title = control_obj.getObj(\'title\');
				document.getElementById(control_obj.getObjName(\'title\')).commit(); // Close editing view for field if in the middle of editing
				var new_title = $(\'<\' + new_value + \' />\').html(current_title.html()).attr(\'id\', current_title.attr(\'id\'));
				if (new_value == \'none\') {
					new_title.hide();
				} else {
					new_title.show();
				}
				new_title.attr(\'class\', current_title.attr(\'class\'));
				current_title.replaceWith(new_title);
				control_obj.addEditableField(control_obj.getObjName(\'title\')); // Re-init field
				
			});

			jQuery(document).ready(function() {
				var descriptionObj = ' . $this->getJsId() . '.getObjName(\'description\');
				jQuery(\'#\' + descriptionObj + \' a\').live(\'click\', function(e) {
					e.preventDefault();
				});
//												
			});
		');
		#$this->addControlJavascript('test.js');

		$page_control_id = $this->getPageControlid();
		
		$title = Page::getControlById($this->getControlFieldName('title'));
		$description = Page::getControlById($this->getControlFieldName('description'));
		$content_id = $this->getConfigValue('contentId');
		
		$is_new = (empty($page_control_id) || empty($content_id));
		
		$sortorder = $this->getSortorder();
		$placeholder = $this->getPlaceholder();
				
		$header_format = $this->getConfigValue('headerFormat');
		
		if (empty($header_format)) {
			// Make element header 1 if first element in main column, otherwise mark as secondary header by default
			if ($placeholder == 'ph_main' && $sortorder == 1) {
				$header_format = 'h1';
			} else {
				$header_format = 'h2';
			}
		}
		$this->setConfigValue('headerFormat', $header_format);
		// Enable editing
		if ($title) {
			$title->enableEditing(true);
			$title->setTagName($header_format);
			#$title->setContent('Placeholder: ' . $placeholder . '; sortorder: ' . $sortorder);
		}
		
		if ($description) {
			$description->enableEditing(true);
		}
		
		if ($is_new) {
			// Nothing special to be done here
		} else {
			
			FrameworkManager::loadLogic('content');
			#$this->shouldAddControlToolbars(false);
			#if ($content_id = $this->getConfigValue('contentId')) {
				
				$content = ContentLogic::getContentById($content_id);
				
				#$header_format = $this->getConfigValue('headerFormat');
				
				if ($title) {
					$title->setContent($content->title);
				}
				
				if ($description) {
					$description->setContent($content->description);
				}
			#}
		}
	}
*/
/*
	public function handleDefaultRequest() {
		$this->loadView('templates/default.tpl');
		
		if ($content_id = $this->getConfigValue('contentId')) {
			
			FrameworkManager::loadLogic('content');
			
			$content = ContentLogic::getContentById($content_id);
			
			if ($title = Page::getControlById($this->getControlFieldName('title'))) {
				
				if (empty($content->title)) {
					
					$title->visible(false);
					
				} else {
				
					$header_format = $this->getConfigValue('headerFormat');
					
					if ($header_format != 'none') {
						$title->setTagName($header_format);
						$title->setContent($content->title);
					}
				}
			}
			
			if ($description = Page::getControlById($this->getControlFieldName('description'))) {
				$description->setClass('content-body');
				$description->setContent($content->description);
			}
		}
	}
*/
}

?>