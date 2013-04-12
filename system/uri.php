<?php namespace System;

/**
 * Nano
 *
 * Just another php framework
 *
 * @package		nano
 * @link		http://madebykieron.co.uk
 * @copyright	http://unlicense.org/
 */

use ErrorException;
use OverflowException;

class Uri {

	/**
	 * The current uri
	 *
	 * @var string
	 */
	public static $current;

	/**
	 * Get a path relative to the application
	 *
	 * @param string
	 * @return string
	 */
	public static function to($uri) {
		if(strpos($uri, '://')) return $uri;

		$base = Config::app('url', '');

		if($index = Config::app('index', '')) {
			$index .= '/';
		}

		return rtrim($base, '/') . '/' . $index . ltrim($uri, '/');
	}

	/**
	 * Get full uri relative to the application
	 *
	 * @param string
	 * @return string
	 */
	public static function full($uri, $secure = null) {
		if(strpos($uri, '://')) return $uri;

		if( ! is_null($secure)) {
			$scheme = $secure ? 'https://' : 'http://';
		}
		else {
			$scheme = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
		}

		return $scheme . $_SERVER['HTTP_HOST'] . static::to($uri);
	}

	/**
	 * Get full secure uri relative to the application
	 *
	 * @param string
	 * @return string
	 */
	public static function secure($uri) {
		return static::full($uri, true);
	}

	/**
	 * Get the current uri string
	 *
	 * @return string
	 */
	public static function current() {
		if(is_null(static::$current)) static::$current = static::detect();

		return static::$current;
	}

	/**
	 * Try and detect the current uri
	 *
	 * @return string
	 */
	public static function detect() {
		$try = array('REQUEST_URI', 'PATH_INFO', 'ORIG_PATH_INFO');

		foreach($try as $method) {
			if( ! array_key_exists($method, $_SERVER)) continue;

			if($uri = filter_var($_SERVER[$method], FILTER_SANITIZE_URL)) {
				// make sure the uri is not malformed and return the pathname
				if($uri = parse_url($uri, PHP_URL_PATH)) {
					return static::format($uri);
				}

				// woah jackie, we found a bad'n
				throw new ErrorException('Malformed URI');
			}
		}

		throw new OverflowException('Uri was not detected. Make sure the PATH_INFO or REQUEST_URI is set.');
	}

	/**
	 * Format the uri string remove any malicious
	 * characters and relative paths
	 *
	 * @param string
	 * @return string
	 */
	public static function format($uri) {
		// Remove all characters except letters, digits and $-_.+!*'(),{}|\\^~[]`<>#%";/?:@&=.
		$uri = filter_var(rawurldecode($uri), FILTER_SANITIZE_URL);

		// remove script path/name
		$uri = static::remove_script_name($uri);

		// remove the relative uri
		$uri = static::remove_relative_uri($uri);

		// return argument if not empty or return a single slash
		return trim($uri, '/') ?: '/';
	}

	/**
	 * Remove a value from the start of a string
	 * in this case the passed uri string
	 *
	 * @param string
	 * @param string
	 * @return string
	 */
	public static function remove($value, $uri) {
		// make sure our search value is a non-empty string
		if(is_string($value) and strlen($value)) {
			// if the search value is at the start sub it out
			if(strpos($uri, $value) === 0) {
				$uri = substr($uri, strlen($value));
			}
		}

		return $uri;
	}

	/**
	 * Remove the SCRIPT_NAME from the uri path
	 *
	 * @param string
	 * @return string
	 */
	public static function remove_script_name($uri) {
		return static::remove(Arr::get($_SERVER, 'SCRIPT_NAME'), $uri);
	}

	/**
	 * Remove the relative path from the uri set in the application config
	 *
	 * @param string
	 * @return string
	 */
	public static function remove_relative_uri($uri) {
		// remove base url
		if($base = Config::app('url')) {
			$uri = static::remove(rtrim($base, '/'), $uri);
		}

		// remove index
		if($index = Config::app('index')) {
			$uri = static::remove('/' . $index, $uri);
		}

		return $uri;
	}

}