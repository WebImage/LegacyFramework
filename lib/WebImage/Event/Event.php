<?php

namespace WebImage\Event;

class Event {
	/** @var string A string identifier for the event */
	private $type;
	/** @var mixed Any data that should be provided as part of the event */
	private $data;
	/** @var ?object The sender that initiated the event */
	private $sender;
	/** @var bool */
	private $isCancelled = false;
	/**
	 * Event constructor.
	 *
	 * @param string $type
	 * @param mixed $data
	 * @param ?object $sender
	 */
	public function __construct(string $type, $data, ?object $sender = null)
	{
		$this->type = $type;
		$this->data = $data;
		$this->sender = $sender;
	}

	/**
	 * @return string
	 */
	public function getType(): string
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
	public function getSender(): ?object
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
