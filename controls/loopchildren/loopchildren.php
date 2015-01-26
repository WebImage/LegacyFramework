<?php
/**
 * Loops all underlying children controls - should be used by inheriting controls, not directly
 *
 * Inheriting control should implement:
 * prepareContents() - used to setup local vars
 * getLoopCount() - returns total number of times to loop over children
 * getLoopIndexVar() - name of the context var to use for iterating through 
 * prepareContextForIndex(int $index)
 * 
 **/
class LoopChildrenControl extends WebControl {
	
	private $childrenAlreadyRendered = false;
	
	protected function init() {
		parent::init();
		$this->setInitParam('wrapOutput', false);
	}
	/**
	 * Gets the total number of times to loop over children
	 * @return int
	 **/
	protected function getLoopCount() { return 0; }
	/**
	 * @return string the name of the variable to use in setting the context's loop index variable
	 **/
	protected function getLoopIndexVar() { return 'loop_index'; }
	/**
	 * Sets up the context for a given ($index) loop cycle
	 * @param int $index
	 * @return void
	 **/
	protected function prepareContextForIndex($index) {}
	
	/**
	 * Will be used in the near future to allow inheriting classes to get feedback from rendered children
	 **/
	private function renderLoopChildren($index) {
		$tmp_content = '';
		$children = $this->getControls();
		#foreach($children as $child_control) {
		while ($control = $children->getNext()) {
			$this->getContext()->set($this->getLoopIndexVar(), $index);
			$tmp_content .= $child_control->render();
		}
		return $tmp_content;
	}
	
	function renderChildren() {
		if ($this->childrenAlreadyRendered) return;
		$this->childrenAlreadyRendered = true;
		#$children = $this->getControls();
		$tmp_content = '';
		if ($this->preRenderChildren()) {
			for ($i=0, $j=$this->getLoopCount(); $i < $j; $i++) {
				$this->prepareContextForIndex($i);
				$tmp_content .= $this->renderLoopChildren($i);
			}
		} else {
			// False
		}
		$this->setRenderedChildContent($tmp_content);
	}
}

?>