<?php

/**
 * Handles general events.  Whereas ObjectListener and ClassListener pertain to object, this will only listen to a specific $event_type (the passed objected must be null).
 *
 * Example triggers would be:
 *    CWI_EVENT_Manager::trigger(null, 'my_custom_event', $args);
 *    CWI_EVENT_Manager::triggerGeneral('my_custom_event', $args);
 *
 **/

FrameworkManager::loadLibrary('event.abstracteventlistener');
class CWI_EVENT_GeneralListener extends CWI_EVENT_AbstractEventListener {
	public function handlesEvent($obj, $event_type) {
		return (is_null($obj) && $this->getEventType() == $event_type);
	}
}

?>