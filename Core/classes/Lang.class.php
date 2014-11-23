<?php
/**
 * Copyright (C) 2013-2014 David PHAM-VAN
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

class Lang {
	const i18n = "i18n";

	protected static $words = array();
	protected static $baselang;



	public static function load($filename, $lang = NULL) {
		if (!is_file($filename))
			throw new Exception("Can't find translation file \"${filename}\"");

		$words = json_decode(file_get_contents($filename), true);
		self::setWords($words, $lang);
	}


	public static function setWords($words, $lang = NULL, $suffix = NULL) {
		foreach($words as $token => $word) {
			if (is_array($word)) {
				self::setWords($word, $lang, $token . ".");
			} else {
				self::set($suffix.$token, $word, $lang);
			}
		}
	}


	public static function setLang($lang) {
		if (self::$baselang == $lang)
			return;

		self::$baselang = $lang;

		if (LANG_AUTOLOAD) {
			if (($pos = strpos($lang, "_")) !== false) {
				$slang = substr($lang, 0, $pos);
				foreach (Plugins::findAll(self::i18n . DIRECTORY_SEPARATOR . $slang . ".json") as $filename) {
					self::load($filename, $lang);
				}
			}

			foreach (Plugins::findAll(self::i18n . DIRECTORY_SEPARATOR . $lang . ".json") as $filename) {
				self::load($filename, $lang);
			}
		}
	}


	public static function getLang() {
		return self::$baselang;
	}


	public static function getLangHtml() {
		return str_replace("_", "-", self::getLang());
	}


	public static function get($token, $lang = NULL, $context = NULL) {
		if ($lang == NULL)
			$lang = self::$baselang;

		if(self::exists($token)) {
			if(!is_null($context)) {
				return sprintf(self::getByCount($token, $context, $lang), $context);
			} else {
				if(is_null($t = self::getByLang($token, $lang)))
					$t = self::getByLang($token, LANG_DEFAULT);
				return $t;
			}
		}

		if (DEBUG) {
			self::set($token, str_replace(array("_", "."), array(" ", " "), $token));
		}
		return $token;
	}


	public static function getTextByCount($text, $count) {
		preg_match('/\((.+)\)/', $text, $regs, PREG_OFFSET_CAPTURE);
		$fill = '';
		$x = explode('|', $regs[1][0]); // If there are different notations for singular and plural
		if ((Int)$count != 1) {
			$fill = $x[count($x) - 1]; // last element
		}
		elseif (count($x) > 1) {
			$fill = $x[0];
		}
		return substr_replace($text, $fill, $regs[0][1], strlen($regs[0][0]));
	}


	public static function getByCount($token, $count, $lang = NULL) {
		return self::getTextByCount(self::get($token, $lang), $count);
	}


	public static function getByLang($token, $lang)
	{
		if (isset(self::$words[$token][$lang])) {
			return self::$words[$token][$lang];
		}
		return null;
	}


	public static function set($token, $value, $lang = NULL) {
		if (is_null($token))
			throw new Exception("token cannot be null");

		if ($lang == NULL) {
			$lang = self::$baselang;
		}

		self::$words[$token][$lang] = $value;
	}


	public static function exists($token) {
		return array_key_exists($token, self::$words);
	}


	public static function detect($supported_list = NULL) {
		$languages = array();
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$languagesQ = array();
			$languageList = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$languageRanges = explode(',', trim($languageList));
			foreach ($languageRanges as $languageRange) {
				if (preg_match('/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/', trim($languageRange), $match)) {
					if (!is_array($supported_list) || in_array($supported_list, strtolower($match[1]))) {
						if (!isset($match[2])) {
							$match[2] = '1.0';
						} else {
							$match[2] = (string) floatval($match[2]);
						}
						if (!isset($languagesQ[$match[2]])) {
							$languagesQ[$match[2]] = array();
						}
						$languagesQ[$match[2]][] = strtolower($match[1]);
					}
				}
			}
			krsort($languagesQ);
			foreach ($languagesQ as $langQ) {
				foreach ($langQ as $lang) {
					$lang = str_replace("-", "_", $lang);
					if (($pos = strpos($lang, "_")) !== false) {
						$lang = strtolower(substr($lang, 0, $pos)) . strtoupper(substr($lang, $pos));
					}
					$languages[] = $lang;
				}
			}
		}

		return $languages;
	}

}

Lang::setLang(LANG_DEFAULT);
if (LANG_AUTODETECT)
	Lang::setLang(Lang::detect()[0]);
