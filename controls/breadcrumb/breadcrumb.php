<?php


class BreadCrumbControl extends WebControl {

	protected $trail = array();

	protected function init() {
		parent::init();
		$this->setInitParams(array(
			'headerTemplate' => '',
			'betweenTemplate' => '',
			'itemTemplate' => '%s',
			'footerTemplate' => ''
		));
	}
	public function prepareContent() {
		
		$elements = array();
		foreach($this->trail as $element) {
			$text = $element->getTitle();
			$link = $element->getLink();
			
			$text = '<span class="breadcrumb-element">' . $text . '</span>';
			
			if (!empty($link)) $text = '<a href="' . substr(ConfigurationManager::get('DIR_WS_HOME'), 0, -1) . $element->getLink() . '" class="breadcrumb-link">' . $text . '</a>';
			
			$elements[] = sprintf($this->getItemTemplate(), $text);
		}

		$this->setRenderedContent($this->getHeaderTemplate() . implode($this->getBetweenTemplate(), $elements) . $this->getFooterTemplate());
	}

	public function addCrumb($title, $link=null) {
		$this->trail[] = new BreadCrumbElement($title, $link);
	}

	public function getTrail() {
		return $this->trail;
	}
	
	public function getHeaderTemplate() { return $this->getParam('headerTemplate'); }
	public function getBetweenTemplate() { return $this->getParam('betweenTemplate'); }
	public function getFooterTemplate() { return $this->getParam('footerTemplate'); }
	public function getItemTemplate() { return $this->getParam('itemTemplate'); }

	public function setHeaderTemplate($template) { $this->setParam('headerTemplate', $template); }
	public function setBetweenTemplate($template) { $this->setParam('betweenTemplate', $template); }
	public function setFooterTemplate($template) { $this->setParam('footerTemplate', $template); }
	public function setItemTemplate($template) { $this->setParam('itemTemplate', $template); }
}