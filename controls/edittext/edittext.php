<?php

#FrameworkManager::loadLibrary('controls.editable.abstracteditinplacecontrol');
#No longer available: FrameworkManager::loadLibrary('search.ipagecontentsearch');
FrameworkManager::loadControl('edit');

class EditTextControl extends EditControl { # No Longer available: implements CWI_SEARCH_IPageContentSearch {
	protected $devMode = true;
	public function searchKeyword($keyword) { return false; }
	
	private function getTitleFromDescription($description) {
		
		$title = '';
		
		if (preg_match_all('#<h([1-6]{1})>(.+?)</h([1-6]{1})>#', $description, $html_matches)) {
			
			$highest_priority_index = -1;
			
			for ($i=0; $i < count($html_matches[0]); $i++) {
				
				$header_level = intval($html_matches[1][$i]);
				
				if ($highest_priority_index == -1 || ($header_level < $html_matches[1][$highest_priority_index])) {
					$highest_priority_index = $i;
				}
				
			}
			FrameworkManager::debug('EditInsite');
			
			if ($highest_priority_index >= 0) {
				
				$title = $html_matches[2][$highest_priority_index];
				
			}
			
			
		} else {
			FrameworkManager::debug('No matches');
		}
		
		return $title;
		
	}
	
	public function handleAdminRequestPostBack() {
		if (!Roles::isUserInRole('AdmBase')) return false;
		
		$description = Page::get('description');
		$title = self::getTitleFromDescription($description);
		
		FrameworkManager::loadLogic('content');
		if ($content_id = $this->getConfigValue('contentId')) {
			
			$content		= ContentLogic::getContentById($content_id);
			
			$content->title		= $title;
			$content->description	= $description;
			
			$content		= ContentLogic::save($content);
			
			$response = new CWI_CONTROLS_EDITABLE_EditableControlJsonResponse();
			return $response;
			//return '{"status":true}';
		} else {
			FrameworkManager::loadStruct('content');
			
			$content		= new ContentStruct();
			$content->title		= $title;
			$content->description	= $description;
			
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

	public function handleAdminRequest() {
		
		if (!Roles::isUserInRole('AdmBase')) return false;
		
		$this->loadView('templates/default.tpl');
	
		$this->jsClass('EditTextControl', 'edittextcontrol.js');
		
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
			if ($header_format == 'none') {
				$title->setParam('style', 'display:none;');
			} else {
				$title->setTagName($header_format);
			}
			#$title->setContent('Placeholder: ' . $placeholder . '; sortorder: ' . $sortorder);
		}
		
		if ($description) {
			$description->enableEditing(true);
		}
		
		#ob_start();
		#var_dump($description);
		#$debug = nl2br(htmlentities(ob_get_contents()));
		#ob_end_clean();
		#Custodian::log('control', $debug, null, CUSTODIAN_DEBUG);
		
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
}

?>