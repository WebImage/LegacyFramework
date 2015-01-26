<?php

FrameworkManager::loadLibrary('event.abstracteventlistener');
class CWI_EVENT_ClassListener extends CWI_EVENT_AbstractEventListener {
	/**
	 * isStrict - whether the class name has to be an exact match - as opposed to a simple is_a
	 */
	private $className, $isStrict = false;
	function __construct($class_name, $event_type, $handler) {
		$this->className = $class_name;
		parent::__construct($event_type, $handler);
	}
	public function getClassName() { return $this->className; }
	
	public function isStrict($true_false=null) {
		if (is_null($true_false)) { // Getter
			return $this->isStrict;
		} else if (is_bool($true_false)) $this->isStrict = $true_false;
		else throw new Exception('Invalid class listener is_strict.');
	}
	
	public function handlesEvent($obj, $event_type) {
		$class_match = ( ($this->isStrict() && $this->getClassName() == get_class($obj)) || (!$this->isStrict() && is_a($obj, $this->getClassName())) );
		return ($class_match && $this->getEventType() == $event_type);
	}

}

?>