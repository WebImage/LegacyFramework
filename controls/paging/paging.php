<?php
/**
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 * 08/14/2012	(Robert Jones) Added $m_renderNoContent = false to turn off rendering when the paging control is not needed
 */

FrameworkManager::loadLibrary('string.urlmanipulator');

class PagingControl extends WebControl {
	var $m_processInternal = false; // Disable internal processing so that we can process template xml
	
	// Template
	var $m_layoutTemplate = '<div class="paging-number-set">Results Pages: <Data field="prev_page_nav" /><Data field="page_numbers" /><Data field="next_page_nav" /></div>';
	var $m_pageNumTemplate = '<span class="paging-item"><a href="<Data field="page_num_link" />"><Data field="page_num" /></a></span>';
	var $m_pageNumBetweenTemplate = '&nbsp;&nbsp;';
	var $m_currentPageNumTemplate = '<span class="paging-item-selected"><strong><Data field="page_num" /></strong></span>';
	var $m_prevPageLinkTemplate = '<span class="paging-prev-page-link"><a href="<Data field="prev_page_link" />">Prev</a></span>&nbsp;&nbsp;';
	var $m_nextPageLinkTemplate = '&nbsp;&nbsp;<span class="paging-next-page-link"><a href="<Data field="next_page_link" />">Next</a></span>';

	// Control Specific Variables
	var $m_linkBase = null;
	var $m_urlPageVar = 'p';
	
	var $m_maxPagePositions = 10;
	
	var $m_requireResults = 'YES'; // Whether to display layout template if there are not any results
	
	/**
	 * OPTIONS:
	 * FOLLOW - the page results scroll one by one as the current page changes.
	 *	For example, if the current page is 5....
	 *		1 2 3 4 [5] 6 7 8 9 10
	 *	.. and 10 is clicked, the entire set of pages shifts
	 *		5 6 7 8 9 [10] 11 12 13 14
	 * GROUP - the page results only change when the current page goes beyond the number of pages in maxPagePositions
	 *	For example, if the current page is 5...
	 * 		1 2 3 4 [5] 6 7 8 9 10
	 * 	.. and 10 is clicked, the same set of page will be displayed ..
	 *		1 2 3 4 5 6 7 8 9 [10]
	 *	.. however, once "Next Page" is clicked, the entire group of pages changes ..
	 *		[11] 12 13 14 15 16 17 18 19 20
	 * [NOTE] If the number of pages is less than maxPagePositions then scrollType will not matter
	 */
	var $m_scrollType="FOLLOW";
	
	/**
	 * $weightType 
	 * Selects where the current page will be within the results, the choices are LEFT, CENTER,  RIGHT, and SCROLL which effect the result as follows
	 * $scrollType MUST be "FOLLOW"
	 *
	 * LEFT (assuming selected page is 11)
	 * [11] 12 13 14 15 16 17 18 19 20
	 *
	 * CENTER (assuming selected page is 11)
	 * 5 6 7 8 9 [10] 11 12 13 14
	 *
	 * RIGHT (assuming selectd page is 11)
	 * 2 3 4 5 6 7 8 9 10 [11]
	 *
	 * [NOTE] If the number of pages is less than maxPagePositions then weightType will not matter
	 */
	var $m_weightType = 'CENTER';

	#var $m_forControlId; // Use specified page control for result set

	public function init() {
		$this->setInitParam('renderNoContent', true);
		parent::init();
	}
	function getMaxPagePositions() { return $this->m_maxPagePositions; }
	function getForControlId() { return $this->getParam('forControlId'); }
	function getScrollType() { return strtoupper($this->m_scrollType); }
	function getWeightType() { return strtoupper($this->m_weightType); }
	function getLinkBase() { return $this->m_linkBase; }
	function getRequireResults() { return strtoupper($this->m_requireResults); }
	
	function getUrlPageVar() { return $this->m_urlPageVar; }

	function getLayoutTemplate() { return $this->m_layoutTemplate; }
	function getPageNumTemplate() { return $this->m_pageNumTemplate; }
	function getCurrentPageNumTemplate() { return $this->m_currentPageNumTemplate; }
	function getPageNumBetweenTemplate() { return $this->m_pageNumBetweenTemplate; }
	function getPrevPageLinkTemplate() { return $this->m_prevPageLinkTemplate; }
	function getNextPageLinkTemplate() { return $this->m_nextPageLinkTemplate; }
	
	function setMaxPagePositions($max_page_positions) { $this->m_maxPagePositions = $max_page_positions; }
	function setForControlId($control_id) { $this->setParam('forControlId', $control_id); }
	function setScrollType($scroll_type) { $this->m_scrollType = strtoupper($scroll_type); }
	function setWeightType($weight_type) { $this->m_weightType = strtoupper($weight_type); }
	function setLinkBase($link_base) { $this->m_linkBase = $link_base; }
	function setRequireResults($require_results) { $this->m_requireResults = $require_results; }

	function setLayoutTemplate($layout_template) { $this->m_layoutTemplate = $layout_template; }
	function setPageNumTemplate($page_num_template) { $this->m_pageNumTemplate = $page_num_template; }
	function setCurrentPageNumTemplate($current_page_num_template) { $this->m_currentPageNumTemplate = $current_page_num_template; }
	function setPageNumBetweenTemplate($between_template) { $this->m_pageNumBetweenTemplate = $between_template; }
	function setPrevPageLinkTemplate($link_template) { $this->m_prevPageLinkTemplate = $link_template; }
	function setNextPageLinkTemplate($link_template) { $this->m_nextPageLinkTemplate = $link_template; }
	
	function prepareContent() {
		
		if ($page_range = $this->_preparePageRange()) {
			
			if ( $this->_prepareTemplates() ) {
				
				$page_num_template		= ControlTemplateHelper::parseTemplate( $this->getPageNumTemplate() );
				$current_page_num_template	= ControlTemplateHelper::parseTemplate( $this->getCurrentPageNumTemplate() );
				
				// Prepare Page Number Links
				$page_num_output = '';

				for ($i=$page_range->start_page; $i <= $page_range->end_page; $i++) {
					$link = CWI_STRING_UrlManipulator::appendUrl($this->getLinkBase(), $this->getUrlPageVar(), $i);
					
					if ($i == $page_range->current_page) {
						$current_page_num_template->set('page_num_link', $link);
						$current_page_num_template->set('page_num', $i);
						$page_num_output .= $current_page_num_template->render();
					} else {
						$page_num_template->set('page_num_link', $link);
						$page_num_template->set('page_num', $i);
						$page_num_output .= $page_num_template->render();
					}
					if ($i < $page_range->end_page) {
						$page_num_output .= $this->getPageNumBetweenTemplate();
					}
				}
				
				// Prepare Previous Page Links
				if ($page_range->current_page > 1) {
					$prev_page_link = CWI_STRING_UrlManipulator::appendUrl($this->getLinkBase(), $this->getUrlPageVar(), ($page_range->current_page-1) );
					$prev_page_nav = '';

					if ($prev_page_template = ControlTemplateHelper::parseTemplate($this->getPrevPageLinkTemplate())) {
						$prev_page_template->set('prev_page_link', $prev_page_link);
						$prev_page_nav = $prev_page_template->render();
					}
				} else {
					$prev_page_link = '';
					$prev_page_nav = '';
									}
				
				// Prepare Next Page Links
				if ($page_range->current_page < $page_range->total_pages) {
					$next_page_link = CWI_STRING_UrlManipulator::appendUrl($this->getLinkBase(), $this->getUrlPageVar(), ($page_range->current_page+1) );
					$next_page_nav = '';
					
					if ($next_page_template = ControlTemplateHelper::parseTemplate($this->getNextPageLinkTemplate())) {
						$next_page_template->set('next_page_link', $next_page_link);
						$next_page_nav = $next_page_template->render();
					}

				} else {
					$next_page_link = '';
					$next_page_nav = '';
				}
				
				
				// Template vars that should be made available to all templates within this Paging control
				$global_tpl_vars = array(
					'prev_page_link'	=> $prev_page_link,
					'prev_page_nav'		=> $prev_page_nav,
					'next_page_link'	=> $prev_page_link,
					'next_page_nav'		=> $next_page_nav,
					'page_numbers'		=> $page_num_output,
					'current_page'		=> $page_range->current_page,
					'total_pages'		=> $page_range->total_pages,
					'total_results'		=> $page_range->total_results
				);
				
				// Only display 
				if (!empty($page_num_output) || $this->getRequireResults() == 'NO') {
					$layout_template = ControlTemplateHelper::parseTemplate( $this->getLayoutTemplate() );
					$layout_template->set($global_tpl_vars);

					$this->setRenderedContent($layout_template->render());
				}
			}
		}
	}
	
	function _prepareTemplates() {
/*
		<Template>
			<LayoutTemplate><![CDATA[
				<Data field="page_numbers" />
			]]></LayoutTemplate>
			<PageNumTemplate><![CDATA[
				<span class="paging-item-selected"><strong>[<Data field="page_num" />]</strong></span>
			]]></PageTemplate>
			<CurrentPageNumTemplate><![CDATA[
				<span class="paging-item"><a href="<Data field="page_num_link" />"><Data field="page_num" /></a></span>
			]]></CurrentPageNumTemplate>
		</Template>
*/
		$xml = CWI_XML_Compile::compile($this->getInnerCode());

		if ($template = $xml->getPathSingle('/Template')) {
		
			if ($layout_template = $template->getData('LayoutTemplate')) {
				$this->setLayoutTemplate($layout_template);
			}
			
			if ($page_num_template = $template->getData('PageNumTemplate')) {
				$this->setPageNumTemplate($page_num_template);
			}
			
			if ($current_page_num_template = $template->getData('CurrentPageNumTemplate')) {
				$this->setCurrentPageNumTemplate($current_page_num_template);
			}
			
			if ($prev_page_link_template = $template->getData('PrevPageLinkTemplate')) {
				$this->setPrevPageLinkTemplate($prev_page_link_template);
			}
			
			if ($next_page_link_template = $template->getData('NextPageLinkTemplate')) {
				$this->setNextPageLinkTemplate($next_page_link_template);
			}
			
		}
		return true;
	}

	private function _preparePageRange() {
		
		if ($test = Page::getControlById($this->getForControlId())) {
			
			$data = $test->getData();
			
			if (is_a($data, 'ResultSet')) {
				
				$max_page_positions	= $this->getMaxPagePositions();
				$scroll_type		= $this->getScrollType();
				$weight_type		= $this->getWeightType();
				
				$total_pages		= $data->getTotalPages();
				$current_page		= $data->getCurrentPage();
				$total_results		= $data->getTotalResults();
				
				$start_page		= 0;
				$end_page		= 0;

				if ($total_pages < $max_page_positions) {
				
					$start_page	= 1;
					$end_page	= $total_pages;
					
				} else if ($scroll_type == 'FOLLOW') {

					switch ($weight_type) {
						case 'LEFT':
							$start_page	= $current_page;
							$end_page	= $start_page + $max_page_positions - 1;
							if ($end_page > $total_pages) {
								$offset = $end_page - $total_pages;
								$end_page = $total_pages;
								$start_page -= $offset;
							}
							break;
						case 'RIGHT':

							$end_page	= $current_page;
							$start_page	= $end_page - $max_page_positions + 1;
							
							if ($start_page < 1) {
								$offset = abs($start_page) + 1;
								$start_page = 1;
								$end_page += $offset;
							}
							break;
						case 'CENTER':
						default:
							$pos_left	= ceil($max_page_positions / 2) - 1;
							$pos_right	= $max_page_positions - $pos_left - 1;
							$start_page	= $current_page - $pos_left;
							$end_page	= $current_page + $pos_right;

							if ($start_page < 1) {
								$offset = abs($start_page) + 1;
								$start_page = 1;
								$end_page += $offset;
							} else if ($end_page > $total_pages) {
								$offset = $end_page - $total_pages;
								$end_page = $total_pages;
								$start_page -= $offset;
							}


							break;
					}
				} else { // scroll_type = GROUP
					$group_set = ceil($current_page / $max_page_positions);
					$start_page	= ($group_set * $max_page_positions) - $max_page_positions + 1;
					$end_page	= $start_page + $max_page_positions - 1;
				}


				$page_range = new stdClass();
				$page_range->start_page = $start_page;
				$page_range->end_page = $end_page;
				$page_range->current_page = $current_page;
				$page_range->total_pages = $total_pages;
				$page_range->total_results = $total_results;
				
				return $page_range;
			}
		}
		return false;
	}

}
/*
<Template>
	<LayoutTemplate><![CDATA[
		<div>
			<Data field="prev_page_nav" />
			<Data field="page_numbers" />
			<Data field="next_page_nav" />
		</div>
		Currently Viewing Page <Data field="current_page" /> of <Data field="total_pages" />
	]]></LayoutTemplate>
	<PageNumTemplate><![CDATA[
		<span class="paging-item-selected"><strong>[<Data field="page_num" />]</strong></span>
	]]></PageTemplate>
	<CurrentPageNumTemplate><![CDATA[
		<span class="paging-item"><a href="<Data field="page_num_link" />"><Data field="page_num" /></a></span>
	]]></CurrentPageNumTemplate>
</Template>
*/
?>