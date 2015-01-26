<?php

FrameworkManager::loadLogic('form');
FrameworkManager::loadLogic('formfield');
FrameworkManager::loadLogic('formentry');

$form_id = Page::get('formid');
$page = Page::get('p', 1);
if (!is_numeric($page)) $page = 1;
$results_per_page = 10;

$form = FormLogic::getFormById($form_id);
$rs_entries = FormEntryLogic::getEntryRecords($form_id, $page, $results_per_page);
$rs_fields = FormFieldLogic::getFormFieldsByFormId($form_id);

$max_len = 30;
while ($entry = $rs_entries->getNext()) {
	
	$vars = get_object_vars($entry);
	
	foreach($vars as $name=>$value) {
		$is_field = (substr($name, 0, 6) == 'field_');
		$value = is_array($value) ? implode(', ', $value) : $value;
		$value_summary = $value;
		if ($is_field && strlen($value_summary) > $max_len) $value_summary = substr($value, 0, $max_len-3) . '... (more)';
		#if (is_array($value)) $entry->$name = implode(', ', $value);
		$entry->$name = $value;
		
		$summary_key = 'summary_' . $name;
		if ($is_field) $entry->$summary_key = $value_summary;
	}
	
}

if ($form_name = Page::getControlById('form_name')) {
	$name = empty($form->name) ? 'Form Entries' : $form->name;
	$form_name->setText($name);
}

if ($dl_entries = Page::getControlById('dl_entries')) {

	$table_header = '<table cellspacing="0" cellpadding="0" border="0" class="table table-striped">';
	
	$item_template = '';
	
	$table_header .= '<tr>';
	
	$table_header .= '<th>Created</th>';
	
	$item_template .= '<td width="100"><Data field="created" format="date(\'m/d/Y\',strtotime(\'%field\'))" /></td>';
	
	while ($field = $rs_fields->getNext()) {
		
		#$field->enable
		$field_id	= $field->field_id;
		$label		= preg_replace('/[^a-z0-9]+$/i', '', $field->label);  // Replace special characters
		
		$table_header .= '<th>' . $label . '</th>';
		
		$item_template .= '<td><span title="<Data field="field_' . $field_id . '" />"><Data field="summary_field_' . $field_id . '" /></span></td>';

	}
	
	$table_header .= '</tr>';
	
	$item_template1 = '<tr>' . $item_template . '</tr>';
	#$item_template2 = '<tr class="altrow1">' . $item_template . '</tr>';
	
	
	$dl_entries->setEmptyTemplate('No entries');
	
	$dl_entries->setHeaderTemplate($table_header);
	$dl_entries->addItemTemplateByHtml($item_template1);
	#$dl_entries->addItemTemplateByHtml($item_template2);
	$dl_entries->setFooterTemplate('</table>');
	$dl_entries->setData($rs_entries);
	
	
}

?>