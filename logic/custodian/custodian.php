<?php

FrameworkManager::loadDAO('custodian');
class CustodianLogic {
	
	public static function getRecords($current_page, $results_per_page, $type=null, $severity=null, $start_date=null, $end_date=null) {
		$dao = new CustodianDAO();
		$dao->paginate($current_page, $results_per_page);
		
		$rows = $dao->getRecords($type, $severity, $start_date, $end_date);
		
		while ($row = $rows->getNext()) {
			
			$row->severity_name = Custodian::getSeverityName($row->severity);
			$row->message_final = $row->message;
			
			$is_php = ($row->type == 'php');
			
			if (!empty($row->variables)) {
				
				$d = ConfigDictionary::createFromString($row->variables);
				$fields = $d->getAll();
				
				while ($field = $fields->getNext()) {
					
					$key = $field->getKey();
					$value = $field->getDefinition();
					
					if ($is_php && $key == 'e_no') {
						
						$value = CustodianLogic::getPhpErrorCode($value);

					}
					
					$key_format = '${%s}';
					$key_replace = sprintf($key_format, $key);
					
					$row->message_final = str_replace($key_replace, $value, $row->message_final);
					
				}
				
			}	
					
		}
		
		return $rows;
	}
	
	public static function getPhpErrorCode($php_err_no) {
		switch($php_err_no) {
			case E_ERROR: // 1 //
				return 'E_ERROR';
			case E_WARNING: // 2 //
				return 'E_WARNING';
			case E_PARSE: // 4 //
				return 'E_PARSE';
			case E_NOTICE: // 8 //
				return 'E_NOTICE';
			case E_CORE_ERROR: // 16 //
				return 'E_CORE_ERROR';
			case E_CORE_WARNING: // 32 //
				return 'E_CORE_WARNING';
			case E_CORE_ERROR: // 64 //
				return 'E_COMPILE_ERROR';
			case E_CORE_WARNING: // 128 //
				return 'E_COMPILE_WARNING';
			case E_USER_ERROR: // 256 //
				return 'E_USER_ERROR';
			case E_USER_WARNING: // 512 //
				return 'E_USER_WARNING';
			case E_USER_NOTICE: // 1024 //
				return 'E_USER_NOTICE';
			case E_STRICT: // 2048 //
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR: // 4096 //
				return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED: // 8192 //
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED: // 16384 //
				return 'E_USER_DEPRECATED';
		}
		return $php_err_no;
	}
	
	public static function save(CustodianStruct $struct) {
		$dao = new CustodianDAO();
		return $dao->save($struct);
	}
	
	public static function clear() {
		$dao = new CustodianDAO();
		return $dao->clearTable();
	}
	
	public static function delete($id) {
		$dao = new CustodianDAO();
		return $dao->delete($id);
	}
}

?>