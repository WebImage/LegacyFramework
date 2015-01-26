<?php

FrameworkManager::loadLibrary('html.form.fileupload');

/**
 * Core asset class
 */
interface CWI_ASSETS_IAsset {
	public function renderHtml();
	public function renderHtmlLink();
	#public function renderHtmlVisual();
	public function getThumbnailImage();
	#public function getAssetManager();
	public function getProperties();
	public function getProperty($property);
	public function getDisplayTemplate();
	public function getWebFilePath();
	public function getSystemFilePath();
	public function getId();

	public function extractFromText($text);
	
	public function setProperty($property, $value);
	public function setDisplayTemplate($template);
	public function setWebFilePath($file_path);
	public function setSystemFilePath($file_path);
	public function setId($id);
}

class CWI_ASSETS_Asset implements CWI_ASSETS_IAsset {
	/**
	 * @property CWI_ASSETS_AssetFolder $folder
	 **/
	private $caption, $categoryId, $description, $enable, $folder, $isManageable, $originalFileName, $parentId, $variationKey, $version;

	private $id;
	private $properties;
	private $displayTemplate;
	private $webFilePath;
	private $systemFilePath;
	/**
	 * @property string A name referencing an CWI_ASSETS_AssetFileType configuration for this file's type (assets.xml)
	 **/
	private $type;
	
	public function __construct() {
		$this->properties = new ConfigDictionary();
	}
	
	public function renderHtml() { return ''; }
	public function renderHtmlLink() { return ''; }
	#public function renderHtmlVisual();
	public function getThumbnailImage() { return ''; }
	#public function getAssetManager();
	public function getProperties() { return $this->properties->getAll(); }
	public function getPropertiesRaw() { return $this->properties; }
	public function getProperty($property) { return $this->properties->get($property); }
	public function getDisplayTemplate() { return ''; }
	public function getWebFilePath() { return $this->webFilePath; }
	public function getSystemFilePath() { return $this->systemFilePath; }
	public function getId() { return $this->id; }
	public function getType() { return $this->type; }
	
	public function getSystemFileName() {
		$file_path = basename($this->getSystemFilePath());
		#echo 'File Path: ' . $file_path;exit;
	}
	
	public function getCaption() { return $this->caption; }
		
	public function setProperty($property, $value) { $this->properties->set($property, $value); }
	public function setProperties(ConfigDictionary $properties) { $this->properties = $properties; }
	public function setDisplayTemplate($template) {$this->displayTemplate = $template; }
	public function setWebFilePath($file_path) { $this->webFilePath = $file_path; }
	public function setSystemFilePath($file_path) { $this->systemFilePath = $file_path; }
	public function setId($id) { $this->id = $id; }
	public function setType($type) { $this->type = $type; }
	
	public function getCategoryId() { return $this->categoryId; }
	public function getDescription() { return $this->description; }
	public function getEnable() { return $this->enable; }
	public function getFolder() { return $this->folder; }
	public function isManageable($true_false=null) { 
		if (is_null($true_false)) { // Getter
			return $this->isManageable;
		} else if (is_bool($true_false)) {
			$this->isManageable = $true_false;
		} else {
			throw new Exception('Invalid isManageable() parameter. Expecting boolean.');
		}
	}
	public function getOriginalFileName() { return $this->originalFileName; }
	public function getParentId() { return $this->parentId; }
	public function getVariationKey() { return $this->variationKey; }
	public function getVersion() { return $this->version; }
	
	public function setCaption($caption) { $this->caption = $caption; }
	public function setCategoryId($category_id) { $this->categoryId = $category_id; }
	public function setDescription($description) { $this->description = $description; }
	public function setEnable($enable) { $this->enable = $enable; }
	public function setFolder(CWI_ASSETS_AssetFolder $folder) { $this->folder = $folder; }
	public function setOriginalFileName($original_file_name) { $this->originalFileName = $original_file_name; }
	public function setParentId($parent_id) { $this->parentId = $parent_id; }
	public function setVariationKey($variation_key) { $this->variationKey = $variation_key; }
	public function setVersion($version) { $this->version = $version; }

	// Static
	public function extractFromText($text) {}
	/**
	 * Takes a FileUpload object and retrieves any additional required data from the upload (such as width/height from an image)
	 * @param CWI_HTML_FORM_FileUpload $file_upload the file upload object
	 * @return null
	 */
	public function populatePropertiesFromUpload($file_upload) {}
}

?>