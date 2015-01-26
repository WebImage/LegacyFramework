<?php

class PageControl extends WebControl {
	var $page_id;
	var $page_title;
	var $vars;
	var $init_code = array();
	var $post_init_code = array();
	var $attach_init_code = array();
	var $render_code = array();
	function __construct($init_array=array()) {
		parent::__construct($init_array);
	}
	function loadPlaceHolderContent($page_id) {}

	function setTemplate($template_file) {
		$fp = fopen(PathManager::translate($template_file), 'r');
		$template_contents = '';
		while ($read = fread($fp, 1024)) {
			$template_contents .= $read;
		}
		fclose($fp);

		$template_object = CompileControl::compile($template_contents);

		$this->init_code[] = $template_object->init_code;
		$this->attach_init_code[] = $template_object->attach_init_code;
		$this->render_code[] = $template_object->render_code;
	}
	function loadControl($control_file) {
		$fp = fopen(PathManager::translate($control_file), 'r');
		$control_contents = '';
		while ($read = fread($fp, 1024)) {
			$control_contents  .= $read;
		}
		fclose($fp);
		
		$control_object = CompileControl::compile($control_contents);
		if (isset($control_object->master_page_file)) {
			//$control_object->master_page_file;
			$this->setTemplate($control_object->master_page_file);
		}

		$this->init_code[] = $control_object->init_code;
		$this->attach_init_code[] = $control_object->attach_init_code;
		$this->render_code[] = $control_object->render_code;
		return $control_object;
	}
	
	function prepareContent() {
		eval(implode($this->init_code));
		eval(implode($this->attach_init_code));
		ob_start();
		eval(implode($this->render_code));
		$rendered_content = ob_get_contents();
		ob_end_clean();
		$this->setRenderedContent($rendered_content);
	}
	function addPostInitCode($code) {
		$this->post_init_code[] = $code;
	}
}

?>