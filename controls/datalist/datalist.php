<?php

/**
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 * 05/15/2010	(Robert Jones) Added control attribute for emptyTemplateWrapClassId to allow the control to modify the wrapClassId property if there are not any results.
 * 01/10/2012	(Robert Jones) Added setWrapOutput(false) call to all internally compiled templates to prevent extraneous HTML from being generated, e.g. <div%s>%s</div>
 */

class DataListControl extends DataWebControl {
	/**
	 * <cms:DataList id="dl" dataSource="ContentLogic::getAllContent()" class="standardBox" emptyTemplateWrapClassId="xyz">
	 * 	<Template>
	 * 		<HeaderTemplate><![CDATA[<h1>Header</h1><table cellpadding="10" border="1">]]></HeaderTemplate>
	 * 		<GroupItemTemplate groupBy="field_name" itemsPerGroup="3"><![CDATA[<tr>%s</tr>]]></GroupItemTemplate>
	 * 		<ItemTemplate><![CDATA[
	 * 			<td valign="top">
	 * 				<strong>Id: </strong> <Data field="id" /><br />
	 * 				<strong>Title: </strong> <Data field="title" default="(no title)" format="strtoupper('%title')" />
	 * 			</td>
	 * 		]]></ItemTemplate>
	 *		<ItemTemplate><![CDATA[
	 *		]]></ItemTemplate>
	 * 		<EmptyItemTemplate><![CDATA[
	 * 			<td bgcolor="#000">&nbsp;</td>
	 * 		]]></EmptyItemTemplate>
	 * 		<FooterTemplate></FooterTemplate>
	 *		<EmptyTemplate>What to display if there are not any results</EmptyTemplate>
	 * 	</Template>
	 * </cms:DataList>
	 * <GroupItemTemplate /> Parameters:
	 
	 *
	 */
	var $m_processInternal = false;
	var $m_groupItemTemplate;
	var $m_itemsPerGroup = 1;
	private $groupBy = array();
	var $m_itemTemplates;
	var $m_bodyContent;
	var $m_summaryTemplate;
	var $m_footerTemplate;
	
	var $m_emptyItemTemplate;
	var $m_emptyTemplate;
	var $m_emptyTemplateClass;
	var $m_emptyTemplateWrapClassId;
	
	function prepareContent() {

		if ($this->prepareInternal()) {
			$templates = $this->getItemTemplates();
		
			$data = $this->getData();
	
			$data->resetIndex();
			
			$item_template_content = '';
			$cache = '';
	
			$template_index_max = count($templates) - 1;
			$ti = -1; // Template index
			
			$total_cached = 0;
	
			for ($ti=0;$ti < count($templates); $ti++) {
				$control_manager = new ControlManager();
				$control_manager->loadControlsFromText($templates[$ti]->getTemplate());
				$templates[$ti]->setTemplate($control_manager->render());
				/*
				$make_display_control = CompileControl::compile($templates[$ti]->getTemplate());
				$make_display_control->setWrapOutput(false);
				$templates[$ti]->setTemplate($make_display_control->render());
				*/
			}
			
			$group_by = $this->groupBy;
			$group_by_first = true;
			$is_grouping_by = (count($this->groupBy) > 0);
			$release_now = false;
			$group_row = null;
		
			while ($row = $data->getNext()) {
	
				$eval = array();
				$ti ++;
				if ($ti > $template_index_max) {
					$ti=0;
				}
				$template = $templates[$ti];
				$template->resetValues();
				$template->set($row);
				/**
				 * Begin Expiremental "GroupBy" Functionality
				 */
				if ($is_grouping_by) { // This is probably still buggy
				
					$any_changed = false;
					
					foreach($group_by as $group_field_name=>$group_field_last_value) {
						if ($row->$group_field_name != $group_field_last_value) {
							$any_changed = true;
							$group_by[$group_field_name] = $row->$group_field_name;
						}
					}
					if ($any_changed) {
					
						if (!$group_by_first) {
							$group_template = $this->getGroupItemTemplate();
							$group_template->set($group_row);
							$cache = sprintf($group_template->render(), $cache);
							$item_template_content .= $cache;
							
							// Reset cached info
							$cache = '';
							$total_cached = 0;
							
							$ti = -1; // Reset template index $ti
						} else {
							$group_by_first = false;
						}
					}
					$group_row = $row;
				}
				
				$cache .= $template->render();
				
	
				$total_cached++;
				
				if ($total_cached == $this->getItemsPerGroup() && !$is_grouping_by) {
					$release_now = true;
				}
				#if ($total_cached == $this->getItemsPerGroup() && !$is_grouping_by) {
				if ($release_now) {
					if ($group_template = $this->getGroupItemTemplate()) {
						$cache = sprintf($group_template->render(), $cache);
					}
					
					$item_template_content .= $cache;
					$cache = '';
					$total_cached = 0;
					$release_now = false; // Reset
				} else if (!$data->hasNext()) {
					if ($empty_item_template = $this->getEmptyItemTemplate()) {
						$num_to_generate = $this->getItemsPerGroup() - $total_cached;
						for ($i=0; $i < $num_to_generate; $i++) {
							$cache .= $empty_item_template;
						}
					}
					if ($group_template = $this->getGroupItemTemplate()) {
						$group_template->set($group_row);
						$cache = sprintf($group_template->render(), $cache);
					}
					$item_template_content .= $cache;
					
					$cache = '';
					$total_cached = 0;
	
				}
	
			}
			
			$this->setBodyContent($item_template_content);
			
			if (strlen($item_template_content)) { // Only display if there is content
				$output = $this->getHeaderTemplate();
				$output .= $this->getBodyContent();
		
				$output .= $this->getFooterTemplate();
				$this->setRenderedContent($output);
			} else {
				$empty_template_wrap_class_id = $this->getEmptyTemplateWrapClassId();
				$empty_template_class = $this->getEmptyTemplateClass();
				if (!empty($empty_template_wrap_class_id)) $this->setWrapClassId($empty_template_wrap_class_id);
				if (!empty($empty_template_class)) $this->addClass($empty_template_class);
				$empty_template = $this->getEmptyTemplate();
				$this->setRenderedContent($empty_template);
			}
		}
	}
	
	function prepareInternal() {

		try {
			$xml = CWI_XML_Compile::compile($this->getInnerCode());
		} catch (CWI_XML_CompileException $e) {
			die('Could not prepare internal: ' . $e->getMessage());
			return false;
		}
		/*
		<Template>
			<HeaderTemplate />
			<GroupTemplate itemsPerGroup="x" /> <!-- Not yet designed/coded
			<ItemTemplate />
			<EmptyItemTemplate />
			<SummaryTemplate /> <!-- Not yet designed/coded
			<FooterTemplate />
		</Template>
		*/

		if ($object_template = $xml->getPathSingle('/Template')) {

			if ($header_template = $object_template->getPathSingle('HeaderTemplate')) $this->setHeaderTemplate($header_template->getData());
			if ($empty_item_template = $object_template->getPathSingle('EmptyItemTemplate')) $this->setEmptyItemTemplate($empty_item_template->getData());
			if ($group_item_template = $object_template->getPathSingle('GroupItemTemplate')) {				
				$group_data = ControlTemplateHelper::parseTemplate($group_item_template->getData());
				
				if ($group_by = $group_item_template->getParam('groupBy')) {
					$this->addGroupBy($group_by);
				} else if ($items_per_group = $group_item_template->getParam('itemsPerGroup')) $this->setItemsPerGroup($items_per_group);
				$this->setGroupItemTemplate($group_data);
			}
			
			if ($item_template = $object_template->getPath('ItemTemplate')) {

				foreach($item_template as $it) {
					$this->addItemTemplateByHtml($it->getData());
				}
			}
			
			if ($footerTemplate = $object_template->getPathSingle('FooterTemplate')) $this->setFooterTemplate($footerTemplate->getData());
			if ($emptyTemplate = $object_template->getPathSingle('EmptyTemplate')) $this->setEmptyTemplate($emptyTemplate->getData());
		}
		
		return true;
	}
	
	function addItemTemplateByHtml($html_template) {
		if ($it_template = ControlTemplateHelper::parseTemplate($html_template)) {
			$this->addItemTemplate($it_template);
		}
	}
	
	function addItemTemplate($data_tag_template) {
		$this->m_itemTemplates[] = $data_tag_template;
	}

	function getHeaderTemplate() { return $this->getParam('headerTemplate'); }
	function getGroupItemTemplate() { if (!empty($this->m_groupItemTemplate)) return $this->m_groupItemTemplate; else return false; }
	function getItemTemplates() { return $this->m_itemTemplates; }
	function getBodyContent() { return $this->m_bodyContent; } 
	function getSummaryTemplate() { return $this->m_summaryTemplate; }
	function getFooterTemplate() { return $this->m_footerTemplate; }
	function getItemsPerGroup() { return $this->m_itemsPerGroup; }
	function getEmptyItemTemplate() { if (!empty($this->m_emptyItemTemplate)) return $this->m_emptyItemTemplate; else return false; }
	function getEmptyTemplate() { if (!empty($this->m_emptyTemplate)) return $this->m_emptyTemplate; else return false; }
	function getEmptyTemplateClass() { return $this->m_emptyTemplateClass; }
	function getEmptyTemplateWrapClassId() { return $this->m_emptyTemplateWrapClassId; }
	
	function setHeaderTemplate($header_template) { $this->setParam('headerTemplate', $header_template); }
	function setGroupItemTemplate($group_item_template) { $this->m_groupItemTemplate = $group_item_template; }
	function setGroupItemTemplateByHtml($html_template) {
		if ($group_template = ControlTemplateHelper::parseTemplate($html_template)) {
			$this->setGroupItemTemplate($group_template);
		}
	}
	function setBodyContent($item_template_content) { $this->m_bodyContent = $item_template_content; }
	function setSummaryTemplate($summary_template) { $this->m_summaryTemplate = $summary_template; }
	function setFooterTemplate($footer_template) { $this->m_footerTemplate = $footer_template; }
	function setItemsPerGroup($items_per_group) { $this->m_itemsPerGroup = $items_per_group; }
	function setEmptyItemTemplate($empty_item_template) { $this->m_emptyItemTemplate = $empty_item_template; }
	function setEmptyTemplate($empty_template) { $this->m_emptyTemplate = $empty_template; }
	function setEmptyTemplateClass($class) { $this->m_emptyTemplateClass = $class; }
	function setEmptyTemplateWrapClassId($wrap_class_id) { $this->m_emptyTemplateWrapClassId = $wrap_class_id; }
	/**
	 * Groups item templates by specific fields - still working on this...
	 */
	private function addGroupBy($group_fields) {
		$fields = explode(',', $group_fields);
		foreach($fields as $field) {
			$field = trim($field);
			// Set "last value"
			$this->groupBy[$field] = false;
		}
	}
}

?>