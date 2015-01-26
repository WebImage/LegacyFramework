<?php

class CWI_DB_DateHelper {
	/**
	 * Options for both parameters:
	 *	d = 2-digit with leading zeros
	 *	j = day of month without leading zeros
	 */
	public static function createDayResultSet($key_format='d', $text_format='d') {
		$rs = new ResultSet();
		for($d=1; $d <= 31; $d++) {
			$key = ($key_format=='d') ? sprintf('%02d', $d) : $d;
			$text = ($text_format=='d') ? sprintf('%02d', $d) : $d;
			
			$row = new stdClass();
			$row->id = $key;
			$row->name = $text;
			$rs->add($row);
		}
		return $rs;
	}
	/**
	 *	F = full textual representation of month, January
	 *	m = 2-digit with leading zeros
	 *	M = 3-letter month
	 *	n = month
	 */
	public static function createMonthResultSet($key_format='n', $text_format='F') {
		$rs = new ResultSet();
		$months = array(
			1=>'January',
			2=>'February',
			3=>'March',
			4=>'April',
			5=>'May',
			6=>'June',
			7=>'July', 
			8=>'August',
			9=>'September',
			10=>'October',
			11=>'November',
			12=>'December');
	
		foreach($months as $month_num=>$month_name) {
			switch ($key_format) {
				case 'm':
					$key = sprintf('%02d', $month_num);
					break;
				case 'M':
					$key = substr($month_name, 0, 3);
					break;
				case 'F':
					$key = $month_name;
					break;
				case 'n':
				default:
					$key = $month_num;
					break;
			}
			switch ($text_format) {
				case 'm':
					$text = sprintf('%02d', $month_num);
					break;
				case 'M':
					$text =substr($month_name, 0, 3);
					break;
				case 'n':
					$text = $month_num;
					break;
				case 'F':
				default:
					$text = $month_name;
					break;
			}
			$row = new stdClass();
			$row->id = $key;
			$row->name = $text;
			$rs->add($row);
		}
		return $rs;
		
	}
	/**
	 *	Y = A full numeric representation of a year, 4 digits
	 *	y = two digit representation of a year
	 */
	public static function createYearResultSet($start_year, $end_year) {//, $key_format, $text_format) {
		// Instantiate ResultSet
		$rs = new ResultSet();
		
		// Number of years to include
		$years_to_process = abs($end_year - $start_year);

		// Determine whether we are starting low and counting to a higher number, or starting high and counting to a lower number
		$increment = ($end_year >= $start_year) ? 1 : -1;

		// Start year
		$year = $start_year;
		while ($years_to_process >= 0) {
			
			$row = new stdClass();
			$row->id = $year;
			$row->name = $year;
			$rs->add($row);
			
			// Setup next iteration:
			$years_to_process --;
			$year = $year + $increment;
		}
		
		return $rs;
	}
	
	
	public static function formatDate($display_format, $database_text, $default_value=false) {
		if ($mktime = database_date_to_timestamp($database_text)) {
			return date($display_format, $mktime);
		} else {
			return $default_value;
		}
	}
	
	public static function isDateEmpty($date) {
		return (empty($date) || $date == '0000-00-00 00:00:00');
	}
}

?>