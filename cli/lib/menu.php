<?php

/**
 * A collection of functions to handle menu input
 **/

function prompt($prompt, $length=20) {
        echo $prompt . ' ';
        return trim(fread(STDIN, $length));
}
function prompt_yesno($prompt) {
	while (true) {
		$answer = strtolower(prompt($prompt));

		if (in_array($answer, array('y', 'n'))) return $answer;
		
	}
}
/**
 * Render a menu
 * @param array $menu_items
 * @example
 * $response = render_menu(array(
 		// choice        Choice Text    function called on selection
		   'c' => array('Create Site', 'create_site_menu'), 
		   'u' => array('Update Site', 'update_site_menu'),
		   'q' => array('Quit', 'quit_menu'))
	);
 **/
function render_menu($menu_items, $prompt='Enter choice:') {
	while (true) {
		foreach($menu_items as $menu_char => $options) {
			echo str_pad($menu_char, 5) . $options[0] . "\n";
		}
		echo "\n";
		$answer = prompt($prompt);
		if (isset($menu_items[$answer])) {
			return $answer;
		}
	}
}

/**
 * Takes an array of choices and looks up the selected choice, then calls the function associated with the selection choice
 **/
function call_menu_choice($menu_items, $choice) {
	if (!isset($choice[1])) return; 
	$menu_function_name = $menu_items[$choice][1];
	if (function_exists($menu_function_name)) {
		call_user_func($menu_function_name);
	} else {
		echo 'Could not find ' . $menu_function_name . ' function.' . "\n";
		sleep(1);
	}
}

/**
 * An easy way to create a consistent looking section
 * @param string $title = The title that will appear
 * @param int $level 1 or 2 to distingusih between different depths (in html the analogy would be H1 versus H2)
 **/
function render_menu_section($title, $level=1) {
	$total_length = 80;
	
	if ($level == 3) {
		$title = '// ' . $title . "\n";
	} else if ($level == 2) {
		#$title = '-- ' . $title . ' --';
		#$title = str_pad($title, $total_length, '-') . "\n";
		$title = "#\n#\n# " . $title . "\n#\n#\n";
	} else { /* level = 1 */
		$title = '  ' . $title;
		$title = str_repeat('=', $total_length) . "\n" . str_pad($title, $total_length) . "\n" . str_repeat('=', $total_length) . "\n";
	}
	return $title;
}

?>