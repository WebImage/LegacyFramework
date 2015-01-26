<?php

FrameworkManager::loadDAO('control');
class ControlLogic {
	public static function getControlById($control_id) {
		$control_dao = new ControlDAO();
		return $control_dao->load($control_id);
	}
	
	public static function getControlByClassName($class_name) {
		$control_dao = new ControlDAO();
		return $control_dao->getControlByClassName($class_name);
	}
	
	public static function getAllControls() {
		$control_dao = new ControlDAO();
		return $control_dao->getAllControls();
	}
	public static function save($control_struct) {
		$control_dao = new ControlDAO();
		return $control_dao->save($control_struct);
	}
	public static function getControls() {
		$control_dao = new ControlDAO();
		return $control_dao->getAllControls();
	}
	
	public static function getCompiledControl($control_file) {
		// Load required libraries
		FrameworkManager::loadLibrary('xml.compile');
		
		// Get real path to file
		$control_file = PathManager::translate($control_file);
		
		// Read control file contents
		$control_contents = '';
		if ($fp = fopen(PathManager::translate($control_file), 'r')) {
			while ($read = fread($fp, 1024)) {
				$control_contents .= $read;
			}
			fclose($fp);
		}
		
		// Compile Control
		$control_object = CompileControl::compile($control_contents);
		return $control_object;
	}
	// Loads, compiles, and returns the text of a control file (ending in .html).  Automatically checks if a .html.php file exists for processing
	public static function getCompiledControlContents($control_file) {
		$control_object = ControlLogic::getCompiledControl($control_file);
		
		$output = '';
		ob_start();
		eval($control_object->init_code);
		
		if ($control_file_code_src = PathManager::translate($control_file . '.php')) {
			include($control_file_code_src);
		}
		
		eval($control_object->attach_init_code);
		eval($control_object->render_code);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	/**
	 * Find controls within all Framework directories
	 **/
	public static function discoverControls() {
		
		FrameworkManager::loadLibrary('xml.compile');
		FrameworkManager::loadLibrary('class.scanner.classscanner');
		
		// Get all framework paths (base + site, etc.)
		$paths = PathManager::getPaths();
		
		$base_dir = ConfigurationManager::get('DIR_FS_FRAMEWORK_BASE');
		$base_dir_len = strlen($base_dir);
		
		$site_dir = ConfigurationManager::get('DIR_FS_FRAMEWORK_APP');
		$site_dir_len = strlen($site_dir);

		// Keep track of controls that are discovered within the control paths
		$discover_controls = new Collection();

		foreach($paths as $path) {
			
			$controls_path = $path . 'controls/';

			if (file_exists($controls_path) && ($dh = opendir($controls_path))) {
			
				while ($file = readdir($dh)) {
					
					// Only scan directories
					if (filetype($controls_path . $file) == 'dir' && !in_array($file, array('.', '..'))) {
						
						// Control directory
						$control_dir = $controls_path . $file . '/';
						// Path to control class file
						$control_file = $control_dir . $file .'.php';
						// Path to config file
						$config_file = $control_dir . 'config.xml';
						
						if (file_exists($control_file) && file_exists($config_file)) {
							
							// Assume the best...
							$valid = true;
							
							try {
								
								$xml_config = CWI_XML_Compile::compile(file_get_contents($config_file));
								
							} catch (Exception $e) {
								
								ErrorManager::addError('There was an error loading the configuration for ' . $file . '.  Please contact support');
								$valid = false;
								
							}
							
							if ($valid) {
								
								$friendly_name = $xml_config->getParam('friendlyName');
								
								// Only consider controls that have an assigned friendly name
								if (!empty($friendly_name)) {
									
									$s = new CWI_CLASS_SCANNER_ClassScanner();
									$s->scanFile($control_file);
									$class_fields = $s->getClasses()->getAll();
									
									while ($class_field = $class_fields->getNext()) {
										
										$class_name = $class_field->getKey();
										$class_key = strtolower($class_name);
										$class_key = (substr($class_key, -7)=='control') ? substr($class_key, 0, -7) : $class_key;
										
										// Check if class matches directory name (which means this is the primary control)
										if ($file == $class_key) {
											
											// Check if this is a base control and shorten file path accordingly
											if (substr($control_file, 0, $base_dir_len) == $base_dir) $control_file = '~/' . substr($control_file, $base_dir_len);
											// Check if this is a site control and shorten file path accordingly
											else if (substr($control_file, 0, $site_dir_len) == $site_dir) $control_file = '~/' . substr($control_file, $site_dir_len);
											
											#var $class_name, $created, $created_by, $enable, $file_src, $id, $label, $updated, $updated_by;
											$obj = new ControlStruct();
											$obj->file_src = $control_file;
											$obj->label = $friendly_name;
											$obj->enabled = 1;
											$obj->class_name = $class_name;
											$discover_controls->add($obj);								
											
										}
									}
									
								}
								
							}
							
						}
						
					}
					
				}
				
				closedir($dh);
			}
		}
		return $discover_controls;
	}
	
	/**
	 * Build a list of controls and add any auto discovered controls from Framework directories
	 **/
	public static function autoAddDiscoveredControls() {
		$discovered_controls = ControlLogic::discoverControls();
		
		$control_dao = new ControlDAO();
		$rs_controls = $control_dao->getAllControls();

		$any_new = false;
		while ($control = $discovered_controls->getNext()) {

			$installed = false;
			// Check whether the discovered control is already installed
			while ($existing_control = $rs_controls->getNext()) {
				
				if ($existing_control->class_name == $control->class_name) {
					/*
					// Update label
					if ($existing_control->label != $control->label) {
						$existing_control->label = $control->label;
						ControlLogic::save($existing_control);
					}
					*/
					$installed = true;
					$rs_controls->resetIndex();
					break;
				}
				
			}
			
			if (!$installed) {
				$any_new = true;
				ControlLogic::save($control);
			}
			
		}
		
		// Refresh the control list, if necessary
		if ($any_new) {
			$control_dao->setCacheResults(false);
			$rs_controls = $control_dao->getAllControls();
		}
		
		return $rs_controls;
	}

}

?>