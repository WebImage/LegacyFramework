<?php

/**
 * Changes
 * 03/22/2012	(Robert Jones) Changed constant name UPDATE_PATH to API_PATH to be more in line with what the constant actually represents
 **/
class FileRequestHandler extends AbstractRequestHandler {
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
//			echo nl2br(htmlentities($control_manager->render()));
//			die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
			ob_start();
			echo eval(" ?>" . $control_manager->render() . "<?php ");
			$output = ob_get_contents();
			ob_end_clean();

			return $output;

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

		}

	}

	/**
	 * Gets the path within the framework structure from which to use as the base for all files.
	 *
	 * @return string
	 */
	public function getSystemPath() {

		$file_path = $this->systemPath;

		// Set the default value to "~/pages" if the value is not set
		if (is_null($file_path)) $file_path = '~/pages';
		// Make sure the path does not have a trailing slash, since it will be prepended to the URL path by default
		if (substr($file_path, -1) == '/') $file_path = substr($file_path, 0, -1);

		return $file_path;
	}
	public function setSystemPath($system_path) {
		$this->systemPath = $system_path;
	}

}

?>
