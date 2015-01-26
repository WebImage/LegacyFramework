<?php
/*

CHANGELOG
07/19/2009	Swapped out OPENWYSIWYG text editor and replaced with TINYMCE
01/24/2010	Added removePassThru('value') to prepareHtmlTagFormat() for the "textarea" type
02/17/2010	Added type check for FILE to avoid escaping array data
08/25/2011	(Robert Jones) Added the ability to turn the editor on for textareas programmatically via enableEditor
08/15/2012	(Robert Jones) Added support for "placeholder" attribute as a passthru attribute
$templateHeader	'<select>';
$templateItem	'<option value="%s">%s</option>';
$templateFooter	'</select>';
$templateItemSeparator	'<br />';

*/

FrameworkManager::loadControl('html');

class InputControl extends HtmlControl {
	var $m_tagName = 'input';
	var $m_type;
	#var $m_value;
	var $m_text;
	var $m_checked;
	var $m_enableEditor = false; // Enable WYSIWYG editor (textarea only)
	var $_escapeHtmlOnDisplay = true; // Not public for now
	
	function getType() { return $this->m_type; }
	function getValue() { return $this->getParam('value'); }
	function getText() { return $this->m_text; }
	function setType($type) { $this->m_type = $type; }
	function setValue($value) { $this->setParam('value', $value); }
	function setText($text) { $this->m_text = $text; }
	
	/**
	 * Getter/settor for editor mode - only works when type=textarea
	 **/
	function isEditorEnabled($true_false=null) { 
		if (is_null($true_false)) { // Getter
			return $this->m_enableEditor;
		} else {
			$this->m_enableEditor = $true_false;
		}
	}
	
	function setStructKey($struct_key) { $this->m_structKey = $struct_key; }
	
	function __construct($init=array()) {
		parent::__construct($init);
		$this->addPassThrus(array(
			'accept',
			'align',
			'alt',
			'checked',
			'disabled',
			'maxlength',
			'name',
			'placeholder',
			'readonly',
			'size',
			'src',
			'type',
			'value',
			'tabindex'));
		// Events
		$this->addPassThrus(array(
			'onchange',
			'onfocus',
			'onblur',
			'onkeyup',
			'onkeydown',
			'onkeypress',
			'onclick'
			));
	}

	function prepareHtmlTagFormat() {
		switch ($this->getType()) {
			case 'text':
			case 'password':
			case 'submit':
			case 'file':
			case 'hidden':
			case 'reset':
				$this->setWrapOutput('<input%s />');
				break;
			case 'checkbox':
			case 'radio':
				// Consider changing to: $this->setWrapOutput('%2$s <input%1$s />');
				$this->setWrapOutput('<input%s /> %s');
				break;
			case 'button':
				$this->setWrapOutput('<button%s>%s</button>');
			case 'textarea':
				$this->removePassThru('value');
				#$wrap = '<textarea%s style="width:600px;height:500px;">%s</textarea>';
				$wrap = '<textarea%s>%s</textarea>';
				/*
				if ($this->isEditorEnabled()) $wrap .= '<script language="javascript1.2">WYSIWYG.attach("' . $this->getId() . '", wysiwyg_editor_settings);</script>';
				if ($this->isEditorEnabled()) $wrap .= '<script language="javascript1.2">jQuery("#' . $this->getId() . '").wymeditor();</script>';
				
				*/

				$append_buttons = '';
				if (ConfigurationManager::get('SITE_ENVIRONMENT') == 'development' && ConfigurationManager::get('DOMAIN') == 'dev.athenacms.com') {
					$append_buttons .= ',|,assetmanager';
				}
				if ($this->isEditorEnabled()) {
/*
#content_css : "/mycontent.css"    // resolved to http://domain.mine/mycontent.css
$styles = Page::getStyleSheets();
$editor_stylesheets = array();

while ($stylesheet = $styles->getNext()) {
	array_push($editor_stylesheets, $stylesheet->getSrc());
}
$tinymce_css = implode(',', $editor_stylesheets);
*/

					$include_image_tag = '';
					if (Page::isAdminRequest()) $include_image_tag = 'image,';
					$wrap .= '
					<script type="text/javascript" language="javascript">
					$("#' . $this->getId() . '").css("visibility", "hidden");
					var test = $("#' . $this->getId() . '").before("<div id=\"loading_message_' . $this->getId() . '\">Loading editor</div>");
					function handle_content_area_loading_' . $this->getId() . '() {
						$("#loading_message_' . $this->getId() . '").remove();
						$("#' . $this->getId() . '").css("visibility", "visible");
					}
					function cleanup_' . $this->getId() . '(type, value) {
						return value;
						// Remove MS Office meta data and comments
				
						var regX = /<(?:!(?:--[\s\S]*?--\s*)?(>)\s*|(?:script|style|SCRIPT|STYLE)[\s\S]*?<\/(?:script|style|SCRIPT|STYLE)>)/g; 
						value.replace(regX, function(m,$1){ return $1?\'\':m; }); 
						return value;
					}
					$(document).ready(function() {
						var txtArea = $("#' . $this->getId() . '");
						if (txtArea.tinymce) {
						txtArea.tinymce({
							theme : "advanced",
							skin : "athena",
							plugins	: "table,media,assetmanager,inlinepopups",
							_theme_advanced_buttons1 : "formatselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,media,|,hr,removeformat,visualaid,|,sub,sup",
							theme_advanced_buttons1 : "formatselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,link,unlink,anchor,' . $include_image_tag . '|,hr,removeformat,visualaid,|,sub,sup",
							theme_advanced_buttons2 : "tablecontrols,|,forecolor,backcolor,|,blockquote,|,charmap,|,cleanup,code' . $append_buttons . '",
							theme_advanced_buttons3 : "", theme_advanced_buttons4:"",
							theme_advanced_toolbar_location : "top",
							theme_advanced_toolbar_align : "left",
							theme_advanced_blockformats : "p,h1,h2,h3,h4,h5,h6",
							theme_advanced_statusbar_location : "bottom",
							extended_valid_elements : "iframe[src|width|height|name|align|scrolling|frameborder],div[align|class|style],img[src|align|width|height|border|style|class|assetId|assetVariation]",
							theme_advanced_resizing : true,
							theme_advanced_resize_horizontal : false,
							convert_urls : false,
							init_instance_callback :"handle_content_area_loading_' . $this->getId() . '",
							cleanup_callback:"cleanup_' . $this->getId() . '",
							inlinepopups_skin : "' . Page::getTheme() . '"
						});
						}
					});
					</script>
					';
				}
				$this->setWrapOutput($wrap);
				break;
			case 'date':
				$this->setWrapOutput('<input%s/> %s');
				break;
		}
	}
	function prepareHtmlTagAttributes() {
		
		if ($this->getType() == 'checkbox' || $this->getType() == 'radio') {
			if ($struct = $this->getStruct()) {
				if ($var_struct = Page::getStruct($struct)) {
					$name = $this->getStructKey();
					
					if (property_exists($var_struct, $name)) {
						if ($this->getType() == 'checkbox') {
							if ($var_struct->$name == 1) $this->setParam('checked', 'true');
						} else if ($this->getType() == 'radio') {
							if ($var_struct->$name == $this->getValue()) $this->setParam('checked', 'true');
						}
					}
				}
			} else {
				$name = $this->getStructKey();
				
				if (substr($name, -2) == '[]') $name = substr($name, 0, -2);
				
				if ($submitted_value = Page::get($name)) {
					if ($this->getType() == 'checkbox') {
						if (is_array($submitted_value)) {
							if (in_array($this->getValue(), $submitted_value)) {
								$this->setParam('checked', 'true');
							}
						} else if ($submitted_value == $this->getValue()) {
							$this->setParam('checked', 'true');
						}
					} else if ($this->getType() == 'radio') {
						if ($submitted_value == $this->getValue()) {
							$this->setParam('checked', 'true');
						}
					}
				}
			}
		}
	}
	function prepareHtmlTagContent() {
		$load_defaults = true;

		if ($struct = $this->getStruct()) {

			if ($var_struct = Page::getStruct($struct)) {

				$load_defaults = false;
				$name = $this->getStructKey();

				if (property_exists($var_struct, $name)) {
					if ($this->getType() != 'checkbox' && $this->getType() != 'radio') {
						$this->setValue($var_struct->$name);
					}
					if ($this->_escapeHtmlOnDisplay) {
						$this->setValue(htmlentities($this->getValue()));
					}
					switch ($this->getType()) {
						case 'radio':
						case 'checkbox':
							$this->setRenderedContent($this->getText());
							break;
						case 'textarea':
							$this->setRenderedContent($this->getValue());
							break;
						case 'date':
							$this->setRenderedContent('[DATE SELECTOR]');
							$this->setType('text');
							break;
					}
				} else {
					// Not set
				}
			}
			
		} else {
			if (strtolower($this->getType()) != 'submit') {
				
				if ($value = Page::get($this->getId())) {
					$this->setValue($value);
				}
				/**
				 * Not sure if this should go here yet... escape HTML entities for value
				 */
				if ($this->_escapeHtmlOnDisplay && strtolower($this->getType()) != 'file') {
					$this->setValue(htmlentities($this->getValue()));
				}
			}
		}

		if ($load_defaults) {
			switch ($this->getType()) {
				case 'radio':
				case 'checkbox':
					$text = $this->getText();
					if (!empty($text)) {
						$text = '<label for="' . $this->getId() . '">' . $text . '</label>';
					}
					$this->setRenderedContent($text);
					break;
				case 'textarea':
					$this->setRenderedContent($this->getValue());
					break;
			}
		}
	}
	function getFile() {
		if ($this->getType() == 'file' && $struct = $this->getStruct()) {
			if (isset($_FILES['auto']) && is_array($_FILES['auto'])) {
				$name = $this->getStructKey();
				$posted_file = $_FILES['auto'];
				if (isset($posted_file['name'][$struct][$name])) {
					$file = new stdClass();
					$file->name	= $posted_file['name'][$struct][$name];
					$file->type	= $posted_file['type'][$struct][$name];
					$file->tmp_name	= $posted_file['tmp_name'][$struct][$name];
					$file->error	= $posted_file['error'][$struct][$name];
					$file->size	= $posted_file['size'][$struct][$name];
				} else return false;
			} else return false;
		} else return false;
	}
}

?>