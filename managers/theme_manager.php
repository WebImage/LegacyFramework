<?php

class CWI_PAGE_Header {
}
class CWI_PAGE_ThemeTemplate {
	private $templateFile = '';
	private $stylesheets;
	public function __construct($template_file, Collection $stylesheets) {
		$this->templateFile = $template_file;
		$this->stylesheets = $stylesheets;
	}
	public function getTemplateFile() { return $this->templateFile; }
	public function getStylesheets() { return $this->stylesheets; }
	
	public function addStylesheet($stylesheet) { $this->stylesheets->add($stylesheet); }
	public function setTemplateFile($template_file) { $this->templateFile = $template_file; }
}
class CWI_PAGE_Theme {
	private $config;
	private $templateBaseDir = '';
	private $templates;
	#private $stylesheets = array();
	private $wrapClassIds;
	private $adminContentStylesheets;
	private $adminContentScripts;
	
	public function __construct(CWI_XML_Traversal $config) {
		$this->setConfig($config);
		$this->wrapClassIds = new Dictionary();
		$this->templates = new Dictionary();
		$this->adminContentStylesheets = new Collection();
		$this->adminContentScripts = new Collection();
	}
	public function getConfig() { return $this->config; }
	public function getWrapClass($id) { return $this->wrapClassIds->get($id); }
	public function getTemplateBaseDir() { return $this->templateBaseDir; }
	public function getTemplates() { return $this->templates; }
	public function getTemplate($template_id) { return $this->templates->get($template_id); }
	public function getAdminContentStylesheets() { return $this->adminContentStylesheets; }
	public function getAdminContentScripts() { return $this->adminContentScripts; }
	public function getChartColors() { return $this->chartColors; }
	public function setConfig($config) { $this->config = $config; }
	public function setWrapClass($id, $class) { $this->wrapClassIds->set($id, $class); }
	public function setTemplateBaseDir($template_base_dir) { $this->templateBaseDir = $template_base_dir; }
	public function addTemplate($template_id, CWI_PAGE_ThemeTemplate $template) {
		if (preg_match('/^[a-zA-Z]+/', $template->getTemplateFile())) { // If it starts with a alpha character then this is a relative path... as opposed to starting with ~/templates/
			$base = $this->getTemplateBaseDir();
			if (!empty($base)) {
				$template->setTemplateFile($base . $template->getTemplateFile());
			}
		}
		$this->templates->set($template_id, $template);
	}
	public function addAdminContentStylesheet($stylesheet) {
		$this->adminContentStylesheets->add($stylesheet);
	}
	public function addAdminContentScript($script) {
		$this->adminContentScripts->add($script);
	}
	public function addChartColor($hex_color) {}
}
class CWI_MANAGER_ThemeManager {
	static private $managedThemes;
	static private $activated = false; // Tracks whether any functionality has been used
	public static function isActive($true_false=null) {
		if (is_null($true_false)) return CWI_MANAGER_ThemeManager::$activated; // Getter
		else CWI_MANAGER_ThemeManager::$activated = $true_false;
	}
	
	private static function getTheme($theme_id) {
		if (empty($theme_id)) return false;
		CWI_MANAGER_ThemeManager::isActive(true);
		$managed_themes = CWI_MANAGER_ThemeManager::$managedThemes;
		if (isset($managed_themes[$theme_id])) return $managed_themes[$theme_id];
		else {
			if ($file_path = PathManager::translate('~/config/themes/' . $theme_id . '/theme.xml')) {
				
				// Required library
				FrameworkManager::loadLibrary('xml.compile');
				
				try {
					$xml_config = CWI_XML_Compile::compile( file_get_contents($file_path) );
				} catch (CWI_XML_CompileException $e) {
					#FrameworkManager::debug(__class__ . ' getTheme(' . $theme_id . ') failed XML: ' . $e->getMessage());
					$d = new Dictionary(array('theme_id'=>$theme_id, 'xml_error'=>$e->getMessage()));
					Custodian::log('ThemeManager', __CLASS__ . '::getTheme(${theme_id}) failed XML: ${xml_error}', $d);
					return false;
				}
				$theme = new CWI_PAGE_Theme($xml_config);
				
				if ($xml_templates = $xml_config->getPathSingle('templates')) {
					if ($template_base_dir = $xml_templates->getParam('baseDir')) {
						$theme->setTemplateBaseDir($template_base_dir);
					}
					if ($xml_theme_templates = $xml_templates->getPath('template')) {
						foreach($xml_theme_templates as $xml_template) {
							
							
							$template_file = $xml_template->getParam('file');
							$stylesheets = new Collection();
							
							if ($xml_stylesheets = $xml_template->getPath('stylesheets/add')) {
								foreach($xml_stylesheets as $xml_stylesheet) {
									$stylesheets->add(new PageHeaderStylesheet(ConfigurationManager::getValueFromString($xml_stylesheet->getParam('file'))));
								}
							}
							
							$theme_template = new CWI_PAGE_ThemeTemplate($template_file, $stylesheets);
							$theme->addTemplate($xml_template->getParam('id'), $theme_template);
						}
					}
				}
				if ($wrap_classes = $xml_config->getPath('wrapClassIds/wrapClass')) {
					foreach($wrap_classes as $wrap_class) {
						$theme->setWrapClass($wrap_class->getParam('id'), $wrap_class->getParam('class'));
					}
				}
				
				if ($xml_stylesheets = $xml_config->getPath('adminContent/stylesheets/add')) {
					foreach($xml_stylesheets as $xml_stylesheet) {
						$theme->addAdminContentStylesheet( new PageHeaderStylesheet( ConfigurationManager::getValueFromString($xml_stylesheet->getParam('file')) ) );
					}
				}
				
			} else return false;
			CWI_MANAGER_ThemeManager::$managedThemes[$theme_id] = $theme;
			return $theme;
		}
	}
	
	public static function getConfig($theme_id) {
		if ($theme = CWI_MANAGER_ThemeManager::getTheme($theme_id)) {
			return $theme->getConfig();
		}
	}
	public static function getTemplate($theme_id, $template_id) {
		if ($theme = CWI_MANAGER_ThemeManager::getTheme($theme_id)) {
			
			if ($template = $theme->getTemplate($template_id)) {
				return $template;
			} else {
				#FrameworkManager::debug(__class__ . '->getTemplate(' . $theme_id . ', ' . $template_id . ') could not find template_id');
				$d = new Dictionary(array('theme_id'=>$theme_id, 'template_id'=>$template_id));
				Custodian::log('ThemeManager', __CLASS__.':getTemplate(${theme_id}, ${template_id}) could not find template_id', $d);
				return false;
			}
		} else {
			#FrameworkManager::debug(__class__ . '->getTemplate(' . $theme_id . ', ' . $template_id . ') could not find theme_id');
			$d = new Dictionary(array('theme_id'=>$theme_id, 'template_id'=>$template_id));
			Custodian::log('ThemeManager', __CLASS__.':getTemplate(${theme_id}, ${template_id}) could not find theme_id', $d);
			return false;
		}
	}
	
	public static function getWrapClassById($theme, $wrap_class_id) {
		if ($theme = CWI_MANAGER_ThemeManager::getTheme($theme)) {
			return $theme->getWrapClass($wrap_class_id);
		} else return false;
	}
	
	public static function wrapWithWrapClassId($theme, $content, $wrap_class_id, $tag_name='div') {
		if ($page_theme = CWI_MANAGER_ThemeManager::getTheme($theme)) {
			if ($wrap_class = $page_theme->getWrapClass($wrap_class_id)) {
				$wrap_classes = array_reverse(explode('>', $wrap_class));
			} else {
				$wrap_classes = array($wrap_class_id);
			}
			foreach($wrap_classes as $wrap_class) {
				$content = '<' . $tag_name . ' class="' . $wrap_class . '">' . $content . '</' . $tag_name . '>';
			}
				
			return $content;
		} else return $content;
	}
	
	public static function getAdminContentStylesheets($theme) {
		if ($theme = CWI_MANAGER_ThemeManager::getTheme($theme)) {
			return $theme->getAdminContentStylesheets();
		} else return false;
	}
	
	public static function getAdminContentScripts($theme) {
		if ($theme = CWI_MANAGER_ThemeManager::getTheme($theme)) {
			return $theme->getAdminContentScripts();
		} else return false;
	}
	
	public static function getChartColors($theme) {
		if ($theme = CWI_MANAGER_ThemeManager::getTheme($theme)) {
			return $theme->getChartColors();
		} else return false;
	}
}

?>