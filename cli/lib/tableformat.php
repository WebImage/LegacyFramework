<?php

function table_format_date($date) {
	return date('m/d/y', strtotime($date));
}
function table_format_website($website) {
	return preg_replace('#https?://#', '', $website);
}

function table_format_bool($int) {
	return ($int==1) ? 'Y':'N';
}
function table_format_maxlen($str, $len, $ellipse=true) {
	$len_str = strlen($str);
	if ($len_str > $len) {
		$crop_len = $len;
		if ($ellipse) $crop_len -= 3;
		$str = substr($str, 0, $crop_len);
		if ($ellipse) $str .= '...';
	}
	return $str;
}

function table_format_rows($data_rows) {
	$return = '';
	// Calculate spacing for table rows and columns
	$table_headers = array();
	$column_space_width = 2;
	
	for ($i=0, $j=count($data_rows); $i < $j; $i++) {
		$data_row = $data_rows[$i];
		
		foreach($data_row as $column_name=>$column_value) {
			$len_column_name = strlen($column_name);
			$len_column_value = strlen($column_value);
			
			$column_width = 0;
			
			if ($len_column_name > $len_column_value) { // Use column name as length of column
				$column_width = $len_column_name;
			} else { // Otherwise use column value as length of column
				$column_width = $len_column_value;
			}
			
			// Add table column headers
			if ($i == 0) {
				$table_headers[$column_name] = array(
					'max_length' => strlen($column_value)
					);
			}
			
			// Update column width if necessary
			if ($column_width > $table_headers[$column_name]['max_length']) {
				$table_headers[$column_name]['max_length'] = $column_width;
			}
			
		}
		
	}
	
	// Table headers
	$column_output = '| ';
	$hr_output = '|'; // Horizontal deivider
	foreach($table_headers as $column_name=>$column_info) {
		if (substr($column_name, 0, 1) != '_') { // Hidden column
			$column_output .= str_pad(strtoupper($column_name), $column_info['max_length'] + $column_space_width) . '| ';
			$hr_output .= str_repeat('-', $column_info['max_length'] + $column_space_width + 1) . '|';
		}
	}
#	$return .= implode('| ', $column_output);
	$return .= $hr_output . "\n";
	$return .= $column_output . "\n";
	$return .= $hr_output . "\n";
	
	foreach($data_rows as $data_row) {
		$row_output = '';
		foreach($data_row as $column_name=>$column_value) {
			if (substr($column_name, 0, 1) != '_') {
				$row_output .= str_pad($column_value, $table_headers[$column_name]['max_length'] + $column_space_width) . '| ';
			}
		}
		$return .= '| ' . $row_output . "\n";
	}
	$return .= $hr_output . "\n";
	
	return $return;
}

?>