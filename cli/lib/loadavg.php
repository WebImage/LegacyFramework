<?php

function get_load_avg() {
	exec('cat /proc/loadavg', $load_avg);
	list($one_second_avg) = explode(' ', $load_avg[0]);
	return $one_second_avg;
}

/**
 * Supports a callback function in the format: 
 * function_name($current_loop_count, $current_load_avg, $max_val, $wait_for_threshold)
 **/
function wait_load_avg($max_val=0.50, $wait_for_threshold=0.25, $seconds_between_checks=5, $callback_function=null) {
	$current_load_avg = get_load_avg();
	
	if ($current_load_avg > $max_val) {
		
		$loop_count = 0;
		
		// Only output debug information if a callback function is not defined
		if (is_null($callback_function)) echo 'Waiting for load average to drop below ' . $wait_for_threshold. '.  Currently: ' . $current_load_avg . " (sleep " . $seconds_between_checks. ")\n";
		
		while (floatval($current_load_avg) > floatval($wait_for_threshold)) {
			$loop_count ++;
			
			if (is_null($callback_function)) {
				echo '     Current #' . $loop_count . ': ' . $current_load_avg . "\n";
			} else {
				call_user_func($callback_function, $loop_count, $current_load_avg, $max_val, $wait_for_threshold);
			}
			
			sleep($seconds_between_checks);
			$current_load_avg = get_load_avg();
		}
	}
	
}

/**
 * Supports a callback function in the format:
 * function_name($memory_usage, $max_allowed, $message)
 **/
function kill_on_high_memory_usage($max_memory_bytes, $message, $callback_function=null) {
	
	if (memory_get_usage() > $max_memory_bytes) {
		
		if (is_null($callback_function)) {
			echo 'Halting program for high memory usage.  Max = ' . number_format($max_memory_bytes / 1024) . ' kb; Currently ' . number_format(memory_get_usage() / 1024) . ' kb' . "\n";
			if (!empty($message)) echo 'Message: ' . $message . "\n";
			exit;
		} else {
			call_user_func($callback_function, memory_get_usage(), $max_memory_bytes, $message);
		}
		
	}
}

?>