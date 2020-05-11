<?php

namespace WebImage\Event;

interface ManagerInterface {
	const HIGH_PRIORITY = 1000;
	const MEDIUM_PRIORITY = 500;
	const LOW_PRIORITY = 0;
	/**
	 * @param string
	 * @param callable $handler
	 *
	 * @return mixed
	 */
	public function listen($event, $handler, $priority=self::MEDIUM_PRIORITY);

	/**
	 * @param string|Event $event
	 * @param mixed $data
	 * @param mixed|null $sender
	 *
	 * @return mixed[] Responses from all listeners
	 */
	public function trigger($event, $data, $sender=null);
}