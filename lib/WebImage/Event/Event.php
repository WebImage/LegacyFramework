<?php

namespace WebImage\Event;

class Event {
	/** @var string A string identifier for the event */
	private $type;
	/** @var mixed Any data that should be provided as part of the event */
	private $data;
	/** @var mixed The sender that initiated the event */
	private $sender;
	/** @var bool */
	private $isCancelled = false;
	/**
	 * Event constructor.
	 *
	 * @param string $type
	 * @param mixed $sender
	 */
	public function __construct($type, $data, $sender)
	{
		$this->type = $type;
		$this->data = $data;
		$this->sender = $sender;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @return mixed
	 */
	public function getSender()
	{
		return $this->sender;
	}

	public function cancel()
	{
		$this->isCancelled = true;
	}

	public function isCancelled()
	{
		return $this->isCancelled;
	}
}