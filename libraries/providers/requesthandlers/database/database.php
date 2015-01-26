<?php

FrameworkManager::loadLibrary('event.manager');
FrameworkManager::loadLibrary('event.args');

class GetControlsArgs extends CWI_EVENT_Args {
	private $controls;
	function __construct() {
		$this->controls = new ResultSet();
	}
	public function getControls() { return $this->controls; }
}

class DatabaseRequestHandler extends RequestHandler {
	private $requestHandler;
	
	private $pageStruct;
	
	public function getPageStruct() { return $this->pageStruct; }
	public function setPageStruct($page_struct) { $this->pageStruct = $page_struct; }
	
	public function canHandleRequest($internal_url=null) {
		FrameworkManager::loadBaseLogic('page');
		FrameworkManager::loadBaseLogic('pagecontrol');

		if ($page_struct = PageLogic::getPageByUrl($this->getPageRequest()->getInternalPath())) {
			$page_struct->template_id = (empty($page_struct->template_id)) ? '' : $page_struct->template_id;
			$this->setPageStruct($page_struct);
			
			
	#		$is_custom = false;
			if (!empty($this->pageStruct)) {
	
				if ($this->pageStruct->type == 'C') {
					$this->requestHandler = null;
					
					FrameworkManager::loadLogic('servicehandler');
					
					$service_handler_class = $this->pageStruct->service_handler_class;
					if ($service_handler_struct = ServiceHandlerLogic::getServiceHandler('RequestHandler', $service_handler_class)) {
						
						if (!class_exists($service_handler_class)) include_once($service_handler_struct->class_file);
						
						if (class_exists($service_handler_class)) {
							
							$request_handler = new $service_handler_class;
							$request_handler->setPageStruct($this->getPageStruct());
							$request_handler->setPageRequest($this->getPageRequest());
							$request_handler->init();
							$this->requestHandler = $request_handler;
							
						}
					}
				}
			}
		}
		
		return (!empty($this->pageStruct));
	}
	
	protected function init() {}
	
	protected function getControls() {
		$control_args = new GetControlsArgs();
		
		$rs_controls = PageControlLogic::getControlsByPageIdOrTemplateId($this->pageStruct->id, $this->pageStruct->template_id);
		
		while ($control = $rs_controls->getNext()) {
			$control_args->getControls()->add($control);
		}
		
		return $control_args->getControls();
		
	}
	
	function render() {

		if (!empty($this->pageStruct)) {

#FrameworkManager::debug('DatabaseRequestHandler::render()');
			if (!empty($this->pageStruct->title)) Page::setTitle($this->pageStruct->title);
			if (!empty($this->pageStruct->meta_key)) Page::addMetaTag('keywords', $this->pageStruct->meta_key);
			if (!empty($this->pageStruct->meta_desc)) Page::addMetaTag('description', $this->pageStruct->meta_desc);
			
			if (empty($this->pageStruct->template_contents)) { // Load template file
				$template = Page::loadControl($this->pageStruct->template_src);
			} else { // Load template from text
				$template = Page::loadControlByText($this->pageStruct->template_contents);
			}

			//eval( Page::getInitCode() );
						
			//eval( Page::getAttachInitCode() );
			
			$db_controls = $this->getControls();
			
			while ($get_controls = $db_controls->getNext()) {
				
				$control = PageControlLogic::buildControlByPageControlId($get_controls);

				/*
				if ($parent_control = Page::getControlById($get_controls->placeholder)) {
					// Create hierarchal controls
					$parent_control->addControl($control);
					
				}
				*/
				$this->getPageRequest()->getPageResponse()->getRegionControls($get_controls->placeholder)->add($control);
				
			}
			
			#Page::addScriptText($script);
		}

		$control_manager = $this->getPageRequest()->getPageResponse()->getControlManager();
		$control_manager->initialize();
			
		ob_start();
		/*
		echo eval(" ?>" . $control_manager->render() . "<?php ");
		*/
		echo $control_manager->render();
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
		/*
		$time = FrameworkManager::getTime();
		
		ob_start();
		eval( Page::getRenderCode() );
		$output = ob_get_contents();
		ob_clean();
		$output = eval(" ?>" . $output . "<?php ");
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
		
		DebugManager::addMessage('after render  = '. (FrameworkManager::getTime()-$time));
		$time = FrameworkManager::getTime();
		return $output;

		return 'Database';
		*/
	}
	function getPageId() {
		if (is_object($this->pageStruct)) return $this->pageStruct->id; 
		else return false;
	}
	
	function getRequestHandler() {
		if (is_object($this->requestHandler) && is_a($this->requestHandler, 'RequestHandler')) {
			return $this->requestHandler;
		} else return false;
	}
	
}

?>