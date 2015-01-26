<?php

FrameworkManager::loadLibrary('event.ieventlistener');
abstract class CWI_EVENT_AbstractEventListener implements CWI_EVENT_IEventListener {
	private $eventType, $handler;
	function __construct($event_type, $handler) {
		$this->eventType = $event_type;
		$this->handler = $handler;
	}
	public function getEventType() { return $this->eventType; }
	public function getHandler() { return $this->handler; }
	public function handlesEvent($obj, $event_type) { return false; }
}

?>