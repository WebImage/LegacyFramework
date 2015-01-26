<?php

interface CWI_EVENT_IEventable {
	public function addEvent($event_type, $handler);
	public function removeEvent($event_type);
	public function trigger($even_type);
}

?>