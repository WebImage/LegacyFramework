<?php
FrameworkManager::loadLibrary('assets.assetfiletype');
class CWI_MANAGER_AssetManager {
	#private $config;
	private $initialized=false;
	private $fileTypes;
	private $extensions;
	private $variations;
	
	public static function getInstance() {
		$instance = Singleton::getInstance('CWI_MANAGER_AssetManager');
		$instance->init($instance);
		return $instance;
	}
	private function init($instance) {
		
		if (!$instance->initialized) {
		
			$instance->extensions = new Dictionary();
			$instance->variations = new Dictionary();
			$instance->fileTypes = array();
			
			FrameworkManager::loadLibrary('xml.compile');
			$instance->initialized = true;
			if ($config_path = PathManager::translate('~/config/assets.xml')) {
				$valid_xml = true;
				try {
					$xml = CWI_XML_Compile::compile( file_get_contents($config_path) );
				} catch (CWI_XML_DatabaseException $e) {
					$valid_xml = false;
				} 
				
				if ($valid_xml) {
					/*
					config
						fileTypes
							fileType [name, classFile, className]
								extensions
									extension [fileExt]
					*/
					if ($xml_file_types = $xml->getPath('/config/fileTypes/fileType')) {
						$index = -1;
						foreach($xml_file_types as $xml_file_type) {
							$index += 1;
							$admin = ConfigurationManager::getValueFromString($xml_file_type->getParam('admin'));
							$asset_file_type = new CWI_ASSETS_AssetFileType($xml_file_type->getParam('name'), $xml_file_type->getParam('classFile'), $xml_file_type->getParam('className'), $admin);
							
							if ($xml_extensions = $xml_file_type->getPath('extensions/extension')) {
								foreach($xml_extensions as $xml_extension) {
									$xml_param_file_extension = $xml_extension->getParam('fileExt');
									if (!empty($xml_param_file_extension)) {
										$asset_file_type->addExtension($xml_param_file_extension);
										$instance->extensions->set($xml_param_file_extension, $index);
									}
								}
							}
							
							array_push($instance->fileTypes, $asset_file_type);
						}
					}
					
					FrameworkManager::loadLibrary('assets.variations.variation');
					FrameworkManager::loadLibrary('assets.variations.step');
					FrameworkManager::loadLibrary('assets.variations.stepparameter');
					
					if ($xml_variations = $xml->getPath('variations/variation')) {

						foreach($xml_variations as $xml_variation) {

							$key = $xml_variation->getParam('key');
							$auto = ($xml_variation->getParam('auto') != 'false');

							if ($xml_steps = $xml_variation->getPath('step')) {
								
								foreach($xml_steps as $xml_step) {
									
									if ($xml_parameters = $xml_step->getPath('parameter')) {
									}
									
								}
								
							}
							
						}
						
					}
					/*
							
							<variation key="am-thumbnail" auto="true">
			<!--
				Step Parameters:
				@param method (string, required) - The method to call on /libraries/files.php:ImageResource			
			-->
			<step method="scaleAndCrop">
				<!--
					Parameters: must be passed in order
					@param name (string, optional) - A name used for readability only within this XML file
					@param value (string, required) - The value to pass to the above method
				-->
				<parameter name="width" value="100" />
				<parameter name="height" value="100" />
			</step>
		</variation>
							*/
				}
			}
		}
	}
	
	public static function getAssetFileTypeByExtension($extension) {
		$_this = CWI_MANAGER_AssetManager::getInstance();
		$asset_type_index = $_this->extensions->get($extension);
		if (is_numeric($asset_type_index)) {
			if (isset($_this->fileTypes[$asset_type_index])) {
				return $_this->fileTypes[$asset_type_index];
			} else return false;
		} else return false;
	}
	
	public static function getVariations() {
		
		$_this = CWI_MANAGER_AssetManager::getInstance();
		return $_this->variations;
		
	}
	
}

?>