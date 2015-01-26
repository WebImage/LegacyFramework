<?php

/**
 * Forces the browser to download a file
 * Should be constructed using the static initWithFileContents() or initWithFilePath()
 **/
class CWI_FILE_FileDownload {
	private $filePath;
	private $fileContents;
	private $downloadFileName;
	private $mimeType;
	
	public function __construct($download_file_name=null) {
		$this->setDownloadFileName($download_file_name);
	}
		
	public static function initWithFileContents($file_contents, $download_file_name=null) {
		$file_download = new CWI_FILE_FileDownload($download_file_name);
		$file_download->setFileContent($file_contents);
		return $file_download;
	}
	
	public static function initWithFilePath($file_path, $download_file_name=null) {
		$file_download = new CWI_FILE_FileDownload($download_file_name);
		$file_download->setFilePath($file_path);
		return $file_download;
	}
	
	public function getFileContents() { return $this->fileContents; }
	public function getFilePath() { return $this->filePath; }
	public function getDownloadFileName() {
		return $this->downloadFileName;
	}
	public function getMimeType() {
		if (!empty($this->mimeType)) {
			return $this->mimeType;
		} else {
			$file = $this->getDownloadFileName();
			$parts = explode('.', $file);
			$extension = array_pop($parts);
			return $this->guessContentTypeFromExtension($extension);
		}
	}
	
	public function setFileContent($file_content) { $this->fileContents = $file_content; }
	public function setFilePath($file_path) { $this->filePath = $file_path; }
	public function setDownloadFileName($download_file_name) { $this->downloadFileName = $download_file_name; }
	public function setMimeType($mime_type) { $this->mimeType = $mime_type; }
	
	private function guessContentTypeFromExtension($extension) {
		switch ($extension) {
			/*
			// Not yet supported
			case 'gif':
				return 'image/gif';
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				return 'image/jpeg';
			case 'png':
				return 'image/png';
			case 'tif':
			case 'tiff':
				return 'image/tiff';
			case 'xls':
			case 'xlsx':
				return 'application/vnd.ms-excel';
			case 'doc':
			case 'docx':
				return 'application/msword';
			case 'pdf':
				return 'application/pdf';
			*/
			default:
				return 'text/plain';
		}				
	}
	
	
	private function getContentTransferEncoding() {
		/**
		 * 7bit
		 * quoted-printable
		 * base64
		 * 8bit
		 * binary
		 */
		return '7bit';
	}
	/**
	 * Force the file to be downloaded to the browser
	 * @param int $quality The percent image quality
	 */	 
	public function forceDownload() {
		header('Content-Type: ' . $this->getMimeType());
		
		$file_contents = $this->getFileContents();
		$file_path = $this->getFilePath();
		
		$content_transfer_encoding = $this->getContentTransferEncoding();
		#header('Content-Length: ' . filesize($this->);
		
		$file = $this->getDownloadFileName();
		
		header('Content-Disposition: attachment; filename="' . $file . '"');
		if (!empty($content_transfer_encoding)) {
			header("Content-Transfer-Encoding: " . $content_transfer_encoding);
		}
		
		if (empty($file_contents) && !empty($file_path)) {
			header('Content-Length: ' . filesize($file_path));
			readfile($file_path);			
		} else {
			header('Content-Length: ' . strlen($file_contents));
			echo $file_contents;
		}

	}
}

?>