<?php

FrameworkManager::loadDAO('formentrydata');

class FormEntryDataLogic {
	
	public static function getDataLookup(array $entry_ids) {
		
		$dao = new FormEntryDataDAO();
		$rs_data = $dao->getDataByEntryIds($entry_ids);
		
		$lookup = new Dictionary();
		
		while ($data = $rs_data->getNext()) {
			
			if (!$form_entry = $lookup->get($data->form_entry_id)) {
				
				$form_entry = new Collection();
				$lookup->set($data->form_entry_id, $form_entry);
				
			}
			
			$form_entry->add($data);
		
		}
		
		return $lookup;
	}
	
	public static function save(FormEntryDataStruct $struct) {
		$dao = new FormEntryDataDAO();
		return $dao->save($struct);
	}
	
}

?>