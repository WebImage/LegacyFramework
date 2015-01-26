<?php

/**
 * Interface that describes how a searchable document parser should be structure
 **/
interface CWI_SEARCH_IDocParser {
	public function getTitle();
	public function getRawDocument();
	public function getWords($remove_stop_words=true);
	public function isStopWord($word);
	public function getStopWords();
}

?>