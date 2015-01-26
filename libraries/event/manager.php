<?php

FrameworkManager::loadLibrary('event.event');

class CWI_EVENT_Manager {
	
	var $events = array();
	var $generalEvents = array();
	
	const PRIORITY_EXTREMELY_HIGH	= 1000; // Higher numbers mean higher priority
	const PRIORITY_HIGH		= 800;
	const PRIORITY_MEDIUM_HIGH	= 600;
	const PRIORITY_MEDIUM		= 400;
	const PRIORITY_MEDIUM_LOW	= 300;
	const PRIORITY_LOW		= 100;
	const PRIORITY_EXTREMELY_LOW	= 10; // Default
	
	public static function getInstance() {
		return Singleton::getInstance('CWI_EVENT_Manager');
	}
	
	public static function removeEvent($obj, $event_type) {
		$_this = CWI_EVENT_Manager::getInstance();
		$events = array();
		foreach($_this->events as $event) {
			$keep = true;
			if ($event->handlesEvent($obj, $event_type)) $keep = false;
			if ($keep) array_push($events, $event);
		}
		$_this->events = $events;
	}
	public static function removeListener($obj, $remove_listener) {
		$_this = CWI_EVENT_Manager::getInstance();
		$events = array();
				
		foreach($_this->events as $events) {
			
			foreach($events as $listener) {
				$keep = true;
				if ($listener === $remove_listener) {
					$keep = false;
				}
				if ($keep) array_push($events, $listener);
			}
		}
		$_this->events = $events;
	}
	public static function listenFor($obj, $event_type, $handler, $priority=self::PRIORITY_EXTREMELY_LOW) {
		$_this = CWI_EVENT_Manager::getInstance();
		if (!is_numeric($priority)) $priority = self::PRIORITY_EXTREMELY_LOW;
		
		if (is_object($obj)) {
			FrameworkManager::loadLibrary('event.objectlistener');
			$listener = new CWI_EVENT_ObjectListener($obj, $event_type, $handler);
		} else if (is_string($obj)) {
			FrameworkManager::loadLibrary('event.classlistener');
			$class_name = $obj;
			$listener = new CWI_EVENT_ClassListener($class_name, $event_type, $handler);
		} else if (is_null($obj)) {
			FrameworkManager::loadLibrary('event.generallistener');
			$class_name = $obj;
			$listener = new CWI_EVENT_GeneralListener($event_type, $handler);
		} else {
			throw new Exception('Unknown event object type.');
		}
		
		if (!isset($_this->events[$priority])) $_this->events[$priority] = array();
		array_push($_this->events[$priority], $listener);
		
		return $listener;
	}
	/**
	 * Not sure why this method was named this way, so it was renamed to addEventListener and referenced above for backwards compatability
	 **/
	public static function addEvent($obj, $event_type, $handler, $priority=self::PRIORITY_EXTREMELY_LOW) {
		return self::listenFor($obj, $event_type, $handler, $priority);
	}
	public static function trigger($obj, $event_type) {
		
		$_this = CWI_EVENT_Manager::getInstance();
		$args = func_get_args();
		$args = array_slice($args, 2);
		$event = new CWI_EVENT_Event($obj, $event_type);
		array_unshift($args, $event);
		$all_true = true;
		krsort($_this->events); // Make sure events are ordered by priority
		$process = true;
		foreach($_this->events as $events) {
			foreach($events as $listener) {
				if ($listener->handlesEvent($obj, $event_type)) {
					$response = call_user_func_array($listener->getHandler(), $args);
					if ($response === false) $all_true = false;
					if ($event->isCancelled()) {
						$process = false;
						break;
					}
				}
			}
			if (!$process) break;
		}
		return $all_true;
	}
	
	public static function listenForGeneral($event_type, $handler, $priority=self::PRIORITY_EXTREMELY_LOW) {
		$_this = CWI_EVENT_Manager::getInstance();
		$args = func_get_args();
		array_unshift($args, null);
		call_user_func_array(array($_this, 'listenFor'), $args);
	}
	
	/** 
	 * Conventience method for trigger(null, $event_type, ...)
	 **/
	public static function triggerGeneral($event_type) {
		$_this = CWI_EVENT_Manager::getInstance();
		$args = func_get_args();
		array_unshift($args, null);
		call_user_func_array(array($_this, 'trigger'), $args);
	}
}

?>