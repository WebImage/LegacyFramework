<?php

define('WATERMARK_POS_CENTER',	1);
define('WATERMARK_POS_TILE',	2);

class ImageResource {
	var $_fileSrc, $_width, $_height, $_imageResource;
	function __construct($file_src, $width, $height) {
		$this->_fileSrc	= $file_src;
		$this->_width	= $width;
		$this->_height	= $height;
	}
	function getFileSrc() { return $this->_fileSrc; }
	function getWidth() { return $this->_width; }
	function getHeight() { return $this->_height; }
	function getImageResource() { return $this->_imageResource;  }
	
	function setImageResource($image_resource) {
		$this->_imageResource = $image_resource;
	}
	function setWidth($width) { $this->_width = $width; }
	function setHeight($height) { $this->_height = $height; }
	
	function destroyImageResource() {
		@imagedestroy($this->getImageResource());
	}
}

class ImageManipulator {
	var $_image;
	var $_error = false;
	var $_width;
	var $_height;
	var $_cache = false;
	var $_cacheExpires; // This is a GMT date, but is not currently implemented
	var $_forceDownload = false;
	var $_downloadFileName = 'image.jpg';
	
	/**
	 * Constructor
	 * @param string $file_src The full system path to the file to be manipulated
	 */
	function __construct($file_src) {
		if ($image = $this->_getFile($file_src)) {
			$this->_image = $image;
		} else {
			$this->_error = true;
		}
	}
	/**
	 * Get image resource width
	 * @return int
	 */
	public function getWidth() { return $this->_image->getWidth(); }
	/**
	 * Get image resource height
	 * @return int
	 */
	public function getHeight() { return $this->_image->getHeight(); }
	/**
	 * Get whether or not the image should be forced to be downloaded - instead of displayed
	 * @return boolean True if the image should be forced to be downloaded; False (default) if the image should simply be displayed
	 */
	public function getForceDownload() { return $this->_forceDownload; }
	public function getDownloadFileName() { return $this->_downloadFileName; }
	
	/**
	 * Determine whether the file should be forced to be downloaded - instead of displayed
	 * @param boolean $force_download whether to force download
	 * @return void
	 */
	public function setForceDownload($force_download) { $this->_forceDownload = $force_download; }
	/**
	 * Set the file name to be used when forcing the download
	 * @param string $file_name simple file name that will be used for download
	 */
	public function setDownloadFileName($file_name) { $this->_downloadFileName = $file_name; }
	
	
	/**
	 * Add a watermark image to the existing file resource
	 * @param string $file_src The full system path to the file to be manipulated
	 * @param int $opacity The percent opacity that the watermark should be displayed; 0=completely transparent; 100=fully opaque
	 * @param int $pos How the watermark should be positioned; WATERMARK_POS_CENTER=1=Middle of the image; WATERMARK_POS_TILE=2=Repeated over and over again based on watermark width and height
	 * @param boolean $display_image_on_error Whether the main image should still be displayed, even if the watermark fails.  True means the image will still be displayed; False means the main image will not be displayed
	 * @return void
	 */
	public function addWatermark($file_src, $opacity=50, $pos=WATERMARK_POS_TILE, $display_image_on_error=false) {
		if (!$this->_error && $watermark = $this->_getFile($file_src)) {
			switch ($pos) {
				case WATERMARK_POS_CENTER:
					$position_x = round(($this->_image->getWidth() / 2) - ($watermark->getWidth() / 2));
					$position_y = round(($this->_image->getHeight() / 2) - ($watermark->getHeight() / 2));
					imagecopymerge($this->_image->getImageResource(), $watermark->getImageResource(), $position_x, $position_y, 0, 0, $watermark->getWidth(), $watermark->getHeight(), $opacity);
					break;
				case WATERMARK_POS_TILE:
				default:
					$position_y = 0;

					while ($position_y < $this->_image->getWidth()) {
						$position_x = 0;
						while ($position_x < $this->_image->getWidth()) {
							imagecopymerge($this->_image->getImageResource(), $watermark->getImageResource(), $position_x, $position_y, 0, 0, $watermark->getWidth(), $watermark->getHeight(), $opacity);
							$position_x += ($watermark->getWidth() + 1);
						}
						$position_y += ($watermark->getHeight() + 1);
					}
					
					break;
			}
			$watermark->destroyImageResource();
			return true;
		} else {
			if ($display_image_on_error) {
				return false;
			} else {
				$this->_image = null;
				$this->_error = true;
			}
		}
	}
	/**
	 * Setup an image resource based on a file
	 * @param string $file_src The full system path to the file
	 * @return resource The resource locator for the image created.  Returns false if the extension is not recognized.
	 */
	protected function _getImageResourceFromFile($file_src) {
		$extension = strtolower(substr($file_src, -4));

		if ($extension == '.jpg') {
			return imagecreatefromjpeg($file_src);
		} else if ($extension == '.gif') {
			return imagecreatefromgif($file_src);
		} else if ($extension == '.png') {
			return imagecreatefrompng($file_src);
		} else {
			return false;
		}
	}
	
	/**
	 * Retrieve image file and return image resource
	 * @param string $file_src Full system path to image
	 * @return ImageResource
	 */
	protected function _getFile($file_src) {
		if (file_exists($file_src)) {
			list($width, $height) = getimagesize($file_src);
			if ($image_resource = $this->_getImageResourceFromFile($file_src)) {
				$image = new ImageResource($file_src, $width, $height);
				$image->setImageResource($image_resource);
				return $image;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	/**
	 * Resize and replace the current image resource
	 * @param int $width The target resized width
	 * @param int $height The target resized height
	 * @return void
	 */
	public function resize($width=null, $height=null) {
		
		if (!$this->_error) {
			if (substr($width, -1) == '%') $width = round($this->_image->getWidth() * intval($width) / 100);
			if (substr($height, -1) == '%') $height = round($this->_image->getHeight() * intval($height) / 100);
		
			if (empty($width)) { // Auto create width value
				if (!empty($height)) { // If height not null then auto calculate portion
					$width = round( $height / $this->_image->getHeight() * $this->_image->getWidth() );
				} else { // Otherwise, use original width
					$width = $this->_image->getWidth();
				}
			}
			if (empty($height)) {
				if (!empty($width)) {
					$height = round( $width / $this->_image->getWidth() * $this->_image->getHeight() );
				} else {
					$height = $this->_image->getHeight();
				}
			}
			
			$resize = imagecreatetruecolor($width, $height);
			
			$dst_x = 0;
			$dst_y = 0;
			$src_x = 0;
			$src_y = 0;
			$dst_w = $width;
			$dst_h = $height;
			$src_w = $this->_image->getWidth();
			$src_h = $this->_image->getHeight();
		
			imagecopyresampled(
				$resize, 
				$this->_image->getImageResource(),
				// destination coordinates
				$dst_x,
				$dst_y,
				// source coordinates
				$src_x,
				$src_y,
				// destination dimensions
				$dst_w,
				$dst_h,
				// source dimensions
				$src_w,
				$src_h);
			$this->_image->setWidth($width);
			$this->_image->setHeight($height);
			$this->_image->setImageResource($resize);
		}
	}
	
	public function scaleAndCrop($width, $height, $upscale=false) {
		
		if (!$this->_error) {
			
			$src_x = 0;
			$src_y = 0;
			$src_w = $this->_image->getWidth();
			$src_h = $this->_image->getHeight();
			
			$src_scale = $src_w / $src_h;
			$dst_scale = $width / $height;
			
			if ($src_scale > $dst_scale) { // Source wider
				$w = $src_h * $width / $height;
				$src_x = ceil( ($src_w - $w) / 2);
				$src_w = $w;
			} else if ($src_scale < $dst_scale) { // Source taller
				$h = $src_w * $height / $width;
				$src_y = ceil( ($src_h - $h) / 2);
				$src_h = $h;
			}
			
			// Make sure that if $upscale is false that the source image is greater than the new size
			if ( $upscale || (!$upscale && $width <= $src_w && $height <= $src_h)) {
				
				$target_image = imagecreatetruecolor($width, $height);
				imagecopyresampled($target_image, $this->_image->getImageResource(), 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h);
				
				$this->_image->setWidth($width);
				$this->_image->setHeight($height);
				$this->_image->setImageResource($target_image);
				
			}
			
		}
	}
	
	public function crop($x, $y, $width, $height) {
		if (!$this->_error) {
			if (is_numeric($height) && $height > $this->_image->getHeight()) $height = $this->_image->getHeight();
			if (substr($width, -1) == '%') $width = round($this->_image->getWidth() * intval($width) / 100);
			if (substr($height, -1) == '%') $height = round($this->_image->getHeight() * intval($height) / 100);
			
			// Check if $x has special keywords and process them accordingly
			$x = strtolower($x);
			if ($x == 'left') $x = 0;
			else if ($x == 'center') $x = round($this->_image->getWidth() / 2) - round($width / 2);
			else if ($x == 'right') $x = $this->_image->getWidth() - $width;
			
			// Check if $y has special keywords and process them accordingly
			$y = strtolower($y);
			if ($y == 'top') $y = 0;
			else if ($y == 'middle') $y = round($this->_image->getHeight() / 2) - round($height / 2);
			else if ($y == 'bottom') $y = $this->_image->getHeight() - $height;

			$resize = imagecreatetruecolor($width, $height);
				
			$dst_x = 0;
			$dst_y = 0;
			$dst_w = $width;
			$dst_h = $height;
			
			$src_x = $x;
			$src_y = $y;
			$src_w = $width;
			$src_h = $width;
			
			imagecopyresampled(
				$resize, 
				$this->_image->getImageResource(),
				$dst_x,
				$dst_y,
				$src_x,
				$src_y,
				$dst_w,
				$dst_h,
				$src_w,
				$src_h);
			$this->_image->setWidth($width);
			$this->_image->setHeight($height);
			$this->_image->setImageResource($resize);
		}
	}
	
	/**
	 * Acts as a setter/getter for $_cache;
	 */
	public function cache($cache=null) {
		if (is_null($cache)) {
			return $this->_cache;
		} else {
			$this->_cache = $cache;
		}
	}
	
	/**
	 * Display the current image resource
	 * @param int $quality The percent image quality
	 */	 
	public function display($quality=80) {
		if (!$this->_error) {
			if ($this->cache()) {
				$cache_seconds = 60 * 60;
				#header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($this->_image->getFileSrc()) ) .' GMT');
				
				/*
				session_cache_limiter('public');
				header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($this->_image->getFileSrc())).' GMT'); 
				header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_seconds) . ' GMT');
				header('Cache-Control: public, max-age='.$cache_seconds);
				header('Pragma: public');
				*/
				

#				Cache-Control (no-store,no-cache,must-revalidate,post-check=0, pre-check=
#				Pragma: no-cache
			}
			
			header('Content-Type: image/jpeg');
			#header('Content-Length: ' . filesize($this->_image->getFileSrc()));
			
			ob_start();
			imagejpeg($this->_image->getImageResource(), null, $quality);
			header('Content-Length: ' . ob_get_length());
			
			if ($this->getForceDownload()) {
				$file = $this->getDownloadFileName();
				header("Content-Disposition: attachment; filename=$file");
				header("Content-Transfer-Encoding: binary");
			}

			/*
			if ($this->cache()) {
				$cache_seconds = 60 * 60;
				header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_seconds) . ' GMT');
				header('Cache-Control: public, max-age='.$cache_seconds);
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($this->_image->getFileSrc()) ) .' GMT');
				
				header('Pragma: public');
#				Cache-Control (no-store,no-cache,must-revalidate,post-check=0, pre-check=
#				Pragma: no-cache
			}
			*/
			ob_end_flush();

			#imagejpeg($this->_image->getImageResource(), ConfigurationManager::get('DIR_FS_TMP') . 'test.jpg', $quality);
			$this->_image->destroyImageResource();
			
			@imagedestroy($this->_image->getImageResource());
		}
	}
	
	/**
	 * Writes the current image resource to a file
	 * @param string $full_write_path 
	 * @param int $quality The percent image quality
	 */	 
	public function writeToFile($full_write_path, $quality=80) {
		if (!$this->_error) {
			imagejpeg($this->_image->getImageResource(), $full_write_path, $quality);
		}
	}
	
	/**
	
	
	COPIED FROM LV TERRITORY
	
	
	
	 * Added versionExists($new_file_name) 1/28/2009 
	 * NOTE: This function is not currently in the AthenaCMS version
	 * Checks to see if a version of the $new_file_name already exists. 
	 * @param string $file_name currently this can only handle a file name, but can easily be modified to allow a full path
	
	function versionExists($new_file_name) {
		$file_src	= $this->_image->getFileSrc();
		$dir_parts	= explode('/', $file_src); // Split directories
		$file_name	= $dir_parts[count($dir_parts)-1]; // Get the last index of $dir_parts; i.e. the file name

		$new_file_parts = explode('.', $new_file_name);

		if (count($new_file_parts) <= 1) { // There is not extension, automatically append the original one here
			$file_parts	= explode('.', $file_name);
			$file_ext	= $file_parts[count($file_parts)-1];
			$new_file_name	= $new_file_name . '.' . $file_ext;
		}
		
		array_pop($dir_parts); // Remove file name from $dir_parts stack
		$file_path = implode('/', $dir_parts) . '/'; // Put file path back together (sans file name)
		$new_file_path = $file_path . $new_file_name;
		return (file_exists($new_file_path));
	}
	
	/**
	 * Added createVersion() 1/28/2009 
	 * NOTE: This function is not currently in the AthenaCMS version
	 * Allows a version of the image resource to be saved to file
	 * @param string $file_name currently this can only handle a file name, but can easily be modified to allow a full path if necessary in the future
	 
	
	function createVersion($new_file_name, $quality=80) {
		$file_src	= $this->_image->getFileSrc();
		$dir_parts	= explode('/', $file_src); // Split directories
		$file_name	= $dir_parts[count($dir_parts)-1]; // Get the last index of $dir_parts; i.e. the file name
		
		$new_file_parts = explode('.', $new_file_name);

		if (count($new_file_parts) <= 1) { // There is not extension, automatically append the original one here
			$file_parts	= explode('.', $file_name);
			$file_ext	= $file_parts[count($file_parts)-1];
			$new_file_name	= $new_file_name . '.' . $file_ext;
		}
		
		array_pop($dir_parts); // Remove file name from $dir_parts stack
		$file_path = implode('/', $dir_parts) . '/'; // Put file path back together (sans file name)
		
		imagejpeg($this->_image->getImageResource(), $file_path . $new_file_name, $quality);
	}
	*/
}

?>
