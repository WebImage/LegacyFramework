<?php

class CWI_HTML_FORM_FileUpload {
	var $m_fieldName;
	var $m_path;
	var $m_fileName;

	var $m_isFile = false;
	var $m_error = false;
	var $m_errorMsg;
	var $m_newFileName;
	var $m_wsPath; // Website Path
	var $m_fsPath; // File System Path
	
	var $_fileExtension;
	
	function __construct($field_name, $transfer_to_path, $change_file_name=null) {
		$this->setFieldName($field_name);
		$this->setPath($transfer_to_path);
		$this->setFileName($change_file_name);
	}
	
	function getFieldName() { return $this->m_fieldName; }
	function setFieldName($name) { $this->m_fieldName = $name; }
	function getFileName() { return $this->m_fileName; }
	function getFullFileName() { return $this->getFileName() . '.' . $this->getFileExtension(); }
	function setFileName($file) { $this->m_fileName = $file; }
	function getFileExtension() { return strtolower($this->_fileExtension); }
	function _setFileExtension($extension) { $this->_fileExtension = $extension; }
	function getPath() { return $this->m_path; } 
	function setPath($path) { $this->m_path = $path; }
	
	function isFile($is_file=null) {
		if (is_null($is_file)) return $this->m_isFile;
		else $this->m_isFile = $is_file;
	}
	
	function populateInfo() {} // Populates information about an uploaded image - generally this will be used by deriving classes
	
	function handleUpload() {
		if (!isset($_FILES[$this->getFieldName()])) return false;
		if (is_uploaded_file($_FILES[$this->getFieldName()]['tmp_name'])) {
			$this->isFile(true);
			
			/* ##### Separate file name and extension ##### */
		
			$file_explode		= explode('.', basename($_FILES[$this->getFieldName()]['name']));
		
			$base_name		= $file_explode[count($file_explode)-2];
			$file_extension		= $file_explode[count($file_explode)-1];
			
			$this->_setFileExtension($file_extension);
			
			/* ##### Rename file name if (strlen($rename_file_0) > 0) ##### */
			
			if (strlen($this->getFileName()) == 0) $this->setFileName($base_name);
			
			/*if (strlen($this->getFileName()) > 0) {*/
				$new_file_name	= $this->getFileName() . '.' . $this->getFileExtension();
			/*
			} else {
				$new_file_name	= $base_name . '.' . $file_extension;
			}*/
		
			/* ##### Build new path ##### */
		
			$new_fs_file_location	= $this->getPath() . $new_file_name;

			if (@move_uploaded_file($_FILES[$this->getFieldName()]['tmp_name'], $new_fs_file_location)) {
				$this->m_fsPath		= $new_fs_file_location;
				
				$replace_fs_path = ConfigurationManager::get('DIR_FS_HOME');
				
				// Check new path for asset manager path to see if maybe that's where this is being uploaded to:
				$asset_path = ConfigurationManager::get('DIR_FS_ASSETS_WMS');
				$asset_path_len = strlen($asset_path);
				
				if (substr($new_fs_file_location, 0, $asset_path_len) == $asset_path) { // We are dealing with an asset upload, adjust the folders accordingly
				
					$this->m_wsPath = str_replace($asset_path, ConfigurationManager::get('DIR_WS_ASSETS'), $new_fs_file_location);
					
				} else {
					if (ConfigurationManager::get('DIR_WS_HOME') != '/') {
						$replace_fs_path = str_replace(ConfigurationManager::get('DIR_WS_HOME'), '', $replace_fs_path);
					} else {
						$replace_fs_path = substr($replace_fs_path, 0, strlen($replace_fs_path)-1);
					}
					
					$this->m_wsPath		= str_replace($replace_fs_path, '', $new_fs_file_location);
				}

				$this->m_newFileName	= $new_file_name;
				$this->m_isFile		= true;
				$this->populateInfo();
				return true;
			} else {
				$this->setError('Unable to transfer uploaded file');
				return false;
			}
		} else {
			$this->setError('Unable to upload file');
			return false;
		}
	}
	
	function renameFile($new_name) {
		if ($this->isFile() && !$this->isError()) {
			$file_name_length = strlen($this->getFileName());
			$ext_length = strlen($this->getFileExtension());
			$full_length = $file_name_length + $ext_length + 1;
			
			$dir_fs = $this->getPath();
			$dir_ws = substr($this->m_wsPath, 0, strlen($this->m_wsPath)-$full_length);
			
			$current_file = $this->m_fsPath;
			$new_file_name = $new_name.'.'.$this->getFileExtension();
			$new_file_path = $dir_fs . $new_file_name;
			
			if (@rename($current_file, $new_file_path)) {
				$this->m_newFileName = $new_file_name;
				$this->m_fsPath = $new_file_path;
				$this->m_wsPath = $dir_ws . $new_file_name;
			} else {
				// CANNOT RENAME FILE
			}
		} else {
			return false;
		}
	}
	
	function setError($message) {
		$this->m_error = true;
		$this->m_errorMsg = $message;
	}
	function getError() { return $this->m_errorMsg; }
	function isError() { return $this->m_error; }
	
	function getFSPath() { return $this->m_fsPath; }
	function getWSPath() { return $this->m_wsPath; }
	
	function delete() {
		$file = $this->getFSPath();
		if (file_exists($file)) {
			@unlink($file);
		}
		return false;
	}
}
class FileUpload extends CWI_HTML_FORM_FileUpload {}
?>