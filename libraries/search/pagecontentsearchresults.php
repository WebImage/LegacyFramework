<?php

interface CWI_SEARCH_IPageContentSearchResults {
	public function getResults(); // Collection of IPageContentSearchResult
}
class CWI_SEARCH_PageContentSearchResults extends Collection implements CWI_SEARCH_IPageContentSearchResults {
	public function getResults() { return $this->getAll(); }
}

?>