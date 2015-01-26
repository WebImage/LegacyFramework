<?php

FrameworkManager::loadDAO('servicehandler');

class ServiceHandlerLogic {
	
	public static function getServiceHandlersByType($type) {
		
		$dao = new ServiceHandlerDAO();
		return $dao->getServiceHandlersByType($type);
		
	}
	
	/**
	 * Get a ServiceHandlerStruct object
	 **/
	public static function getServiceHandler($type, $class_name) {
		
		$dao = new ServiceHandlerDAO();
		return $dao->getServiceHandler($type, $class_name);
		
	}
	
	private static function createServiceHandlerStruct($type, $class_name, $class_file, $sortorder, $config, $plugin) {
		
		FrameworkManager::loadStruct('servicehandler');
		
		if (!is_numeric($sortorder)) $sortorder = 1;
		
		if (empty($config)) {
			$config_dictionary = new ConfigDictionary();
			$config_dictionary->toString();
		}
		
		$struct = new ServiceHandlerStruct();
		$struct->class_file	= $class_file;
		$struct->class_name	= $class_name;
		$struct->config		= $config;
		$struct->plugin		= $plugin;
		$struct->sortorder	= $sortorder;
		$struct->type 		= $type;
		
		return $struct;
		
	}
	
	/**
	 * Creates a service_handler entry OR returns an existing one
	 * @return ServiceHandlerStruct
	 **/
	public static function registerServiceHandler($type, $class_name, $class_file, $sortorder=1, $config=null, $plugin) {
		
		$struct = ServiceHandlerLogic::createServiceHandlerStruct($type, $class_name, $class_file, $sortorder, $config, $plugin);
		
		if ($service_handler_struct = ServiceHandlerLogic::getServiceHandler($type, $class_name)) {
			
			return $service_handler_struct; //ServiceHandlerLogic::save($struct); <!-- Don't save here because we do not want to inadvertantly overwrite customized config settings.  Additionally, some code may call this multiple times (on different requests) just to make sure that he appropriate types are registered
			
		} else {
			
			return ServiceHandlerLogic::save($struct, true);
			
		}
	}
	
	/**
	 * Updates an existing service_handler entry OR returns an existing one
	 * @return ServiceHandlerStruct
	 **/
	public static function updateServiceHandler($type, $class_name, $class_file, $sortorder=1, $config=null, $plugin) {
		
		if ($service_handler = ServiceHandlerLogic::getServiceHandler($type, $class_name)) {
			
			$struct = ServiceHandlerLogic::createServiceHandlerStruct($type, $class_name, $class_file, $sortorder, $config, $plugin);
			
			return ServiceHandlerLogic::save($struct);
			
		}
		
	}
	/**
	 * Registers or updates a service_handler entry 
	 * @return ServiceHandlerStruct
	 **/
	public static function registerOrUpdateServiceHandler($type, $class_name, $class_file, $sortorder=1, $config=null, $plugin) {
		
		$struct = ServiceHandlerLogic::createServiceHandlerStruct($type, $class_name, $class_file, $sortorder, $config, $plugin);
		
		if ($service_handler_struct = ServiceHandlerLogic::getServiceHandler($type, $class_name)) {
			
			return ServiceHandlerLogic::save($struct);
			
		} else {
			
			return ServiceHandlerLogic::save($struct, true);
		}
	}
	
	public static function save($service_handler_struct, $force_insert=false) {
		
		$dao = new ServiceHandlerDAO();
		$dao->setForceInsert($force_insert); 
		return $dao->save($service_handler_struct);
		
	}
	
}
?>