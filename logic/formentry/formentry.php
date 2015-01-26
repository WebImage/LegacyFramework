<?php

FrameworkManager::loadDAO('formentry');
class FormEntryLogic {
	
	public static function getEntryRecords($form_id, $page, $results_per_page) {
		
		FrameworkManager::loadLogic('formentrydata');
		
		$dao = new FormEntryDAO();
		#$dao->paginate($page, $results_per_page);
		$rs_entries = $dao->getFormEntriesByFormId($form_id);
		
		// Keep track of entry ids
		$entry_ids = array();
		
		while ($entry = $rs_entries->getNext()) {
			array_push($entry_ids, $entry->id);
		}
		
		$entry_data_lookup = FormEntryDataLogic::getDataLookup($entry_ids);
				
		while ($entry = $rs_entries->getNext()) {
			
			if ($entry_data = $entry_data_lookup->get($entry->id)) {

				while ($data = $entry_data->getNext()) {
					
					$column_key = 'field_' . $data->field_id;
					
					// If column is not set then define it here
					if (!isset($entry->$column_key)) $entry->$column_key = $data->value;
					// If the column is set, but it is not an array, then convert the existing value to an array and add the new value
					else if (!is_array($entry->$column_key)) $entry->$column_key = array($entry->$column_key, $data->value);
					// Otherwise assume that the column has already been converted to an array
					else array_push($entry->$column_key, $data->value);
					
				}	
				
			}
			
		}
		
		return $rs_entries;
		
	}
	
	public static function getFormEntryById($id) {
		
		$form_entry_dao = new FormEntryDAO();
		return $form_entry_dao->load($id);
		
	}
	
	public static function save($form_entry_struct) {
		
		$form_entry_dao = new FormEntryDAO();
		return $form_entry_dao->save($form_entry_struct);
		
	}
}

?>