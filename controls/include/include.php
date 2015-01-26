<?php

class IncludeControl extends WebControl {
	#var $m_file;
	private $alreadyIncluded=false;
		
	protected function init() {
		parent::init();
		$this->loadIncludeControlFile();
	}
	
	private function loadIncludeControlFile() {
		
		if (!$this->alreadyIncluded) {
			
			if ($file = $this->getFile()) {
				
				if ($include_file = PathManager::translate($file)) {

					$compiled_control = Page::loadControl($include_file);
					die('include.php');
					/*
					ob_start();
					eval($compiled_control->init_code);
					eval($compiled_control->attach_init_code);
					eval($compiled_control->render_code);
					$contents = ob_get_contents();
					ob_end_clean();
					
					$this->setRenderedContent($contents);
					*/
				}
				
				$this->alreadyIncluded = true;
			}
		}
	}
	
	function prepareContent() {
	
		$this->loadIncludeControlFile();
		
		return;
		if ($include_file = PathManager::translate($this->getFile())) {

			$compiled_control = Page::loadControl($include_file);
			/*
			ob_start();
			eval($compiled_control->init_code);
			eval($compiled_control->attach_init_code);
			eval($compiled_control->render_code);
			$contents = ob_get_contents();
			ob_end_clean();
			
			$this->setRenderedContent($contents);
			*/
		}
	}
	
	function getFile() {
		if ($file = $this->getParam('file')) {
			return $file;
		} else if (!empty($this->m_file)) {
			return $this->m_file;
		} else return false;
	}
	
	function setFile($file_path) { $this->m_file = $file_path; }
}

?>