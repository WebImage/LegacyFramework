<?php

class Template {
	var $vars = array();
	var $rendered_content;
	var $_cache = false;
	var $_cache_file;
	
	function Template($file = null) {
		$this->file = $file;
	}
	
	/**
	 * @param	$global NOT YET COMPLETE
	 */
	function set($name, $value, $global=false) {
		if (isset($this)) { // $this will not be defined if this is a static call to set a certain value in all templates, i.e. Template::set('home_page', 'http://website/');
			$this->vars[$name] = (is_object($value) && get_class($value) == 'Template') ? $value->render() : $value;
		}
		
		if ($global || !isset($this)) { // !isset($this) would be true if this were a static call
		
			//$GLOBALS['template_vars'][$name] = (is_object($value) && get_class($value) == 'Template') ? $value->render() : $value;
			Template::setGlobal($name, $value);
		}
	}
	
	function setGlobal($name, $value) {
		$GLOBALS['template_vars'][$name] = (is_object($value) && get_class($value) == 'Template') ? $value->render() : $value;
	}
	/*
	function cache($cache_file) {
		$this->_cache = true;
		$this->_cache_file = $cache_file;
	}
	*/
	
	function render($file = null) {
		/*
		if ($this->_cache) {
			$cache = new CWI_Cache($this->_cache_file);
			if ($cache->isCached()) {
				$this->rendered_content = $cache->read()
			}
		}
		*/
		if (strlen($this->rendered_content) == 0) {
			if (!$file) $file = $this->file;
			
			//if (count($this->vars) > 0) extract($this->vars);
			$vars = $this->getVariableList();

			if (count($vars) > 0) extract($vars);
			
			//if (!$this->isCompiled()) $this->build();
			//include(ConfigurationManager::get('DIR_FS_CACHE') . basename($this->file) . '.cache');
			
			ob_start();
				if (file_exists($this->file)) {
					include($this->file);
				} else return false;
				$this->rendered_content = ob_get_contents();
			ob_end_clean();
			
			/*
			ob_start();
			if (file_exists($this->file)) {
				include($this->file);
			} return false;
			
			$this->rendered_content = ob_get_contents();
			ob_end_clean();
			*/
			
		}
		return $this->rendered_content;
	}
	
	function display() {
		echo $this->render();
		return true;
	}
	
	function getVariableList() {
		$vars = $this->vars;
		if (isset($GLOBALS['template_vars']) && is_array($GLOBALS['template_vars'])) {
			foreach ($GLOBALS['template_vars'] as $key=>$value) {
				if (!isset($vars[$key])) $vars[$key] = $value;
			}
		}
		return $vars;
	}
	
	function get($name) {
		if (isset($this)) { // TRUE=status; FALSE=static
			if (isset($this->vars[$name])) return $this->vars[$name];
		}
		
		if (isset($GLOBALS['template_vars'][$name])) {
			return $GLOBALS['template_vars'][$name];
		} else return false;
	}
	
	function getVariableCacheList() {
		$vars = array();
		foreach($this->vars as $key=>$val) {
			$vars[$key] = new stdClass();
			$vars[$key]->value = $val;
			$vars[$key]->global = false;
		}
		if (isset($GLOBALS['template_vars']) && is_array($GLOBALS['template_vars'])) {
			foreach ($GLOBALS['template_vars'] as $key=>$value) {
				if (!isset($vars[$key])) {
					$vars[$key] = new stdClass();
					$vars[$key]->value = $value;
					$vars[$key]->global = true;
				}
			}
		}
		return $vars;
		
	}
	/*
	function buildContent($source_content, $destinatio) {
		
	}
	*/
	/*
	function isCompiled() {
		$compiled_template = ConfigurationManager::get('DIR_FS_CACHE') . basename($this->file) . '.cache';
		return file_exists($compiled_template);
	}
	*/
	function resetVariables() {
		$this->vars = array();
	}
	
	function buildFromFile($file) {
		$template_contents = file_get_contents($file);
		return Template::buildFromText($template_contents);
	}
	function validLoop($stack, $loop_name) {
		foreach($stack as $x) {
			if ($x->loop_name == $loop_name) {
				return true;
			}
		}
		return false;
	}
	
	function validIterator($stack, $match_iterator) {
		foreach($stack as $x) {
			if ($x->iterator_name == $match_iterator) {
				return true;
			}
		}
		return false;
	}
	
	

	function translateIfStatement($statement) {
		$open_ifs = 0;
		
		$filter_statement = preg_replace("/[\)\(]*/", '', $statement);
		$statements = preg_split("/(&&)|(\|\|)/", $filter_statement);
		
		foreach($statements as $evaluate_string) {
			$evaluate_string = trim($evaluate_string);
			if (strpos($evaluate_string, '<') !== false) {
				$operand = '<';
				$translate_operand = $operand;
			} else if (strpos($evaluate_string, '>') !== false) {
				$operand = '>';
				$translate_operand = $operand;
			} else if (strpos($evaluate_string, '!=') !== false) {
				$operand = '!=';
				$translate_operand = $operand;
			} else if (strpos($evaluate_string, '=') !== false) {
				$operand = '=';
				$translate_operand = '==';
			} else {
				$operand = '';
				$translate_operand = $operand;
			}
			
			if (!empty($operand)) {
				$expression_parts = explode($operand, $evaluate_string);
				$left_side = trim($expression_parts[0]);
				$right_side = trim($expression_parts[1]);
				
				$left_side = Template::scrubValue($left_side);
				$right_side = Template::scrubValue($right_side);
				
				$replace_with_code = $left_side . ' ' . $translate_operand. ' ' . $right_side;
				
			} else {
				$replace_with_code = Template::scrubValue($evaluate_string);
				
			}

			$statement = str_replace($evaluate_string, $replace_with_code, $statement);
		}
		return $statement;
	}
	function scrubValue($value) { // Static
		
		$translated = $value;
		
		$reserved_children = array('index', 'count', 'length');
		
		$type = 'variable'; // {variable|literal|function}
		$first_char = substr($translated, 0, 1);
		$last_char = substr($translated, strlen($translated)-1, 1);

		if ($first_char == "'" || $first_char == '"' || is_numeric($translated)) $type = 'literal'; // Used generally for IF/THEN statements
		else if (strpos($value, '.') > 0) { // Check for modifier
			
			$last_dot	= strrpos($value, '.');
			
			$object		= str_replace('.', '->', substr($value, 0, $last_dot));
			$modifier	= substr($value, $last_dot+1, strlen($value)-$last_dot-1);
			
			$open_paren = strpos($modifier, '(');
			if ($open_paren > 0) {
				$parameters = array();
				$param_string = substr($modifier, $open_paren+1, strlen($modifier)-$open_paren-2);
				if (strlen($param_string) > 0) {
					$parameters = explode(',', $param_string);
					foreach($parameters as $parameter) {
						$parameter = Template::scrubValue(trim($parameter));
					}
				}
				$modifier = substr($modifier, 0, $open_paren);
			}
			
			switch (strtolower($modifier)) {
				
				case 'htmlsafe':
					$type = 'function';
					$translated = 'htmlentities($' . $object . ')';
					break;
				case 'length':
					$type = 'function';
					$translated = 'strlen($' . $object . ')';
					break;
				case 'count':
					$type = 'function';
					$translated = 'count($' . $object . ')';
					break;
				case 'index':
					$translated = $object . '_' . $modifier;
					break;
				case 'defined':
					$type = 'function';
					$translated = 'isset($' . $object . ')';
					break;
				case 'numberformat':
					// numberFormat($decimals) = number_format($var, $decimals)
					$type = 'function';
					$translated = 'number_format($' . $object;
					if (!empty($parameters[0])) $translated .= ', ' . $parameters[0];
					$translated .= ')';
					break;
				case 'showstructure':
					$type = 'function';
					$translated = 'print_r($' . $object . ')';
					break;
				default:
					$translated = $object . '->' . $modifier;
					break;
				
			}
		
		}
		
		if ($type == 'variable') {
			$translated = '$' . $translated;
		}
		
		return $translated;
		/*
		if (strpos($value, '.') > 0) {
			
			$object_split	= explode('.', $value);
			$this_name	= $object_split[0];
			$this_value	= $object_split[1];
			
			if ($this_value == 'index' && Template::validIterator($loop_stack, $this_name)) {
				$php_code = '<?php ?>';
			} else if ($this_value == 'count' &&Template::validLoop($loop_stack, $this_name)) {
				$php_code = '<?php ?>';
			}
		}
		*/
	}
	
	/**
	 * @access static
	 * @return string compiled file with header
	 *
	 * Takes a template in the form of a passed string and compiles it into PHP code
	 *
	 * Templates commands [to be translated] are in the form of {command}.  Variables to not have any special designation, whereas literal strings are wrapped in double quotes.
	 * Available options are:
	 *	{function::[function_name]}	Executes a real PHP function where [function_name] is the function name and parameters in normal form, i.e. function::
	 *	{if} | {else if} | {end if}	If/then statements
	 *	{loop} {end loop}		Executes various loops.  
	 *
	 * Loop options:
	 *	{loop array_var_name as var_name} .... {end loop} translates to <?php foreach($array_var_name as $var_name) { ?> ... <?php } ?>
	 *	{loop 1 through 10} ... {end loop} translates to <?php for($temp_var=1; $temp_var <= 10; $temp_var) { ?> ... <?php } ?> - NOTE: $temp_var is an automatically generated variable name so that loops do not intersect
	 *	{loop x:1 through 10} ... {end loop} translated the same as above, except that "$x" (in this case) replaces $temp_var
	 */
	function buildFromText($template_contents) { // Static
		//$compile_to = ConfigurationManager::get('DIR_FS_CACHE') . basename($this->file) . '.cache';
		$template_contents = preg_replace('/{#.*#}/ims', '', $template_contents);
		
		preg_match_all('/{(.+?)}/', $template_contents, $matches);
		$full_match = $matches[0];
		$values = $matches[1];
		$open_ifs = 0;
		//$foreach_loop_open = 0;
		$open_loops = 0;
		$loop_stack = array();
		
		
		
		for ($i=0; $i < count($full_match); $i++) {
			$php_code = '';
			$match = $full_match[$i];
			$value = trim($values[$i]);
			
			/*
			if (count($loop_stack) > 0 && strpos($value, '.')) {
						
				$expression_split = explode('.', $value);
				$loop_stack_name = $expression_split[0];
				$loop_stack_var = $expression_split[1];
				// && !in_array($loop_stack_var, $reserved_children
				//if (in_array($loop_stack_name, $loop_stack)) {
				if (!in_array($loop_stack_var, $reserved_children)) {
					$value = $loop_stack_name . '->' . $loop_stack_var;
				}
				
				//if (Template::validIterator($loop_stack, $loop_stack_name)) {
			}
			
			
			*/
			
			if (substr($value, 0, 10) == 'function::') {
				
				$full_function_call	= substr($value, 10, strlen($value)-10);
				$open_paren		= strpos($full_function_call, '(');
				$close_paren		= strrpos($full_function_call, ')');
				
				$function_name		= substr($full_function_call, 0, $open_paren);
				$param_string		= substr($full_function_call, $open_paren+1, $close_paren-$open_paren-1);
				
				$params	= explode(',', $param_string);
				
				for ($p=0; $p < count($params); $p++) {
					$params[$p] = Template::scrubValue(trim($params[$p]));
				}
				
				$param_string		= implode(', ', $params);
				
				$match_type 		= 'Function';
				
				$php_code		= '<?php if (function_exists(\'' . $function_name . '\')) { echo ' . $function_name . '(' . $param_string . ')' . '; } ?>';
				
			} else if (substr($value, 0, 2) == 'if' || substr($value, 0, 7) == 'else if') {
				
				/**
				 * Future: May need a more complex if/then processor to handle more advanced if/then statements like:
				 *
				 * {if ((var1 == 'value') && (var2 == 'some value' || var2 == 'some other value'))}
				 */
				
				if (substr($value, 0, 2) == 'if') {
					$type_if = true;
					$type_else_if = false;
					$type_prepend = 'if';
				} else if (substr($value, 0, 7) == 'else if') {
					$type_if = false;
					$type_else_if = true;
					$type_prepend = 'else if';
				}
				
				$match_type = 'if/then';
				$open_ifs ++;
				
				$evaluate_start = strpos($value, '(') + 1;
				$evaluate_end = strrpos($value, ')');
				$evaluate_string = substr($value, $evaluate_start, $evaluate_end-$evaluate_start);
				
				$evaluate_string = Template::translateIfStatement($evaluate_string);
				$php_code = '<?php ' . $type_prepend . ' (' . $evaluate_string . ') { ?>';
			
			} else if (substr($value, 0, 4) == 'else') {
					
				$php_code = '<?php } else { ?>';
				
			} else if (substr($value, 0, 4) == 'loop') {
				
				$match_type = 'loop';
				$loop_type = '';
				$loop_expression = substr($value, 5, strlen($value)-5);
				if (strpos($loop_expression, ' as ') > 0) {
					$loop_type = 'foreach';
					$loop_expression_split = explode(' as ', $loop_expression);
				} else if (strpos($loop_expression, ' through ') > 0) { // {loop 1 through 2}
					$loop_type = 'count';
					$loop_expression_split = explode(' through ', $loop_expression);
				}
				
				$loop_expression_left = trim($loop_expression_split[0]);
				$loop_expression_right = trim($loop_expression_split[1]);
				
				if ($loop_type == 'count') {
					$tmp = explode(':', $loop_expression_left);
					
					if (count($tmp) == 1) { // Loop variable not defined, set it here
					
						$loop_count_num ++;
						$loop_count_var = 'loop' . $loop_count_num; // Create unique loop var to help prevent duplicate vars
						
					} else if (count($tmp) == 2) { // Defined Loop Variable  i.e. {loop x:1 through 100}
					
						$loop_count_var = $tmp[0]; 
						$loop_expression_left = $tmp[1];
						
					}
					
				}
				
				$loop_expression_left = Template::scrubValue($loop_expression_left);
				$loop_expression_right = Template::scrubValue($loop_expression_right);
				
				$build_stack = new stdClass();
				$build_stack->loop_name = $loop_expression_left;
				$build_stack->iterator_name = $loop_expression_right;
				
				array_push($loop_stack, $build_stack);
				
				if ($loop_type == 'foreach') {
					$php_code = '<?php foreach (' . $loop_expression_left . ' as ' . $loop_expression_right . ') { ?>'; /* Outputs: <?php foreach($array_name as $indiv_value) {?> */
				} else if ($loop_type == 'count') {
					$php_code = '<?php for ($' . $loop_count_var . '=' . $loop_expression_left . '; $' . $loop_count_var . ' <= ' . $loop_expression_right . '; $' . $loop_count_var . '++) { ?>';
				}

			} else if ($value == 'end loop') {
				
				array_pop($loop_stack);
				$match_type = 'close loop';
				$open_loops --;
				$php_code = '<?php } ?>';	
			
			} else if ($value == 'end if') {
				
				$match_type = 'close if';
				$open_ifs --;
				$php_code = '<?php } ?>';	
			
			} else {
				
				/*
				Future:
				
				Pipe (|) is an option/instruction (i.e. "{varname|blank} does not output anything if {varname} is not defined
				Dot (.) is a modifier (i.e. if $varname = "mytest" and the template has {varname.uppercase} the template will return "MYTEST"
				*/
				
				$match_type = 'Value';
				$display_non_match = false;
				/*
				if (strpos($value, '|') > 0) {
					
					$filter_split	= explode('|', $value);
					$value		= $filter_split[0];
					$apply_filter	= $filter_split[1];
					switch($apply_filter) {
						case 'debug':
							$display_non_match = true;
							break;
					}
					
				}
				*/
				$php_code = '<?php echo ' . Template::scrubValue($value) . '; ?>';
				
			
			
			}
			
			$template_contents = str_replace($full_match[$i], $php_code, $template_contents);
		}
	
	return '<?php /* DO NOT EDIT THIS FILE DIRECTLY.  This template was generated on ' . date("M d, Y") . ' at ' . date("H:i:s") . ' */ ?>' . "\r\n" . $template_contents;
		//file_put_contents($compile_to, $template_contents);
	}
}

?>