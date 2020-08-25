<?php

/**
 * 01/27/2010	(Robert Jones) Modified SiteMap class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 * 02/11/2010	(Robert Jones) Modified loadControlByText so that it automatically evaluates the init code to be generated - as opposed to waiting, which causes some controls to not be available when requested
 * 02/17/2010	(Robert Jones) Added $_FILES to list of variables that are automatically added, similar to $_REQUEST
 * 02/23/2010	(Robert Jones) Added Page::loadFirstValidControl($control_array) to allow multiple control options to be passsed
 * 04/15/2010	(Robert Jones) Added Page::addScriptToTop(...) to allow scripts to be added to the top of the stack
 * 05/08/2010	(Robert Jones) Modified addStyleSheet to allow user to add stylesheet as a premade PageHeaderStylesheet object
 * 07/28/2010	(Robert Jones) Added getActualPath() and getActualQueryString() to allow the retrieval of the browser URL path and query string (as opposed to getPath() and getQueryString() which will now return the path and query string of the request (which may change if sub-requests are performed)
 * 04/27/2012	(Robert Jones) Changed control management in PageResponse from a Dictionary object to a ControlManager object
 * 01/18/2013	(Robert Jones) Changed Page::get() to use the $this->requestedParams->isDefined($name) instead of $this->requestedParams->get($name) because when ->get($name) returned "0" the function returned $default(i.e. false)
 */
FrameworkManager::loadLibrary('controls');

/**
 * Creates a hierarchical context dictionary for storing multi-level values
 **/
/*
 Not yet used
class ContextDictionary extends DictionaryHierarchy {
	
	public function pushContext() {
		$ctx = clone $this;
		$ctx->setParent($this);
		return $ctx;
	}
	
	public function popContext() {
		return $this->getParent();
	}
}
*/
class PageResponse {
	const OUTPUT_TYPE_HTML = 'html';
	const OUTPUT_TYPE_XML = 'xml';
	const OUTPUT_TYPE_JSON = 'json';
	const OUTPUT_TYPE_CSV = 'csv';
	
	private $initCode = array();
	private $attachInitCode = array();
	private $renderCode = array();
	private $controlManager; // ControlManager
	private $regions; // Dictionary - A temporary stash that will allow region and placeholder code to retrieve a list of controls for a specific region
	private $title;
	private $headers; // PageHeader
	private $context;
	/** @var  IServiceManager $serviceManager */
	private $serviceManager;
	/**
	 * Either RESPONSE_OUTPUT_TYPE_HTML, RESPONSE_OUTPUT_TYPE_XML, RESPONSE_OUTPUT_TYPE_JSON
	 * Changes the preferred mode of output - the request handler can choose to ignore this and default to RESPONSE_OUTPUT_TYPE_HTML
	 */
	private $outputType;
	
	public function __construct(WebImage\ServiceManager\IServiceManager $serviceManager) {
		$this->serviceManager = $serviceManager;

		$this->controlManager = $serviceManager->get('ControlManager');
		$this->regions = new Dictionary();
		$this->headers = array(
				       'meta' => new Collection(),
				       'scripts' => new Collection(),
				       'scriptText' => '',
				       'stylesheets' => new Collection()
				       );
	}
	
	public function getControl($control_id) { 
		return $this->controlManager->getControls()->get($control_id); 
	}
	/**
	 * Retrieves an array of controls for a identified region.  If the region has been setup it will be returned here, otherwise an empty array will be returned - ensuring that all calling code will always have an array to work with
	 * @param string $region_key a unique name for a region
	 * @return array An array of control objects
	 **/
	public function getRegionControls($region_key) { 
		$controls = $this->regions->get($region_key);
		if (!is_a($controls, 'Collection')) {
			$controls = new Collection();
			$this->regions->set($region_key, $controls);
		}
		return $controls;
	}
	
	public function getInitCode() { return implode('', $this->initCode); }
	public function getAttachInitCode() { return implode('', $this->attachInitCode); }
	public function getRenderCode() { return implode('', $this->renderCode); }
	public function getTitle() { return $this->title; }
	public function getMetaTags() { return $this->headers['meta']; }
	
	public function getMetaTag($name) {
		$meta_tags = $this->headers['meta'];
		while ($meta_tag = $meta_tags->getNext()) {
			if ($meta_tag->getName() == $name) return $meta_tag->getContent();
		}
		return false;
	}
	
	public function getScripts() { return $this->headers['scripts']; }
	public function getScriptText() { return $this->headers['scriptText']; }
	public function getStyleSheets() { return $this->headers['stylesheets']; }
	public function getOutputType() { return $this->outputType; }
	
	public function getContext() {
		if (is_null($this->context)) $this->context = new Dictionary();
		return $this->context;
	}
	public function getControlManager() { return $this->controlManager; }
	
	public function setOutputType($output_type) {
		if (in_array($output_type, array())) $output_type = PageResponse::OUTPUT_TYPE_HTML;
		$this->outputType = $output_type;
	}
	public function setControl($control_id, $control) {
		$this->controlManager->getControls()->set($control_id, $control);
	}
	public function setTitle($title) { $this->title = $title; }
	
	/**
	 * Sets a specific meta tag
	 * @param string|PageHeaderMetaTag $name
	 * @param string|null $content
	 *
	 * @example setMetaTag('key', 'content')
	 * @example setMetaTag(PageHeaderMetaTag $tag)
	 */
	public function setMetaTag($name_or_tag, $content=null) { // robots, description, keywords
		$new_tag = $this->createPageHeaderMetaTag($name_or_tag, $content);
		
		/** @var Collection $metas */
		$metas = $this->headers['meta'];
		while ($meta_tag = $metas->getNext()) {
			if ($meta_tag->getName() == $new_tag->getName()) {
				$metas->removeAt($metas->getCurrentIndex());
				$metas->resetIndex();
			}
		}
		$this->addMetaTag($new_tag);
	}
	/**
	 * Sets a specific meta tag
	 * @param string|PageHeaderMetaTag $name
	 * @param string|null $content
	 *
	 * @example setMetaTag('key', 'content')
	 * @example setMetaTag(PageHeaderMetaTag $tag)
	 */
	public function addMetaTag($name_or_tag, $content=null) { // robots, description, keywords
		$tag = $this->createPageHeaderMetaTag($name_or_tag, $content);
		$this->headers['meta']->add($tag);
	}
	
	/**
	 * setMetaTag('key', 'content')
	 * setMetaTag(PageHeaderMetaTag $tag)
	 *
	 * @param string|PageHeaderMetaTag $name_or_tag
	 * @param null|string $content
	 *
	 * @return PageHeaderMetaTag
	 */
	private function createPageHeaderMetaTag($name_or_tag, $content=null) {
		if ($name_or_tag instanceof PageHeaderMetaTag) return $name_or_tag;
		
		if (!is_string($name_or_tag)) {
			throw new InvalidArgumentException(sprintf('%s was expecting a string for name', __METHOD__));
		}
		
		return new PageHeaderMetaTag($name_or_tag, $content);
	}
	
	public function addScript($src, $type='text/javascript', $add_to_top=false) {
		while ($script = $this->headers['scripts']->getNext()) {
			if ($script->getSource() == $src) {
				$this->headers['scripts']->resetIndex();
				return true;
			}
		}

		if ($add_to_top) {
			$this->headers['scripts']->insert( new PageHeaderScript($src, $type) );
		} else {
			$this->headers['scripts']->add( new PageHeaderScript($src, $type) );
		}
		
	}

	public function addScriptToTop($src, $type='text/javascript') { // Convenience
		$this->addScript($src, $type, true);
	}
	
	public function addScriptText($text) { $this->headers['scriptText'] .= $text; }
	
	public function addStyleSheet($src, $media='all', $type='text/css', $rel='stylesheet', $add_to_top=false) {
		
		if (is_a($src, 'PageHeaderStylesheet')) {
			$stylesheet = $src;
		} else {
			if (is_null($media)) $media = 'all';
			if (is_null($type)) $type = 'text/css';
			if (is_null($rel)) $rel = 'stylesheet';
			
			$stylesheet = new PageHeaderStylesheet($src, $type, $media, $rel);
		}
		if ($add_to_top) {
			$this->headers['stylesheets']->insert($stylesheet);
		} else {
			$this->headers['stylesheets']->add($stylesheet);
		}
	}
	public function addStyleSheetToTop($src, $media='all', $type='text/css', $rel='stylesheet') {
		$this->addStyleSheet($src, $media, $type, $rel, true);
	}
	
	public function addInitCode($init_code) { array_push($this->initCode, $init_code); }
	public function addAttachInitCode($attach_init_code) { array_push($this->attachInitCode, $attach_init_code); }
	public function addRenderCode($render_code) { array_push($this->renderCode, $render_code); }
	
	/**
	 * Reset init code
	 */
	public function resetInitCode() {
		$this->initCode = array();
	}
	
	
}
class PageRequest {	
	private $requestedParams;
	/** @var \WebImage\String\Url */
	private $internalUrl; // Translated URL
	private $requestedScheme;
	private $requestedDomain;
	private $requestedPath;
	private $requestedQueryString;
	private $requestHandler;
	private $theme;
	private $lock = false;
	private $pageResponse; // PageResponse
	private $serviceManager;
	private $remoteIp;
	private $userAgent;

	public function __construct(WebImage\ServiceManager\IServiceManager $serviceManager, $url, $request_type='GET') { // Request type is not actually used - not sure if we will
		$this->serviceManager = $serviceManager;
		$this->requestedParams = new Dictionary();
		$this->pageResponse = new PageResponse($serviceManager);
		$this->initUrl($url);
	}
	
	public function getPageResponse() {
		return $this->pageResponse;
	}
	public function getRequestHandler() {
		return (is_null($this->requestHandler)) ? false : $this->requestHandler;
	}
	
	public function getAll() { return $this->requestedParams; }
	
	public function get($name, $default=false) {
		if ($this->requestedParams->isDefined($name)) return $this->requestedParams->get($name);
		else return $default;
	}

	/** @return \WebImage\String\Url */
	public function getInternalUrl() { return $this->internalUrl; }
	public function getInternalPath() { return $this->getInternalUrl()->getPath(); }
	public function getInternalQueryString() { return $this->getInternalUrl()->getQueryString(); }
	
	public function getRequestedUrl() {
		$url = '';
		$scheme = $this->getRequestedScheme();
		$domain = $this->getRequestedDomain();
		$path = $this->getRequestedPath();
		$query = $this->getRequestedQueryString();
		
		if (!empty($scheme) && !empty($domain)) $url = sprintf('%s://%s', $scheme, $domain);
		$url .= $path;
		if (!empty($query)) $url .= '?' . $query;
		
		return $url;
	}
	
	public function getRequestedScheme() { return $this->requestedScheme; }
	public function getRequestedDomain() { return $this->requestedDomain; }
	public function getRequestedPath() { return $this->requestedPath; }
	public function getRequestedQueryString() { return $this->requestedQueryString; }
	
	public function getRemoteIp() { return $this->remoteIp; }
	public function setRemoteIp($ip) { $this->remoteIp = $ip; }
	
	public function getUserAgent() { return $this->userAgent; }
	public function setUserAgent($userAgent) { $this->userAgent = $userAgent; }
	
	public function getTheme() { return $this->theme; }
	public function setTheme($theme) { $this->theme = $theme; }
	
	public function setRequestHandler($handler) {
		if (class_implements($handler, 'IServiceAManagerAware')) {
			$handler->setServiceManager($this->serviceManager);
		}
		$this->requestHandler = $handler;
	}

	private function setRequestedScheme($scheme) { $this->requestedScheme = $scheme; }
	private function setRequestedDomain($domain) { $this->requestedDomain = $domain; }
	private function setRequestedPath($requested_path) { $this->requestedPath = $requested_path; }
	private function setRequestedQueryString($query_string) { $this->requestedQueryString = $query_string; }
	
	public function setInternalUrl(\WebImage\String\Url $url) { $this->internalUrl = $url; }
	
	/**
	 * Breaks out a URL into its various parts and sets up the internal URL structure
	 * @param $url
	 */
	private function initUrlParts($url) {
		$url = str_replace('/index.php', '/index.html', $url);
		
		$url_scheme = '';
		$url_domain = '';
		$url_query = '';
		$url_parts = parse_url($url);
		$url_path = $url_parts['scheme'];
		
		if (isset($url_parts['scheme']) && isset($url_parts['host'])) {
			$url_scheme = $url_parts['scheme'];
			$url_domain = $url_parts['host'];
		}
		
		if (isset($url_parts['path'])) $url_path = $url_parts['path'];
		if (isset($url_parts['query'])) $url_query = $url_parts['query'];
		
//		$requested_page_parts = explode('/', $url_path);
//		$page_part = $requested_page_parts[count($requested_page_parts)-1];
		
		if (!defined('DEFAULT_FILE_NAME')) define('DEFAULT_FILE_NAME', 'index');
		ConfigurationManager::set('DEFAULT_FILE_NAME', DEFAULT_FILE_NAME);
		
		if (!defined('DEFAULT_FILE_EXT')) define('DEFAULT_FILE_EXT', 'html');
		ConfigurationManager::set('DEFAULT_FILE_EXT', DEFAULT_FILE_EXT);
		
		$default_file = DEFAULT_FILE_NAME . '.' . DEFAULT_FILE_EXT;
		ConfigurationManager::set('DEFAULT_FILE', $default_file);
		
		$period_pos = strrpos($url_path, '.');
		
		if ($period_pos === false) {
			if (substr($url_path, -1, 1) != '/') {
				$url_path .= '/';
			}
			$url_path .= $default_file;
			$this->getPageResponse()->setOutputType(PageResponse::OUTPUT_TYPE_HTML);
		} else {
			$extension = substr($url_path, $period_pos+1);
			$this->getPageResponse()->setOutputType($extension);
			if ($extension != PageResponse::OUTPUT_TYPE_HTML) {
				$url_path = substr($url_path, 0, $period_pos+1) . PageResponse::OUTPUT_TYPE_HTML;
			}
			$this->getPageResponse()->setOutputType($extension);
		}
		
		$this->setRequestedScheme($url_scheme);
		$this->setRequestedDomain($url_domain);
		$this->setRequestedPath($url_path);
		$this->setRequestedQueryString($url_query);
	}
	
	/**
	 * Make any modifications to the URL for internal purposes
	 */
	private function doUrlRemapping() {
		$config = ConfigurationManager::getConfig();
		
		$url = new \WebImage\String\Url($this->getRequestedUrl());
		/**
		 *
		 * This section determines whether the request was for a file or directory.
		 * If the request is for a directory then the default file "index.html" is set and appended to the requested file path
		 *
		 */
		$config_path_mappings = (isset($config['pages']['pathMappings'])) ? $config['pages']['pathMappings'] : array();
		$config_request_handlers = (isset($config['pages']['requestHandlers'])) ? $config['pages']['requestHandlers'] : array();
		
		foreach($config_path_mappings as $mapping) {
			
			$search = '#' . str_replace('#', '##', $mapping['path']) . '#';
			
			if (preg_match($search, $url->getPath(), $path_matches)) {
				
				// Add parameters
				if (isset($mapping['params'])) {
					foreach($mapping['params'] as $key => $val) {
						if (is_int($key)) {
							$ref_ix = $key + 1;
							$key = $val;
							$val = '$' . $ref_ix;
						}
						
						// Replace any $[num] references to their position in the matched path
						if (preg_match_all('/\$([0-9])/', $val, $matches)) {
							for($i=0, $j=count($matches[0]); $i < $j; $i++) {
								$ix = $matches[1][$i];
								$val = str_replace('$' . $ix, $path_matches[$ix], $val);
							}
						}
						$this->set($key, $val);
					}
				}
				
				// Assign parameters for mapping
				if (count($path_matches) > 0) {
					
					$translated_path = isset($mapping['translate']) ? $mapping['translate'] : '';
					
					if (!empty($translated_path)) {
						
						for ($i=count($path_matches)-1; $i > 0; $i--) {
							$translated_path = str_replace('$'.$i, $path_matches[$i], $translated_path);
						}
						
						$translated_path_parts = explode('?', $translated_path, 2);
						if (!empty($translated_path_parts[0])) $url->setPath($translated_path_parts[0]);
						
						if (isset($translated_path_parts[1])) {
							$translated_vars = explode('&', $translated_path_parts[1]);
							
							foreach($translated_vars as $var_set) {
								$name_value = explode('=', $var_set, 2);
								$name = $name_value[0];
								$value = '';
								
								if (isset($name_value[1])) $value = $name_value[1];
								
								$this->set($name, $value);
							}
						}
					}
					
					$handler_name = (isset($mapping['requestHandler'])) ? $mapping['requestHandler'] : null;
					
					if (null !== $handler_name) {
						
						$handler_config = (isset($config_request_handlers[$handler_name])) ? $config_request_handlers[$handler_name] : null;
						
						if (null !== $handler_config) {
							
							if ($handler_file = PathManager::translate($handler_config['classFile'])) include_once($handler_file);
							
							$class_name = $handler_config['className'];
							
							if (class_exists($class_name)) {
								$handler = new $class_name();
								$handler->setPageRequest($this);
								$this->setRequestHandler($handler);
							}
						}
						
						break;
					}
				}
			}
		}
		
		$this->setInternalUrl($url);
	}
	
	private function checkAccessRights() {
		/**
		 * Check if user is allowed access to this location
		 **/
		$user_allowed = true;
		$location_roles = ConfigurationManager::getLocationRoles();
		$check_url = PathManager::getPath();
		
		foreach($location_roles as $location_role) {
			
			$search = '#^' . str_replace('#', '##', $location_role['path_regex']) . '$#';
			
			// Check the internal and external paths for matches
			if (preg_match($search, $this->getRequestedPath(), $path_matches) || preg_match($search, $check_url, $path_matches)) {
				
				if (count($location_role['roles']) > 0) {
					$user_allowed = false;
					foreach($location_role['roles'] as $role) {
						if (Roles::isUserInRole($role)) {
							$user_allowed = true;
							break;
						}
					}
					// Break out of outer $location_role loop to prevent location overlaps that might otherwise allow the user access to this page
					if (!$user_allowed) break;
				}
			}
		}
		
		if (!$user_allowed) {
			$return_path = $this->getRequestedPath();
			if (strlen($this->getRequestedQueryString()) > 0) $return_path .= '?' . $this->getRequestedQueryString();
			
			SessionManager::set('returnpath', $return_path);
			$redirect_url = ConfigurationManager::get('URL_LOGIN') . '?type=unauthorized';
			
			// Attach the domain that we are currently on so that the user can be redirected back
			if (substr($redirect_url, 0, 4) == 'http') {
				$redirect_url .= '&fromdomain=' . ConfigurationManager::get('DOMAIN');
			}
			
			Page::redirect( $redirect_url );
		}
	}
	
	private function initUrl($url) {
		
		$this->initUrlParts($url);
		$this->doUrlRemapping();
		$this->checkAccessRights();
		
		/**
		 * Check whether page should be loaded from a file or generated from a database
		 */
		$request_handler_found = false;
		
		if ($request_handler = $this->getRequestHandler()) {
			$request_handler->setPageRequest($this);

			if ($request_handler->canHandleRequest()) {
				$request_handler_found = true;
				$this->setRequestHandler($request_handler);
			}
		}
		
		if (!$request_handler_found) {

			$request_handlers = ConfigurationManager::getRequestHandlers();

			foreach($request_handlers as $request_handler) {
				include_once($request_handler['file']);
				$class_name = $request_handler['class'];
				$handler = new $class_name($this);

				if ($handler->canHandleRequest()) {
					$this->setRequestHandler($handler);
					break;
				}
			}
			
		}

		if (!$this->getRequestHandler()) die("NO HANDLER");

	}
	
	public function set($name, $value) {
		$this->requestedParams->set($name, $value);
	}
}
class PageHeader {
	function __construct() {}
	function renderHtml() {}
}
class PageHeaderStylesheet extends PageHeader {
	var $src, $type, $media, $rel;
	function __construct($src, $type='text/css', $media='all', $rel='stylesheet') {
		$this->src = $src;
		$this->type = $type;
		$this->media = $media;
		$this->rel = $rel;
	}
	public function getSrc() { return $this->src; }
	function renderHtml() {
		return '<link href="'.$this->src . '" type="' . $this->type . '" rel="' . $this->rel . '" media="' . $this->media . '" />';
	}
}
class PageHeaderScript extends PageHeader {
	var $src, $type;
	function __construct($src, $type='text/javascript') { $this->src = $src; $this->type = $type; }
	function renderHtml() {
		return '<script src="' . $this->getSource() . '" type="' . $this->getType() . '"></script>';
	}
	function getSource() { return $this->src; }
	function getType() { return $this->type; }
}
class PageHeaderMetaTag extends PageHeader {
	var $name, $content;
	private $nameAttribute = 'name';
	private $valueAttribute = 'content';
	
	/**
	 * PageHeaderMetaTag constructor.
	 * @param $name
	 * @param $content
	 * @param string|null $name_attribute Allows the "name? attribute to be overridden, e.g. "property" instead of "name" - thanks Facebook
	 * @param string|null $value_attribute Allows the "content" attribute to be overridden, e.g. "value" instead of "content" - adding just in case
	 */
	public function __construct($name, $content, $name_attribute=null, $value_attribute=null) {
		$this->name = $name;
		$this->content = $content;
		if (null !== $name_attribute && is_string($name_attribute)) $this->nameAttribute = $name_attribute;
		if (null !== $value_attribute && is_string($value_attribute)) $this->valueAttribute = $value_attribute;
	}
	
	public function renderHtml() {
		$name_attribute = $this->nameAttribute;
		if (empty($name_attribute)) $name_attribute = 'name';
		return sprintf('<meta %s="%s" %s="%s" />',
			$this->getNameAttribute(),
			htmlentities($this->getName()),
			$this->getValueAttribute(),
			htmlentities($this->getContent())
		);
	}
	
	public function getName() { return $this->name; }
	public function getContent() { return $this->content; }
	public function getNameAttribute() {
		$name_attribute = $this->nameAttribute;
		if (empty($name_attribute)) $name_attribute = 'name';
		
		return $name_attribute;
	}
	public function getValueAttribute() {
		$value_attribute = $this->valueAttribute;
		if (empty($value_attribute)) $value_attribute = 'content';
		
		return $value_attribute;
	}
}

FrameworkManager::loadLibrary('event.args');
class PageRedirectArgs extends CWI_EVENT_Args {
	private $redirectUrl, $httpResponseCode;
	function __construct($redirect_url, $http_response_code) {
		$this->redirectUrl = $redirect_url;
		$this->httpResponseCode = $http_response_code;
	}
	public function getRedirectUrl() { return $this->redirectUrl; }
	public function getHttpResponseCode() { return $this->httpResponseCode; }
	
	public function setRedirectUrl($url) { $this->redirectUrl = $url; }
	public function setHttpResponseCode($code) { $this->httpResponseCode = $code; }
}

class Page {
	private $_initialized = false;

	/**
	 * Variables for use if page is loaded from a file/control
	 */
	var $m_controls = array();
	
	/**
	 * Whether or not to render this page
	 */
	var $_renderPage = true;
	
	/**
	 * Variables for use if page is loaded from the database
	 */
	var $m_pageId;
	var $m_pageStatId;
	var $m_dbControls = array();
	/**
	 * Whether or not config\render.php should automatically render the selected page
	 */
	var $m_autoRender = true;
	/**
	 * Variables specific to page path and header info
	 */
	var $m_requestedPath;
	var $m_pageDir;
	var $m_pageFile;
	
	var $m_requestedPathFileType; // File or Dir
	var $m_requestedParams = array();
	var $m_pageTitle;
	var $m_metaTags = array();
	var $m_scripts = array();
	var $m_scriptText;
	var $m_styleSheets = array();
	
	var $m_autoFormStructs = array();
	
	var $_siteMap; // Site Map Object
	
	// Parameters specific to page being viewed
	var $_parametersInitialized = false;
	var $_parameters;
	
	var $_requestHandler; // The process that handles the page rendering
	var $_pageRequests = array();

	/**
	 * @var ServiceManager
	 */
	protected $serviceManager;

	public static function isSecure() {
		return (
			(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
			(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
		);
	}

	public static function requireSecureConnection() {

		if ( !Page::isSecure()) { //!isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'on') ) {
			if (Page::isPostBack()) { // There's really no way to salvage POSTed unsecure form data - get the user out of here
				Page::redirect('/errors/nosecureconnection.html');
			} else {
				// Redirect the user to the secure version of this page
				Page::redirect(PathManager::getSecureUrl());
			}
		}
	}

	/**
	 * @return Page
	 */
	public static function getInstance() {
		/*
		static $instance;
		if (!isset($instance[0])) {
			$instance[0] = new Page();
			$instance[0]->init();
		}
		return $instance[0];
		*/
		$instance = Singleton::getInstance('Page');
		$instance->init($instance);
		return $instance;
	}
	
	// Build Header
	public static function renderHeader($debug=false) {
		$header = '';
		
		// Add required Admin files
		if (Page::isAdminRequest()) {
			/* Moving to admin requesthandler
			Page::addScript(ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS') . 'global.js');
			Page::addScript( ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS') . 'jquery/jquery-1.4.2.min.js');
			Page::addScript( ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS') . 'jquery/jquery-ui-1.8.1.custom.min.js');

			Page::addScript(ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'tinymce/tiny_mce.js');
			Page::addScript(ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'tinymce/jquery.tinymce.js');
			Page::addScript(ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'jquery/jquery.clickedit.js');
			Page::addStylesheet(ConfigurationManager::get('DIR_WS_GASSETS_CSS') . 'controls.css');
			*/
		}
		
		// Title
		$page_title = Page::getTitle();
		if (!empty($page_title)) {
			if ($debug) $header .= "\r\n<!--  // Page Title // -->\r\n";
			$header = '<title>'.Page::getTitle() . '</title>';
			if ($debug) $header .= "\r\n";
		}
		
		// Meta Tags
		$meta_tags = Page::getMetaTags();
		
		$meta_tags->add( new PageHeaderMetaTag('generator', 'EditInSite') );
		//$meta_tags->add( new PageHeaderMetaTag('revisit-after', '7 days') );
		$meta_tags->add( new PageHeaderMetaTag('copyright', 'Copyright &copy; ' . date('Y') . '.  All rights reserved.') );
		$meta_tags->add( new PageHeaderMetaTag('author', 'Corporate Web Image, Inc.') );
		
		if ($debug) $header .= "\r\n<!-- // Meta Tags // -->\r\n";
		while ($meta = $meta_tags->getNext()) {
			$header .= $meta->renderHtml();
			if ($debug) $header .= "\r\n";
		}
		
		// Stylesheets
		if ($debug) $header .= "\r\n<!-- // Stylesheets // -->\r\n";
		while ($style = Page::getStyleSheets()->getNext()) {
			$header .= $style->renderHtml();
			if ($debug) $header .= "\r\n";
		}
		
		// Scripts
		if ($debug) $header .= "\r\n<!-- // Script Files // -->\r\n";
		while ($script = Page::getScripts()->getNext()) {
			$header .= $script->renderHtml();
			if ($debug) $header .= "\r\n";
		}
		
		$script_text = Page::getScriptText();
		if (!empty($script_text)) $header .= '<script type="text/javascript" language="javascript">' . $script_text . '</script>';
		
		return $header;		
		
	}
	
	// Header Functions
	public static function getTitle() {
		$_this = Page::getInstance();
		return $_this->getCurrentPageRequest()->getPageResponse()->getTitle();
	}
	public static function getScripts() { return Page::getCurrentPageRequest()->getPageResponse()->getScripts(); }
	public static function getScriptText() { return Page::getCurrentPageRequest()->getPageResponse()->getScriptText(); }
	
	public static function getMetaTags() { return Page::getCurrentPageRequest()->getPageResponse()->getMetaTags(); } // robots, description, keywords
	public static function getMetaTag($name) { return Page::getCurrentPageRequest()->getPageResponse()->getMetaTag($name); }
	
	public static function getStyleSheets() { return Page::getCurrentPageRequest()->getPageResponse()->getStylesheets(); }
	
	public static function setTitle($title) { Page::getCurrentPageRequest()->getPageResponse()->setTitle($title); }
	
	public static function setMetaTag($name, $content) { Page::getCurrentPageRequest()->getPageResponse()->setMetaTag($name, $content); } // robots, description, keywords
	
	public static function addMetaTag($name, $content) { Page::getCurrentPageRequest()->getPageResponse()->addMetaTag($name, $content); } // robots, description, keywords
	
	public static function addScript($src, $type='text/javascript', $add_to_top=false) { Page::getCurrentPageRequest()->getPageResponse()->addScript($src, $type, $add_to_top); }
	
	public static function addScriptToTop($src, $type='text/javascript') { Page::getCurrentPageRequest()->getPageResponse()->addScriptToTop($src, $type); }
	
	public static function addScriptText($text) { Page::getCurrentPageRequest()->getPageResponse()->addScriptText($text); }
	
	public static function addStyleSheet($src, $media='all', $type='text/css', $rel='stylesheet', $add_to_top=false) { Page::getCurrentPageRequest()->getPageResponse()->addStyleSheet($src, $media, $type, $rel, $add_to_top); }
	
	public static function addStyleSheetToTop($src, $media='all', $type='text/css', $rel='stylesheet') { Page::getCurrentPageRequest()->getPageResponse()->addStyleSheetToTop($src, $media, $type, $rel); }
	
	public static function setTheme($theme) { Page::getCurrentPageRequest()->getPageResponse()->setTheme($theme); }
	
	public static function loadControlByText($control_contents) {
		
		static $times_called;
		if (!$times_called) $times_called = 0;
		$times_called ++;
		
		$control_manager = Page::getCurrentPageRequest()->getPageResponse()->getControlManager();
		$control_manager->loadControlsFromText($control_contents);

		// Initialize all current controls
		$control_manager->initialize();
		
		return $control_manager;
		
	}
	
	public static function loadControl($control_file) {
		static $times_called;
		if (!$times_called) $times_called = 0;
		$times_called ++;
		
		FrameworkManager::loadLibrary('xml.compile');

		$_this = Page::getInstance();
		
		$control_contents = '';

		if ($file_path = PathManager::translate($control_file)) {
			
			$control_contents = file_get_contents($file_path);
			
		}
		return Page::loadControlByText($control_contents);
	}
	/**
	 * Take an array of controls and load the first one that is valid (ignoring the rest
	 */
	public static function loadFirstValidControl($control_file_array) {
		if (is_array($control_file_array)) {
			foreach($control_file_array as $control_file) {
				if ($valid_file = PathManager::translate($control_file)) {
					$control_contents = file_get_contents($valid_file);
					return Page::loadControlByText($control_contents);
				}
			}
		}
		return false;
	}
	
	/**
	 * @return PageRequest
	 * @throws Exception
	 */
	public static function getCurrentPageRequest() {
		$_this = Page::getInstance();
		$index = count($_this->_pageRequests)-1;
		
		if ($index < 0) {
			throw new Exception('Current page request is not available.');
		}
		
		return $_this->_pageRequests[$index];
		
	}
	public static function createPageRequest($url) {
		$_this = Page::getInstance();
		$page_request = new PageRequest($_this->getServiceManager(), $url);
		array_push($_this->_pageRequests, $page_request);
		return $page_request;
	}
	
	private static function isRootPageRequest() {
		$_this = Page::getInstance();
		return (count($_this->_pageRequests) == 1);
	}
	
	public static function endCurrentPageRequest() {
		$_this = Page::getInstance();
		array_pop($_this->_pageRequests);
	}

	public static function getServiceManager() {
		$_this = Page::getInstance();
		return $_this->serviceManager;
	}
	private function init(Page $instance) {

		if ($instance->_initialized) return true;
		$instance->_initialized = true;

		$instance->serviceManager = FrameworkManager::getApplication()->getServiceManager();

		if (Page::isAdminRequest()) {
			
			if (!Roles::isUserInRole('AdmBase')) {

				if (!$user = Membership::getUser(1)) { // Make sure root user actually exists
				
					FrameworkManager::loadStruct('membership');
					FrameworkManager::loadLogic('role');
					FrameworkManager::loadStruct('role');
					
					$membership_struct = new MembershipStruct();
					$membership_struct->username = 'Admin';
					$membership_struct->password = 'password';
					$user = Membership::createUser($membership_struct);
					
					$role_struct = new RoleStruct();
					$role_struct->description = 'Admin Account';
					$role_struct->name = 'AdmBase';
					$role_struct->visible = 0;
					RoleLogic::save($role_struct);

					Roles::addUserToRole('AdmBase', $user, true);
				}
				
				$path_parts = parse_url($_SERVER['REQUEST_URI']);
				
				$return_path = (isset($path_parts['path'])) ? $path_parts['path'] : '';
				$query = (isset($path_parts['query']) ? $path_parts['query'] : '');
				if (!empty($query)) $return_path .= '?' . $query;	
				
				SessionManager::set('returnpath', $return_path);
				Page::redirect( ConfigurationManager::get('FILE_WS_ADMIN_LOGIN') );
			}
		}
		
		$instance->_parameters = new Dictionary();

		$page_request = $instance->createPageRequest( PathManager::getCurrentUrl() );
		$page_request->setRemoteIp(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0');
		$page_request->setUserAgent(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		
		foreach($_REQUEST as $request_name=>$request_value) {
			$page_request->set($request_name, $request_value);
		}
		
		if (isset($_FILES) && is_array($_FILES)) {
			foreach($_FILES as $file_name=>$file_value) {
				$page_request->set($file_name, $file_value);
			}
		}
		
	}
	
	public static function getRequestedPath() {
		if ($request = Page::getCurrentPageRequest()) {
			return $request->getRequestedPath();
		} else return false;
	}
	public static function getRequestedPathFileType() { 
		$_this = Page::getInstance();
		return $_this->m_requestedPathFileType;
	}
	/**
	 * Get the path of the URL as it appears in the browser
	 */
	public static function getActualPath() {
		$path_parts = parse_url($_SERVER['REQUEST_URI']);
		return $path_parts['path'];
	}
	/**
	 * Get the query string of the URL as it appears in the browser
	 */
	public static function getActualQueryString() {
		$path_parts = parse_url($_SERVER['REQUEST_URI']);
		return (isset($path_parts['query']) ? $path_parts['query'] : '');
	}
	/**
	 * Get the path of the current request
	 */
	public static function getPath() {
		if ($request = Page::getCurrentPageRequest()) {
			return $request->getRequestedPath();
		} else return false;
	}
	public static function getQueryString() {
		if ($request = Page::getCurrentPageRequest()) {
			return $request->getRequestedQueryString();
		} else return false;
	}
	
	public static function getReturnPath() {
		$path = Page::getPath();
		$query = Page::getQueryString();
		$return_path = $path;
		if (!empty($query)) $return_path .= '?' . $query;
		return $return_path;
	}
	
	public static function getPageDir() {
		$_this = Page::getInstance();
		return $_this->m_pageDir;
	}
	
	public static function getPageFile() {
		$_this = Page::getInstance();
		return $_this->m_pageFile;
	}
	
	public static function getPageClass() {
		$_this = Page::getInstance();
		$file = $_this->getPageFile();
		//  /the-product-file.html
		//  _the_product_file_html
		// _theproductfile_html
		$class = '_' . str_replace('.', '_', preg_replace('/[^a-zA-Z\.]*/', '', $file));
		return $class;
	}
	
	public static function setRequestedPath($path) {
		$_this = Page::getInstance();
		$_this->m_requestedPath = $path;
	}
	
	public static function getAll() {
		if ($request = Page::getCurrentPageRequest()) {
			return $request->getAll();
		}
		return false;
	}
	
	public static function get($name, $default=false) {
		$_this = Page::getInstance();
		if ($request = $_this->getCurrentPageRequest()) {
			$value =  $request->get($name, $default);
			return $request->get($name, $default);
		} else {
			return $default;
		}
		
		if (isset($_this->m_requestedParams[$name])) return $_this->m_requestedParams[$name]; else return $default;
	}
	
	public static function set($name, $value) {
		$_this = Page::getInstance();
		if ($request = $_this->getCurrentPageRequest()) {
			$request->set($name, $value);
		}
	}
	
	public static function _getDefinedAutoStructs() {
		$_this = Page::getInstance();
		return $_this->m_autoFormStructs;
	}
	public static function _addAutoStruct($struct_name, $object) {
		$_this = Page::getInstance();
		$_this->m_autoFormStructs[$struct_name] = $object;
	}
	public static function _clearAutoStruct($struct_name) {
		$_this = Page::getInstance();
		if (isset($_this->m_autoFormStructs[$struct_name])) {
			unset($_this->m_autoFormStructs[$struct_name]);
		}
	}
	
	public static function getStructFieldValue($struct, $field) {
		if ($structure = Page::getStruct($struct)) {
			if (isset($structure->$field)) return $structure->$field;
			else  return false;
		} else return false;
	}
	public static function getStruct($struct) {
		@FrameworkManager::loadStruct($struct);
		$name_parts = explode('_', $struct);
		$struct_name = '';
		foreach($name_parts as $part) {
			$part = strtoupper($part[0]) . substr($part, 1, strlen($part)-1);
			$struct_name .= $part;
		}
		$struct_name .= 'Struct';
		if (!class_exists($struct_name)) $struct_name = 'stdClass';
		
		$auto_structs = Page::_getDefinedAutoStructs();

		if (isset($auto_structs[$struct])) { // Already defined
			return $auto_structs[$struct];
		} else if ($auto = Page::get('auto')) { // Get from POST
			$obj = new $struct_name;

			if (isset($auto[$struct])) {
				$vars = $auto[$struct];
				foreach($vars as $var=>$val) {
					$obj->$var = $val;
				}
			}
			Page::_addAutoStruct($struct, $obj);
			return $obj;
		} else if (class_exists($struct_name)) {
			return new $struct_name;
		} else {
			return false;
		}
	}
	
	public static function setStruct($struct_name, $struct_value) {
		Page::_addAutoStruct($struct_name, $struct_value);
		$auto = Page::_getDefinedAutoStructs();

		//Page::set('auto', $auto);
	}
	
	public static function getRequestType() { return $_SERVER['REQUEST_METHOD']; }
	public static function isPostBack() { return ($_SERVER['REQUEST_METHOD'] == 'POST'); }
	public static function isAdminRequest() {
		if (!isset($_SERVER['REQUEST_URI'])) return false;
		$path_parts = parse_url($_SERVER['REQUEST_URI']);
		$path = (isset($path_parts['path'])) ? $path_parts['path'] : '';
		
		$dir_ws_admin = substr(ConfigurationManager::get('DIR_WS_ADMIN'), 0, -1);
		$check_admin = substr($path, 0, strlen($dir_ws_admin));
		if ($check_admin == $dir_ws_admin) {
			return true;
		}
		return false;
	}
	
	public static function getInitCode() {
		$_this = Page::getInstance();
		return $_this->getCurrentPageRequest()->getPageResponse()->getInitCode();
	}
	/**
	 * Reset init code
	 */
	public static function resetInitCode() { 
		$_this = Page::getInstance();
		$_this->getCurrentPageRequest()->getPageResponse()->resetInitCode();
	}
	public static function getAttachInitCode() {
		$_this = Page::getInstance();
		return $_this->getCurrentPageRequest()->getPageResponse()->getAttachInitCode();
	}
	public static function getRenderCode() {
		$_this = Page::getInstance();
		return $_this->getCurrentPageRequest()->getPageResponse()->getRenderCode();
	}
	public static function addControl($control_id, $control) {
		$_this = Page::getInstance();
		$_this->getCurrentPageRequest()->getPageResponse()->setControl($control_id, $control);
	}
	
	public static function getExistingControlById($control_id, &$control) {
		$_this = Page::getInstance();
		
		$page_response = $_this->getCurrentPageRequest()->getPageResponse();
		$control = $page_response->getControl($control_id);
		return ($control) ? true:false;
	}
	
	public static function getControlById($control_id) {
		$_this = Page::getInstance();
		
		return $_this->getCurrentPageRequest()->getPageResponse()->getControl($control_id);
	}
	
	public static function getRegionControls($region_key) {
		$_this = Page::getInstance();
		
		return $_this->getCurrentPageRequest()->getPageResponse()->getRegionControls($region_key);
	}
	
	public static function render($url=null) {

		$output = '';

		$_this = Page::getInstance();

		$new_request = (!is_null($url) && !empty($url));

		// If URL defined, create new PageRequest and add it to the stack
		if ($new_request) {
			$_this->createPageRequest($url);
		}

		if ($in_context_page_request = $_this->getCurrentPageRequest()) {

			if ($request_handler = $in_context_page_request->getRequestHandler()) {

				if ($_this->isRootPageRequest() && $request_handler->statsEnabled() && ConfigurationManager::get('ENABLE_PAGE_STATS') == 'true') {

					FrameworkManager::loadLogic('pagestat');

					if ($stat = PageStatLogic::createStatForCurrentUser( Page::getPath(), Page::getQueryString())) {
						Page::setPageStatId($stat->id);
					}
				}

				$output = $request_handler->render();
			}
		}

		// Close current page request by popping it from the stack if it was created within this method
		if ($new_request) {
			$_this->endCurrentPageRequest();
		}

		return $output;
	}
	
	/**
	 * Getter/Setter for $m_autoRender
	 * Determines whether the page should automatically be rendered (auto_render=true), or whether render needs to be called manually (auto_render=false)
	 */
	public static function autoRender($auto_render=null) {
		$_this = Page::getInstance();
		if (is_null($auto_render)) return $_this->m_autoRender;
		else $_this->m_autoRender = $auto_render;
	}
	
	public static function getPageId() {
		$_this = Page::getInstance();
		if ($request_handler = $_this->getCurrentPageRequest()->getRequestHandler()) {
			return $request_handler->getPageId();
		}
		return false;
	}
	public static function getPageStatId() {
		if (Page::isRootPageRequest()) {
			$_this = Page::getInstance();
			if (!empty($_this->pageStatId)) return $_this->pageStatId;
		}
		return false;
	}
	private static function setPageStatId($id) {
		$_this = Page::getInstance();
		$_this->pageStatId = $id;
	}
	
	public static function redirect($redirect_url, $http_response_code=null) {
		
		$_this = Page::getInstance();
		
		FrameworkManager::loadLibrary('event.manager');
		
		$args = new PageRedirectArgs($redirect_url, $http_response_code);
		
		if (CWI_EVENT_Manager::trigger($_this, 'redirecting', $args)) {
			if (!is_null($http_response_code) && in_array($http_response_code, array(301, 302, 303))) {
				if (!headers_sent()) header('Location: ' . $redirect_url, true, $http_response_code);
			} else {
				if (!headers_sent()) header('Location: ' . $redirect_url);
			}
			exit;
		}
		
	}
	
	public static function notifyRedirect($redirect_url, $notification_message, $delay_seconds=5) {
		$output = '<html><head><meta http-equiv="refresh" content="' . $delay_seconds . ';url=' . $redirect_url . '"><body><div style="padding:20px;font-size:large;">';
		$output .= '<p>' . $notification_message . '</p>';
		$output .= '<p>You will automatically be transferred in ' . $delay_seconds . ' seconds.  <a href="' . $redirect_url . '">Click here to go now.</a>';
		$output .= '</div></body></html>';
		echo $output;exit;
	}
	
	// Options specific to page
	public static function _initParameters() {
		$_this = Page::getInstance();
		if (!$_this->_parametersInitialized) {
			FrameworkManager::loadLogic('pageparameter');
			$parameters = PageParameterLogic::getPageParametersByPageId($_this->getPageId());
			while ($parameter = $parameters->getNext()) {
				$_this->setParameter($parameter->parameter, $parameter->value);
			}
		}
	}
	
	public static function getRequestHandler() {
		$_this = Page::getInstance();
		return $_this->getCurrentPageRequest()->getRequestHandler();
	}
	
	public static function getParameter($parameter_name) {
		$_this = Page::getInstance();
		$_this->_initParameters();
		if ($parameter_value = $_this->_parameters->get($parameter_name)) {
			return $parameter_value;
		} else return false;
	}
	
	public static function getRequestedUrl() {
		$_this = Page::getInstance();
		return $_this->getCurrentPageRequest()->getRequestedUrl();
	}
	
	/**
	 * string $context the context to use if the default request does not find a theme (can be null [for default] or ADMIN or ASSETMANAGER
	 */
	public static function getTheme($context=null) {

		if ($page_request = Page::getCurrentPageRequest()) {
			$theme = $page_request->getTheme();

			if (empty($theme)) {
				$theme_key = 'THEME';
				if (!is_null($context)) $theme_key .= '_' . strtoupper($context);
				return ConfigurationManager::get($theme_key);
			}
			
			return $theme;
		} else {
			return false;
		}
	}
	
	public static function setParameter($parameter_name, $parameter_value) {
		$_this = Page::getInstance();
		$_this->_parameters->set($parameter_name, $parameter_value);
	}
	
}

class SiteMap {
	/**
	 * Note:
	 * Regarding _parseChildren function, &$parent_obj used
	 * to pass "=null" as a default parameter, but this did 
	 * not work on early version of PHP5 or PHP4.   
	 *
	 * This has been fixed by passing "new SiteMapNode()" to
	 * this parameter  from the iniFromXml function.
	 */
	var $m_nodes=array();
	function __construct() {}
	function getNodes() { return $this->m_nodes; }
	function initFromXml($xml) {
		FrameworkManager::loadLibrary('xml.compile');
		$valid_xml = true;
		try {
			$xml_traversal_obj = CWI_XML_Compile::compile($xml);
		} catch (CWI_XML_CompileException $e) {
			ErrorManager::addError('Unable to load site XML file');
			$valid_xml = false;
		}
		if ($valid_xml && $xml_root = $xml_traversal_obj->getPathSingle('/siteMap')) {
			$this->m_nodes = $this->_parseChildren($xml_root, new SiteMapNode());
		}
	}
	
	function _parseChildren($xml_obj, &$parent_obj, $parent_path_array=null) {
	
		if (is_null($parent_obj)) {
			$parent_obj = new SiteMapNode();
			$parent_obj->children = array();
		}
		if (is_null($parent_path_array)) {
			$parent_path_array = array();
		}

		if ($sitemap_nodes = $xml_obj->getPath('siteMapNode')) {
			$nodes = array();
			foreach($sitemap_nodes as $sitemap_node) {
				$parallels[] = $parent_obj;
				
				$test_obj = new SiteMapNode();
				$test_obj->url = $sitemap_node->getParam('url');
				$test_obj->title = $sitemap_node->getParam('title');
				
				$pass_parent_obj = new SiteMapNode();
				$pass_parent_obj->url = $test_obj->url;
				$pass_parent_obj->title = $test_obj->title;

				// Linear array for child siblings
				$pass_parent_path_array = array_merge($parent_path_array, array($pass_parent_obj));
				
				$test_obj->children = array();				
				$test_obj->parent = $parent_obj;
				$test_obj->parent_path_array = $parent_path_array;

				$xyz = $this->_parseChildren($sitemap_node, $test_obj, $pass_parent_path_array);//$test_obj->parent_path_array);

				$nodes[] = $test_obj;
				if (isset($xyz->all_nodes)) {
					$nodes = array_merge($nodes, $xyz->all_nodes);
				}
			}

			$parent_obj->all_nodes = $nodes;	
		}
		return $parent_obj;
	}

	function findPath($path) {
		$dir_match = false;
		
		$path_parts = explode('/', $path);
		if (strpos($path_parts[count($path_parts)-1], '.') > 0) {
			array_pop($path_parts);
			$path_dir = implode('/', $path_parts) . '/';
		} else $path_dir = implode('/', $path_parts);
		
		$nodes = $this->getNodes();
		if (isset($nodes->all_nodes)) {
			foreach($nodes->all_nodes as $lookup) {
				if ($lookup->url == $path) { // Exact match
					return $lookup;
				} else if ($lookup->url == $path_dir) { // Directory (parent) match
					$dir_match = $lookup;
				}
			}
		}
		return $dir_match;
	}
	
	
}

class SiteMapNode { //extends Collection {
	var $url, $title;
	var $children = array();
	var $parent;
	var $parent_path_array = array();
	var $all_nodes = array();
	
	function getUrl() { return $this->url; }
	function getTitle() { return $this->title; }
	function getParent() { return $this->parent; }
	function getParentPath() { return $this->parent_path_array; }
}
class Link {
	var $_link, $_title;
	function __construct($link, $title) {
		$this->setLink($link);
		$this->setTitle($title);
	}
	function getTitle() { return $this->_title; }
	function getLink() { return $this->_link; }
	
	function setTitle($title) { $this->_title = $title; }
	function setLink($link) { $this->_link = $link; } 
	
}
// TODO: move to own file
class BreadCrumbElement extends Link {
	function __construct($title, $url=null) {
		$this->Link($url, $title);
	}	
}
// TODO: move to own file
class BreadCrumb {
	var $_trail = array();

	function __construct() {
	}
	/**
	 * $link_last_element = true/false = whether to link last element in trail
	 */
	function getTrail($link_last_element=false) {
		$trail = $this->_trail;
		if (!$link_last_element && count($trail)>0) {
			$trail[count($trail)-1]->setLink(null);
		}
		return $trail;
	}
	function addCrumb($title, $url=null) {
		$this->_trail[] = new BreadCrumbElement($title, $url);
	}
	function hasTrail() {
		return (count($this->_trail) > 0);
	}
}

?>