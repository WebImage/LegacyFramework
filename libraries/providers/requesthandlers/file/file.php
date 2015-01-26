<?php

/** 
 * Changes
 * 03/22/2012	(Robert Jones) Changed constant name UPDATE_PATH to API_PATH to be more in line with what the constant actually represents
 **/
class FileRequestHandler extends RequestHandler {
	protected $loadControlFile;
	protected $loadControlCodeFile;
	private $systemPath;
	//const UPDATE_PATH = '/__cmsupdate__/index.html';
	const API_PATH = '/api/index.html';
	private $noStats = array(self::API_PATH, '/controlaction.html');
	public function statsEnabled() { return (!in_array($this->getPageRequest()->getRequestedPath(), $this->noStats)); } //($this->getPageRequest()->getRequestedPath() != self::API_PATH); }
	
	/**
	 * @param string $internal_url Generally will be null.  The exception might be in an extending classes which need to modify the internal URL
	 **/
	public function canHandleRequest($internal_url=null) {
		if ($this->getPageRequest()->getRequestedPath() == self::API_PATH) return true;
		
		if (is_null($internal_url)) {
			$internal_url = $this->getPageRequest()->getInternalPath();
		}
		
		$base_file_path = $this->getSystemPath() . $internal_url;
		$check_control = PathManager::translate($base_file_path);
		$check_control_code = PathManager::translate($base_file_path . '.php');
		
		if ($check_control || $check_control_code) {
			$this->loadControlFile = $check_control;
			$this->loadControlCodeFile = $check_control_code;
			return true;
		}
		return false;
	}
	function render() {
		
		/**
		 * Check if this is an API request
		 **/
		if ($this->getPageRequest()->getRequestedPath() == self::API_PATH) {
			FrameworkManager::loadBaseLibrary('providers.requesthandlers.api.api');
			$api_request = new ApiRequestHandler();
			$new_page_request = clone $this->getPageRequest();
			$api_request->setPageRequest( $new_page_request );
			return $api_request->render();
		}
		
		$control_manager = $this->getPageRequest()->getPageResponse()->getControlManager();
		
		/**
		 * If we need to load a control file, load it here, e.g. index.html
		 **/
		
		if ($this->loadControlFile) {
			#$this->getPageRequest()->getPageResponse()->getControlManager
			Page::loadControl($this->loadControlFile);
		}
		
		/**
		 * If there is a control code class then rendering will be passed off to that class
		 */
		if ($this->loadControlCodeFile) { // Pass processing to 
			
			$control_manager->initialize();
			include_once($this->loadControlCodeFile);
			
			ob_start();
			echo eval(" ?>" . $control_manager->render() . "<?php ");
			$output = ob_get_contents();
			ob_end_clean();
			
			return $output;
			
			
			############## From here lower to be replaced by above ##################
			/*
			// Create page controls
			eval(Page::getInitCode());
			// Reset page init code so that we can check if additional init code has been added via the include loadControlCodeFile below
			Page::resetInitCode();
			
			// Include processing files, which allows created page controls to be modified via the control file
			include_once($this->loadControlCodeFile);
			
			// Create page controls that might have been added by the above include (usually when that file calls Page::loadControl())
			eval(Page::getInitCode());
			
			// Build hierarchy of controls

			eval(Page::getAttachInitCode());

// onPreProcessCode should go here

			ob_start();
			eval(Page::getRenderCode());
			$output = ob_get_contents();
			ob_clean();
			$eval = eval(" ?>".$output."<?php ");
			$output = ob_get_contents();
			ob_end_clean();

			return $output;
			*/
		/**
		 * Otherwise this function will handle rendering directly
		 */
		} else {
			
			$control_manager = $this->getPageRequest()->getPageResponse()->getControlManager();
			$control_manager->initialize();
			
			ob_start();
			echo eval(" ?>" . $control_manager->render() . "<?php ");
			$output = ob_get_contents();
			ob_end_clean();
			
			return $output;
			
			########### LEGACY ##########
			/*
			eval( Page::getInitCode() );
			eval( Page::getAttachInitCode() );
			ob_start();
			eval( Page::getRenderCode() );
			$output = ob_get_contents();
			ob_clean();
			$eval = eval(" ?>".$output."<?php ");
			$output = ob_get_contents();
			ob_end_clean();
			return $output;
			*/
		}

	}
	
	public function getSystemPath() {
		$file_path = $this->systemPath;
		if (is_null($file_path)) $file_path = '~/pages';
		if (substr($file_path, -1) == '/') $file_path = substr($file_path, 0, -1);
		return $file_path;
	}
	public function setSystemPath($system_path) {
		$this->systemPath = $system_path;
	}

}

?>