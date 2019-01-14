<?php

class AdminRequestHandler extends AbstractRequestHandler {
	private $requestHandler;
	private $editingContent = false;
	public function statsEnabled() { return false; }
	#function canHandleRequest() {
	public function canHandleRequest($internal_url=null) {

		if ($this->isAdminRequest()) {
			
			$request_path = $this->getPageRequest()->getInternalUrl()->getPath();
			$admin_path = ConfigurationManager::get('DIR_WS_ADMIN');
			$content_path = ConfigurationManager::get('DIR_WS_ADMIN_CONTENT');
			$home_path = ConfigurationManager::get('DIR_WS_HOME');
			
			$check_admin_content = substr($request_path, 0, strlen($content_path));
			
			if ($check_admin_content == $content_path) { // Editing content page
				
				$request_path = str_replace($content_path, $home_path, $request_path);
				
				FrameworkManager::loadLibrary('providers.requesthandlers.database.database');
				
				$database_request = new DatabaseRequestHandler();
				
				$new_page_request = clone $this->getPageRequest();
				$new_page_request->setInternalUrl(new \WebImage\String\Url($request_path));
				
				$database_request->setPageRequest( $new_page_request );
				
				if ($database_request->canHandleRequest()) { // Database managed page
					$this->requestHandler = $database_request;
					$this->editingContent = true;
					return true;
				} else {
					FrameworkManager::loadLibrary('providers.requesthandlers.file.file');
					$file_request = new FileRequestHandler();
					$file_request->setPageRequest($new_page_request);
					
					if ($file_request->canHandleRequest()) {
						
						$this->requestHandler = $file_request;
						return true;
						die('<div style="width:600px;margin:10px auto;padding:20px;border:1px solid #ccc;">The page you have requested is valid, but is not editable in admin mode.  Please use your browser\'s back button to return to the previous page.</div>');
						#$this->requestHandler = $file_request;
						#return true;
						
					} else {
						
						// Reaches this point if a page cannot be found
						Page::redirect( ConfigurationManager::get('DIR_WS_ADMIN') . 'pages/edit.html?missingurl=' . urlencode($request_path) );
					}
				}
				
			} else { // Page within the admin
				
				$request_path = str_replace($admin_path, $home_path, $request_path);
				
				FrameworkManager::loadLibrary('providers.requesthandlers.file.file');
				
				$file_request = new FileRequestHandler();
				
				$system_path = '~/admin/';
				
				if (substr($request_path, 0, 8) == '/plugins') {
					$path_parts = explode('/', $request_path, 4);
					$plugin_name = $path_parts[2];
					$request_path = '/' . $path_parts[3];
					$system_path = ConfigurationManager::get('DIR_FS_PLUGINS') . $plugin_name . '/admin/';
				}

				$file_page_request = clone $this->getPageRequest();
				$file_page_request->setInternalUrl(new \WebImage\String\Url($request_path));
				
				$file_request->setPageRequest($file_page_request);
				$file_request->setSystemPath($system_path);

				if ($file_request->canHandleRequest()) {
					$this->requestHandler = $file_request;
					return true;
				}
			}
			
		}
		
		return false;
	}
	
	function isAdminRequest() {
		$dir_ws_admin = substr(ConfigurationManager::get('DIR_WS_ADMIN'), 0, -1);
		$check_admin = substr($this->getPageRequest()->getRequestedPath(), 0, strlen($dir_ws_admin));
		if ($dir_ws_admin == $check_admin) return true;
		return false;
	}
	
	function getPageId() {
		if ($request_handler = $this->getRequestHandler()) {
			return $request_handler->getPageId();
		}
		return false;
	}
	
	function getRequestHandler() {
		if (is_object($this->requestHandler) && is_a($this->requestHandler, 'IRequestHandler')) {
			return $this->requestHandler;
		}
		return false;
	}
	
	function render() {
		
		if ($this->getRequestHandler()) {
			
			#$this->getPageRequest()->getPageResponse()->addScript( ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS') . 'jquery/jquery-1.4.2.min.js' );
			#$this->getPageRequest()->getPageResponse()->addScript( 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js' ); # Replaced 2012-10-16
			$this->getPageRequest()->getPageResponse()->addScript('//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js'); #Added 2012-10-16
			#$this->getPageRequest()->getPageResponse()->addScript( ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS') . 'jquery/jquery-ui-1.8.1.custom.min.js' );
			#$this->getPageRequest()->getPageResponse()->addScript( ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS') . 'jquery/jquery-ui-1.8.4.custom.min.js' );
			$this->getPageRequest()->getPageResponse()->addScript('//ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js');
			
			$this->getPageRequest()->getPageResponse()->addScript( ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'core.js' );
			$this->getPageRequest()->getPageResponse()->addScript( ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS') . 'global.js' );
			$this->getPageRequest()->getPageResponse()->addScript( ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS') . 'assetmanager.js' );

			$this->getPageRequest()->getPageResponse()->addScript( ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'tinymce/tiny_mce.js' );
			$this->getPageRequest()->getPageResponse()->addScript( ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'tinymce/jquery.tinymce.js' );
			$this->getPageRequest()->getPageResponse()->addScript( ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'jquery/jquery.clickedit.js' );
			
			// $this->getPageRequest()->getPageResponse()->addStylesheet( ConfigurationManager::get('DIR_WS_GASSETS_CSS') . 'controls.css' );
			
			if ($this->editingContent) { // Only load extra stylesheets if we are in edit content mode
				FrameworkManager::loadManager('theme');
				
				#$content_stylesheets = CWI_MANAGER_ThemeManager::getAdminContentStylesheets( $this->getPageRequest()->getTheme() );
				if ($content_stylesheets = CWI_MANAGER_ThemeManager::getAdminContentStylesheets( ConfigurationManager::get('THEME_ADMIN_CONTENT') )) {
					while ($content_stylesheet = $content_stylesheets->getNext()) {
						$this->getPageRequest()->getPageResponse()->addStylesheet( $content_stylesheet );
					}
				}
			} else {
				
				$this->getPageRequest()->setTheme( ConfigurationManager::get('THEME_ADMIN') );
				
				#$this->getPageRequest()->getTheme('ADMIN')
				#echo 'Theme: ' . $this->getPageRequest()->getTheme() . ' - ' . Page::getTheme();exit;
				#$this->getPageRequest()->setTheme( ConfigurationManager::get('THEME_ADMIN') );
			}

			
			$output = $this->requestHandler->render();
			
			if (is_a($this->requestHandler, 'DatabaseRequestHandler')) {
				
				/* ******** */
				$admin_controls_content = '
					<div class="admin-header-controls">
						<div class="admin-header-controls-left">
							
							<a href="' . ConfigurationManager::get('DIR_WS_ADMIN') . 'pages/"><i class="icon icon-back"></i> Pages</a>
							<a href="' . ConfigurationManager::get('DIR_WS_ADMIN') . 'pages/edit.html"><i class="icon icon-page"></i> Create New Page</a>
						';
				
				if ($page_id = $this->requestHandler->getPageId()) $admin_controls_content .= '
							<a href="' . ConfigurationManager::get('DIR_WS_ADMIN') . 'pages/edit.html?pageid=' . $page_id . '"><i class="icon icon-cog"></i> Meta</a>';
							
				$admin_controls_content .= '
						</div>
						<div class="admin-header-controls-right"></div>
						<br style="clear:both;" />
					</div>';
					
				$admin_controls = preg_replace("#[\n|\r|\t]+#", '', $admin_controls_content);
				$output = preg_replace('#(</body.*?>)#', $admin_controls . '$1', $output);
				/* ******** */
			}
			return $output;
		}
		else return '';
	}
}

?>