<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

class Input {

	private static $time = null;
	private static $host = null;
	private static $ip = null;
	private static $countryName = null;
	private static $path = null;
	private static $relativePath = null;

	private function __construct() {}

	public static function __callStatic($name, $args) {
		return Request::getInstance($name);
	}

	public static function getTime() {
		if (self::$time == 0) {
			self::$time = self::server()->getInt('REQUEST_TIME', time());
		}
		return self::$time;
	}

	public static function getHost() {
		if (is_null(self::$host)) {
			self::$host = self::server()->getString('HTTP_HOST');
		}
		return self::$host;
	}

	public static function getClientIP() {
		if (is_null(self::$ip)) {
			self::$ip = self::server()->getString('REMOTE_ADDR', '127.0.0.1');
		}
		return self::$ip;
	}

	public static function getClientCountryName() {
		if (is_null(self::$countryName)) {
			if (function_exists('geoip_country_name_by_name')) {
				// remove ipv6 prefix
				$ip = ltrim(self::getClientIP() , ':a-f');
				if (!empty($ip)) {
					// let's ignore any lookup errors
					$errorReporting = error_reporting(E_ALL ^ E_NOTICE);
					restore_error_handler();
					self::$countryName = geoip_country_name_by_name($ip) ? : '';
					set_error_handler('ErrorHandler');
					error_reporting($errorReporting);
				}
			}
		}
		return self::$countryName;
	}

	public static function getPath() {
		if (is_null(self::$path)) {
			$directory = dirname(self::server()->getString('SCRIPT_NAME'));
			self::$path = 'http'.(!self::server()->isString('HTTPS') ? '' : 's').'://'
				.self::getHost().($directory == '/' ? '' : $directory).'/';
		}
		return self::$path;
	}

	public static function getRelativePath() {
		if (is_null(self::$relativePath)) {
			$directory = dirname(self::server()->getString('SCRIPT_NAME'));
			self::$relativePath = ($directory == '/' ? '' : $directory) . '/';
		}
		return self::$relativePath;
	}
}

?>