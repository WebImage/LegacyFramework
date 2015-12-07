<?php

/**
 * 02/02/2010	(Robert Jones) Added HTML escaping to the outputted error messages
 * 01/26/2011	(Robert Jones) Added lastNumErrorsDisplayed to allow multiple instances of this control to be included on a single page without outputting the same errors over and over again
 */
class ErrorControl extends WebControl {
	
	/**
	 * keep track of the number of errors last displayed across all instances.  
	 * This will allow multiple instances of this control to be included on one page without fear of outputting the same errors over and over again
	 **/
	static private $lastNumErrorsDisplayed = 0;
	
	protected function init() {
		parent::init();
		$this->setInitParams(array(
			'class' => 'error',
			'headerTemplate' => '<p class="errordesc">The following error(s) occurred:</p><ul>',
			'footerTemplate' => '</ul>',
			'itemTemplate' => '<li class="errormsg">%s</li>'
		));
	}
	/**
	 * Override contentFinalized.  All controls have been had their regular content rendered, so now we can output any errors that have been rendered
	 **/
	public function contentFinalized() {
		
		$output = '';
		if (ErrorManager::anyDisplayErrors()) {
			$errors = ErrorManager::getDisplayErrors();
			
			if ($errors->getCount() > ErrorControl::$lastNumErrorsDisplayed) {
				$output .= $this->getHeaderTemplate();
				
				while ($error = $errors->getNext()) {
					
					// Only show errors that have not been shown before
					if ($errors->getCurrentIndex() >= (ErrorControl::$lastNumErrorsDisplayed-1)) {
						
						$error_string = $error;
						
						$error_links = preg_match_all('#(<a.*?>)(.*?)(</a>)#ims', $error, $matches);
						
						if (count($matches) > 0) { // Found links - only escape not link HTML
							$link_matches = array();
							
							// Take out link HTML so that they can be preserved and restored after all other characters have been escaped.
							for($i=0; $i < count($matches[0]); $i++) {
								$error_string = str_replace($matches[0][$i], '[[link'.$i.']]', $error_string);
								$properly_escaped_link = $matches[1][$i] . htmlentities($matches[2][$i]) . $matches[3][$i];
								$link_matches['link'.$i] = $properly_escaped_link;
							}
							// Escape the string without the links
							$error_string = htmlentities($error_string);
							
							// Add the links back in
							foreach($link_matches as $key=>$link) {
								$error_string = str_replace('[[' . $key . ']]', $link, $error_string);
							}
							
							$error = $error_string;
						} else { // No links - escape whole string
							$error_string = htmlentities($error);
						}
						$output .= sprintf($this->getItemTemplate(), $error_string);
					}
				}
				$output .= $this->getFooterTemplate();
				
				ErrorControl::$lastNumErrorsDisplayed = $errors->getCount();
			}
		}
		$this->setRenderedContent($output);
		
		return $this->getRenderedContent();
		
	}

	function getHeaderTemplate() { return $this->getParam('headerTemplate'); }
	function getFooterTemplate() { return $this->getParam('footerTemplate'); }
	function getItemTemplate() { return $this->getParam('itemTemplate'); }
}