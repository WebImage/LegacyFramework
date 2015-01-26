<?php

class CWI_EVENT_Event {
	private $sender, $eventType, $cancel = false;
	function __construct($sender, $event_type) {
		$this->sender = $sender;
		$this->eventType = $event_type;
	}
	public function getSender() { return $this->sender; }
	public function getEventType() { return $this->eventType; }
	public function cancel() { $this->cancel = true; }
	public function isCancelled() { return $this->cancel; }
}

?>