<?php

FrameworkManager::loadLibrary('search.ipagecontentsearch');
FrameworkManager::loadLibrary('search.pagecontentsearchresult');
FrameworkManager::loadLibrary('search.pagecontentsearchresults');

class ControlSearch implements CWI_SEARCH_IPageContentSearch {
	public function searchKeyword($keyword) {
		FrameworkManager::loadBaseLogic('control');
		$controls = ControlLogic::getControls();
		
		while ($control = $controls->getNext()) {
			include_once( PathManager::translate($control->file_src) );
			$class_name = $control->class_name;
			
			$temp_instance = new $class_name();
			echo $class_name . ': ';
			if (is_a($temp_instance, 'CWI_SEARCH_IPageContentSearch')) echo 'true';
			else echo 'false';
			$temp_instance = null;
			unset($temp_instance);
			echo '<br />';
		}
		
		$dao = new DataAccessObject();
		
		$results = $dao->selectQuery("SELECT * FROM content WHERE title LIKE '%" . $keyword . "%'");
		
		$search_results = new CWI_SEARCH_PageContentSearchResults();
		
		while ($result = $results->getNext()) {
			$search_result = new CWI_SEARCH_PageContentSearchResult(80, '/about/index.html', $result->title, strip_tags($result->description));
			$search_results->add($search_result);
		}
		
		return $search_results;
	}
}

class TestSearch implements CWI_SEARCH_IPageContentSearch {
	public function searchKeyword($keyword) {
		$dao = new DataAccessObject();
		
		$results = $dao->selectQuery("SELECT * FROM content WHERE title LIKE '%" . $keyword . "%'");

		$search_results = new CWI_SEARCH_PageContentSearchResults();
		
		while ($result = $results->getNext()) {
			$search_result = new CWI_SEARCH_PageContentSearchResult(60, '/ads/top-banner.html', $result->title, trim(strip_tags($result->description)) );
			$search_results->add($search_result);
		}
		
		return $search_results;
	}
}

class CWI_SEARCH_GlobalSearch {
	private $results;
	
	private function prioritySort($param1, $param2) {
		return ($param1->getScore() - $param2->getScore());
	}
	
	function searchKeyword($keyword) {
		$config = CM::getConfig();

		$results1 = ControlSearch::searchKeyword('about');
		
		$results = array_merge($results1->getAll());
		
		$search_results = new CWI_SEARCH_PageContentSearchResults();
		#$search_results->merge($results);
		#$search_results->merge($results2);
		#$final_results = new 
		usort($results, array($this, 'prioritySort'));
		$search_results->merge($results);
		return $search_results;
	}
}

?>