<?php

class CWI_SEARCH_PageContentSearchResult {
	protected $score;
	protected $link;
	protected $title;
	protected $description;
	protected $adminEditLink;

	function __construct($score, $link, $title, $description=null, $admin_edit_link=null) {
		$this->score = $score;
		$this->link = $link;
		$this->description = $description;
		$this->title = $title;
	}
	/**
	 * @returns double A number from 0 thru 100 to indicate how close to the match the serarch was
	 */
	public function getScore() { return $this->score; }
	/**
	 * @returns 
	 */
	public function getTitle() { return $this->title; }
	public function getLink() { return $this->link; }
	public function getAbstract() { return $this->description; }
	#public function getAdminEditLink();
}

?>