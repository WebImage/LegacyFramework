<?php

FrameworkManager::loadLibrary('event.abstracteventlistener');
class CWI_EVENT_ObjectListener extends CWI_EVENT_AbstractEventListener {
	private $obj;
	function __construct($obj, $event_type, $handler) {
		$this->obj = $obj;
		parent::__construct($event_type, $handler);
	}
	public function getObject() { return $this->obj; }
	public function handlesEvent($obj, $event_type) {
		return ($this->getObject() === $obj && $this->getEventType() == $event_type);
	}
}

?>