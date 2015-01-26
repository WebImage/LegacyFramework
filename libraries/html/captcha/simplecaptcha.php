<?php

class CWI_HTML_CAPTCHA_SimpleCaptcha {
	private $canvasWidth, $canvasHeight;
	private $numChars;
	private $captchaString;
	private $backgroundImage = array();

	function __construct($width, $height, $num_chars=5) {
		/**
		 *	Configure Captcha
		 */
		$this->canvasWidth	= $width; // Canvas Width
		$this->canvasHeight	= $height; // Canvas Height
		$this->numChars	= $num_chars;
		$this->generateString();
	}
	
	function setCaptchaString($string) {
		$this->captchaString = $string;
		$this->numChars = strlen($string);
	}
	function getCaptchaString() { return $this->captchaString; }
	
	function generateString() {
		$string = '';
		for ($i=0; $i < $this->numChars; $i++) {
			$string	.= chr(rand(0, 25) + 65); // Use all uppercase alphabetical letters ORD values 65-90
		}
		$this->setCaptchaString($string);
	}
	function addBackgroundImage($img_path) {
		$this->backgroundImage[] = $img_path;
	}
	function render() {
		// Initialize image & colors
		
		#header('Content-type: image/gif');
		#imagegif($img);
		#imagedestroy($img);

		$im		= imagecreate($this->canvasWidth, $this->canvasHeight);
		$color_bg	= imagecolorallocate($im, 0, 0, 0);
		$color_text	= imagecolorallocate($im, 255, 255, 255);
		
		// Font Specifications
		$font_size	= 5; // PHP font size to use
		$font_width	= imagefontwidth($font_size); // Font width
		$font_height	= imagefontheight($font_size); // Font height
		$top_margin	= $this->canvasHeight - $font_height;
		$min_y_pos	= ceil($top_margin/3) * -1;
		$max_y_pos	= ceil($top_margin/3) - 1;
			
		// Fill Image Background
		#imagefill($im, 1, 1, $color_bg);
		
		// Initialize start position
		$cur_x_pos	= 0;
		$increment_x	= $this->canvasWidth/($this->numChars+1);
		$offset_letter	= floor($font_width / 2);
		$session_string	= '';
		
		// If backgrounds are available, use them instead of black background
		if (count($this->backgroundImage) > 0) {
			$random_image_num = rand(0, count($this->backgroundImage)-1);
			$random_image = $this->backgroundImage[$random_image_num];
			$img = imagecreatefromgif($random_image);
			imagecopymerge($im, $img, 0, 0, 1, 1, $this->canvasWidth, $this->canvasHeight, 90);
			imagedestroy($img);
		}
		
		imageline($im, 1, ($top_margin/2) + rand($min_y_pos, $max_y_pos), $this->canvasWidth, ($top_margin/2) + rand($min_y_pos, $max_y_pos), $color_text);
		imagearc($im, 20, rand(0, $this->canvasHeight), rand(17, 22), rand(17, 22), 0, 360, $color_text);

		#imageline($im, 1, ($top_margin/2) + rand($min_y_pos, $max_y_pos), $this->canvasWidth, ($top_margin/2) + rand($min_y_pos, $max_y_pos), $color_text);

		/**
		 * DRAW LETTERS
		 */
		$captcha_string	= $this->getCaptchaString();
		
		for ($i=0; $i < strlen($captcha_string); $i++) {
			
			$letter		= $captcha_string[$i]; //chr(rand(0, 25) + 65); // Use all uppercase alphabetical letters ORD values 65-90
			
			$cur_x_pos += $increment_x; // Increase cur_x_pos to the center of this starting x position
			$this_x_pos	= $cur_x_pos - $offset_letter; // Take center position of left and subtract offset to locate the left position of this letter
			$this_y_pos = floor(($top_margin)/2) + rand($min_y_pos, $max_y_pos);
			
			/*
			// For future rotation
			$im_letter		= imagecreate($font_width+100, $font_height+100);
			$font_color_bg		= imagecolorallocate($im_letter, 0, 0, 0);
			$font_color_text	= imagecolorallocate($im_letter, 255, 255, 255);
			imagechar($im_letter, 5, 1, 1, $letter, $font_color_text);
			*/
			
			imagechar($im, $font_size, $this_x_pos, $this_y_pos, $letter, $color_text);
			
			
			#$im_letter = imagerotate($im_letter, 45, 0);
			#imagecopymerge($im, $im_letter, $this_x_pos, $this_y_pos, 1, 1, $font_width, $font_height, 100);
			#imagedestroy($im_letter);
			
			$session_string .= $letter;
		}
		
		SessionManager::set('ahctpac', md5($session_string));
		
		header('Content-type: image/gif');
		imagegif($im);
		imagedestroy($im);
	}
	/**
	 * Static
	 */
	function validateText($text) {
		if ($session_captcha_string = SessionManager::get('ahctpac')) {
			if (md5(strtoupper($text)) == $session_captcha_string) return true;
			else return false;
		} else return false;
	}
}
	
?>