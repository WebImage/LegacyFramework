<?php

interface CWI_EVENT_IEventListener {
	public function getEventType();
	public function getHandler();
	public function handlesEvent($obj, $event_type);
}


?>