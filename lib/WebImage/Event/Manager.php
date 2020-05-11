<?php

namespace WebImage\Event;

class Manager implements ManagerInterface
{
	private $listeners = [];

	/**
	 * Listen for an event
	 * @param $event
	 * @param callable $handler With a function/method signature of $handler(Event $event)
	 * @param int $priority
	 * @return void
	 */
	public function listen($event, $handler, $priority = Manager::MEDIUM_PRIORITY)
	{
		$this->listeners[$event][$priority][] = $handler;
	}

	/**
	 * Trigger an event
	 * @param string|Event $event
	 * @param mixed $data
	 * @param mixed|null $sender
	 *
	 * @return mixed[] Responses from all listeners
	 */
	public function trigger($event, $data, $sender = null): array
	{
		$responses = [];
		$event = $this->buildEvent($event, $data, $sender);
		$listeners = $this->prioritizedListenersForEvent($event);

		foreach($listeners as $listener) {
			$responses[] = call_user_func($listener, $event);

			if ($event->isCancelled()) break;
		}

		return $responses;
	}

	/**
	 * Sort listeners by priority and merge into a single set of listeners
	 * @param Event $event
	 *
	 * @return array
	 */
	private function prioritizedListenersForEvent(Event $event): array
	{
		$type = $event->getType();
		$listeners = isset($this->listeners[$type]) ? $this->listeners[$type] : [];

		return count($listeners) == 0 ? [] : call_user_func_array('array_merge', $listeners);
	}

	/**
	 * Create an event object, if $event is not already an event instance
	 * @param string|Event $event
	 * @param mixed $data
	 * @param mixed $sender
	 *
	 * @return Event
	 */
	private function buildEvent($event, $data, $sender): Event
	{
		if (is_object($event)) {
			if (!($event instanceof Event)) {
				throw new \InvalidArgumentException(sprintf('%s was expecting an instance of %s', __METHOD__, Event::class));
			}
			if (null !== $data || null !== $sender) {
				throw new \InvalidArgumentException(sprintf('$data and $sender should not be specified when an %s instance is supplied', Event::class));
			}

			return $event;

		} else if (!is_string($event)) {
			throw new \InvalidArgumentException(sprintf('%s was expecting a string event', __METHOD__));
		}

		return new Event($event, $data, $sender);
	}
}