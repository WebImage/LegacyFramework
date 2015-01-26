<?php

class EncryptionLogic {
	/*
	public static function encrypt($string) {
		return md5($string);
		return $string;
	}
	
	public static function decrypt($string) {
		return $string;
	}
	*/
	
	/**
	 * Encrypts text using the supplied key
	 * @return string Encrypted text
	 */
	public static function encryptWithKey($key, $normal_text) {
		// Get initialization vector size
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		
		// Initialization vector
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		
		// Maximum length of key
		$max_key_size = mcrypt_get_key_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		
		// Insure that provided key does not exceed the maximum number of characters allowed for this specific cipher
		if (strlen($key) > $max_key_size) $key = substr($key, 0, $max_key_size);
		
		// Encrypt string
		$crypt_text = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $normal_text, MCRYPT_MODE_ECB, $iv);
		
		return base64_encode($crypt_text);
	}
	
	/**
	 * Decrypts encrytped text using the supplied key
	 * @return string Decrypted text
	 */
	public static function decryptWithKey($key, $enc_text) {
		$enc_text = base64_decode($enc_text);
		// Get initialization vector size
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		
		// Initilization vector
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		
		// Maximum length of key
		$max_key_size = mcrypt_get_key_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		
		// Insure that provided key does not exceed the maximum number of characters allowed for this specific cipher
		if (strlen($key) > $max_key_size) $key = substr($key, 0, $max_key_size);
		
		// Return decrypted string
		return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $enc_text, MCRYPT_MODE_ECB, $iv);
	}
}

?>