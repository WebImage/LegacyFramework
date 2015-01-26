<?php

class PageContentSearchResult {
	protected $score;
	protected $pageLink;
	protected $adminEditLink;

	/**
	 * @returns double A number from 0 thru 100 to indicate how close to the match the serarch was
	 */
	public function getScore() { return $this->score; }
	/**
	 * @returns 
	 */
	public function getPageLink() { return $this->pageLink; }
	#public function getAdminEditLink();
}

?>