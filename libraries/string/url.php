<?php

/**
 * Utility class for handling URL manipulation.  Not using PHP's parse_url() because it is too strict and does not always work with malformed URLs
 */
class CWI_STRING_Url {
	/**
	 * @var string
	 */
	private $scheme;
	/**
	 * @var string
	 */
	private $host;
	/**
	 * @var string
	 */
	private $port;
	/**
	 * @var string
	 */
	private $user;
	/**
	 * @var string
	 */
	private $pass;
	/**
	 * @var string
	 */
	private $path;
	/**
	 * @var string
	 */
	private $fragment;
	/**
	 * @var Dictionary
	 */
	private $query;
	
	/**
	 * TODO: Add support for allowMultiQueryParams
	 * Whether to allow multiple query parameter names WITHOUT the special "[]" PHP designation, e.g. if false then ?key=Value1&key=Value2 will result in ?key=Value2 (since Value2 would override the earlier Value1
	 * @var bool
	 */
	private $allowMultiQueryParams = false;
	
	function __construct($url) {
		
		$this->setUrl($url);
			
	}
	
	public function setUrl($url) {
		
		// Fragment
		$parts = explode('#', $url, 2);
		list($url, $fragment) = (count($parts) == 2) ? $parts : array($parts[0], '');
		
		// Scheme
		$parts = explode('://', $url, 2);
		list($scheme, $url) = (count($parts) == 2) ? $parts : array('', $parts[0]);
		
		// Path & Query
		$parts = explode('?', $url, 2);
		list($url, $query) = (count($parts) == 2) ? $parts : array($parts[0], '');
		
		// Host
		$parts = explode('/', $url, 2);
		$n_parts = count($parts);

		if ($n_parts == 2) {
			$host = $parts[0];
			$url = '/' . $parts[1];
		} else if ($n_parts == 1) {
			$host = $parts[0];
			$url = '';
		} else {
			$host = $parts[0];
			$url = '';
		}
		
		// Username/Password
		$parts = explode('@', $host, 2);
		list($user_pass, $host) = (count($parts) == 2) ? $parts : array('', $parts[0]);
		if (strlen($user_pass) > 0) {
			list($user, $pass) = explode(':', $user_pass);
		}
		
		// Port
		$parts = explode(':', $host, 2);
		list($host, $port) = (count($parts) == 2) ? $parts : array($host, '');
		
		// Path & Query
		$path = $url;
		
		$this->setScheme($scheme);
		$this->setUser($user);
		$this->setPass($pass);
		$this->setHost($host);
		$this->setPort($port);
		$this->setPath($path);
		$this->setQueryString($query);
		$this->setFragment($fragment);
	}
	function __toString() {
		
		$scheme = $this->getScheme();
		$user = $this->getUser();
		$pass = $this->getPass();
		$host = $this->getHost();
		$port = $this->getPort();
		$path = $this->getPath();
		$query = $this->getQueryString();
		$fragment = $this->getFragment();
		
		$url = '';
		if (strlen($host) > 0) {
			
			if (strlen($scheme) > 0) $url .= $scheme. '://';
			
			if (strlen($user) > 0 || strlen($pass) > 0) {
				$url .= sprintf('%s:%s@', $user, $pass);
			}
			
			$url .= $host;
			if (strlen($port) > 0) $url .= ':' . $port;
		}
		
		if (!empty($url) && empty($path) && !empty($query)) $path .= '/';
		$url .= $path;
		
		if (!empty($query)) $url .= '?' . $query;
		
		if (strlen($fragment) > 0) $url .= '#' . $fragment;
		
		return $url;
	}
	
	public function getQueryString() {
		$query = array();
		$d_query = $this->getQuery()->getAll();
		while ($q = $d_query->getNext()) {
			$name = $q->getKey();
			$value = $q->getDefinition();
			// If value is an array then create the notation to reflect that
			if (is_array($value)) {
				foreach($value as $v) {
					$v = urlencode($v);
					$query[] = sprintf('%s[]=%s', $name, $v);
				}
			} else {
				$value = urlencode($value);
				$query[] = sprintf('%s=%s', $name, $value);
			}
		}
		return implode('&', $query);
	}
	/**
	 * @return string
	 */
	public function getScheme() { return $this->scheme; }
	/**
	 * @return string
	 */
	public function getHost() { return $this->host; }
	/**
	 * @return string
	 */
	public function getPort() { return $this->port; }
	/**
	 * @return string
	 */
	public function getPath() { return $this->path; }
	/**
	 * @return Dictionary
	 */
	public function getQuery() { return $this->query; }
	/**
	 * @return string
	 */
	public function getUser() { return $this->user; }
	/**
	 * @return string
	 */
	public function getPass() { return $this->pass; }
	/**
	 * @return string
	 */
	public function getFragment() { return $this->fragment; }
	/**
	 * @param string $scheme
	 * @return CWI_STRING_Url
	 */
	public function setScheme($scheme) {
		$this->scheme = $scheme;
		return $this;
	}
	/**
	 * @param string $host
	 * @return CWI_STRING_Url
	 */
	public function setHost($host) {
		$this->host = $host;
		return $this;
	}
	/**
	 * @param string $port
	 * @return CWI_STRING_Url
	 */
	public function setPort($port) {
		$this->port = $port;
		return $this;
	}
	/**
	 * @param string $user
	 * @return CWI_STRING_Url
	 */
	public function setUser($user) {
		$this->user = $user;
		return $this;
	}
	/**
	 * @param string $pass
	 * @return CWI_STRING_Url
	 */
	public function setPass($pass) {
		$this->pass = $pass;
		return $this;
	}
	/**
	 * @param string $path
	 * @return CWI_STRING_Url
	 */
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}
	/**
	 * @param string $query_string
	 * @return CWI_STRING_Url
	 */
	public function setQueryString($query) {
		
		$this->query = new Dictionary();
		
		// Build query
		if (strlen($query) > 0) {
				
			$url_parts = explode('&', $query);
			foreach($url_parts as $url_part) {
		
				@list($name, $value) = explode('=', $url_part, 2);
				
				$is_array = false;
				if (substr($name, -2) == '[]') {
					$name = substr($name, 0, -2);
					$is_array = true;
					$value = array($value);
				}
				
				if (($is_array || $this->allowMultiQueryParams) && $this->getQuery()->isDefined($name)) {
					
					$existing = $this->getQuery()->get($name);
					if (!is_array($existing)) $existing = array($existing);
					if (!is_array($value)) $value = array($value);
					$value = array_merge($existing, $value);
				}
				echo '<Hr/>Name: ' . $name . '<br /><pre>';print_r($value);
				$this->setQueryValue($name, $value);
			}
		}
		
		return $this;
	}
	
	public function getQueryValue($name) {
		return $this->getQuery()->get($name);
	}
	public function setQueryValue($name, $value) {
		$this->getQuery()->set($name, $value);
	}
	public function addQueryValue($name, $value) {
		
		$values = array();
		if ($this->getQuery()->isDefined($name)) {
			$values = $this->getQuery()->get($name);
			if (!is_array($values)) $values = array($values);
		}
		if (is_array($value)) $values = array_merge($values, $value);
		else $values[] = $value;
		
		$this->setQueryValue($name, $values);
		
	}
	/**
	 * @param sring $fragment
	 * @return CWI_STRING_Url
	 */
	public function setFragment($fragment) {
		$this->fragment = $fragment;
		return $this;
	}

}