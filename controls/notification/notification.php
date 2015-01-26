<?php
/**
 * 02/02/2010	(Robert Jones) Added HTML escaping to the outputted notification messages
 */
class NotificationControl extends WebControl {
	
	protected function init() {
		parent::init();
		$this->setInitParams(array(
			'headerTemplate' => '<p class="notificationdesc">Notification(s):</p><ul>',
			'footerTemplate' => '</ul>',
			'itemTemplate' => '<li class="notificationmsg">%s</li>',
			'class' => 'notification'
		));
	}
	function prepareContent() {
		$output = '';
		if (NotificationManager::anyNotifications()) {
			$messages = NotificationManager::getMessages();
			$output .= $this->getHeaderTemplate();
			while ($message = $messages->getNext()) {
				$output .= sprintf($this->getItemTemplate(), htmlentities($message));
			}
			$output .= $this->getFooterTemplate();
		}
		$this->setRenderedContent($output);
	}
	
	function getHeaderTemplate() { return $this->getParam('headerTemplate'); }
	function getFooterTemplate() { return $this->getParam('footerTemplate'); }
	function getItemTemplate() { return $this->getParam('itemTemplate'); }
}

?>