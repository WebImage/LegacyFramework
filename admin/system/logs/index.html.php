<?php

FrameworkManager::loadLogic('custodian');

if ($clear = Page::get('clear')) {
	CustodianLogic::clear();
	Page::redirect('index.html');
} else if ($delete = Page::get('delete')) {
	CustodianLogic::delete($delete);
	Page::redirect('index.html');
}

$p = Page::get('p', 1); // Page number
$n = Page::get('n', 20); // Results per page
$rs_logs = CustodianLogic::getRecords($p, $n);

if ($dg_records = Page::getControlById('dg_records')) {
	
	while ($log = $rs_logs->getNext()) {
		
		$vars = ConfigDictionary::createFromString($log->variables);
		$var_fields = $vars->getAll();
		
		$message_formatted = '<span style="font-style:italic;">' . $log->message . '</span>';

		while ($field = $var_fields->getNext()) {
			$name = $field->getKey();
			$value = $field->getDefinition();
			
			if ($log->type == 'php' && $name == 'e_no') {
						
				$value = CustodianLogic::getPhpErrorCode($value);

			}
					
			$replace = '${' . $name . '}';
			$message_formatted = str_replace($replace, '<span style="color:green;font-weight:bold;">' . $value . '</span>', $message_formatted);
			
		}
		
		$log->message_formatted = $message_formatted;
		
	}
	
	$dg_records->setData($rs_logs);
	
}

?>