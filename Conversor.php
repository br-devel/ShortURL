<?php

/**
 * based in Base62 by Lalit Patel
 *
 * Base62: A class to convert a number from any base between 2-62 to any other base between 2-62
 * It doesn't use BC Math functions so works without the use of BC Math library.
 * It uses the native base_convert functions when the base is below 36 for faster execution.
 * The output number is backward compatible with the native base_convert function.
 *
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/base62-php-convert-number-to-base-62-for-short-urls
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (08 December 2011)
 *
 * Usage:
 *       $converted_num = Base62::convert($number, $from_base, $to_base);
 *
 * modificada por eturino
 */

class EtuDev_ShortURL_Conversor {

	const CHARLIST_NORMAL = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

	/**
	 * needs to be a prime number (better if large)
	 * @var int
	 */
	static protected $MULTIPLIER = 13; //primo

	/**
	 * a positive number to be added (extra protection)
	 * @var int
	 */
	static protected $SUMATOR = 1; //sumador extra

	/**
	 * @var string
	 */
	static protected $CHARLIST;

	static protected $init = false;

	static public function init($multiplier = null, $sumator = null, $charlist = null) {
		if (!$multiplier && !$sumator && !$charlist && static::$MULTIPLIER && static::$SUMATOR && static::$CHARLIST) {
			return true;
		}

		if (!$multiplier) {
			if (static::$MULTIPLIER) {
				$multiplier = static::$MULTIPLIER;
			} else {
				if (defined('SHORT_URL_MULTIPLIER')) {
					$multiplier = SHORT_URL_MULTIPLIER;
				}
			}
		}

		if (!$sumator) {
			if (static::$SUMATOR) {
				$sumator = static::$SUMATOR;
			} else {
				if (defined('SHORT_URL_SUMATOR')) {
					$sumator = SHORT_URL_SUMATOR;
				}
			}
		}

		if (!$charlist) {
			if (static::$CHARLIST) {
				$charlist = static::$CHARLIST;
			} else {
				if (defined('SHORT_URL_CHARLIST')) {
					$charlist = SHORT_URL_CHARLIST;
				} else {
					$charlist = static::CHARLIST_NORMAL;
				}
			}
		}

		if ($multiplier) {
			static::setMultiplier($multiplier);
		}

		if ($sumator) {
			static::setSumator($sumator);
		}

		if ($charlist) {
			static::setCharlist($charlist);
		}


		static::$init = true;
		return true;
	}

	static public function fromDecimal($decimal) {
		if (!static::$init) {
			static::init();
		}
		return static::convert(($decimal * static::$MULTIPLIER) + static::$SUMATOR, 10, 62, static::$CHARLIST);
	}

	static public function toDecimal($base62) {
		if (!static::$init) {
			static::init();
		}
		$x = (static::convert($base62, 62, 10, static::$CHARLIST) - static::$SUMATOR) / static::$MULTIPLIER;

		if (intval($x) == $x) {
			return $x;
		}

		return null;
	}

	/**
	 * Converts a number/string from any base between 2-62 to any other base from 2-62
	 *
	 * @param mixed        $number
	 * @param int          $from_base
	 * @param int          $to_base
	 * @param string|null  $charlist
	 *
	 * @return string $conveted_number.
	 * @throws Exception
	 */
	protected static function convert($number, $from_base = 10, $to_base = 62, $charlist = null) {
		if (!$charlist) {
			$charlist = static::$CHARLIST;
		}
		if ($to_base > 62 || $to_base < 2) {
			throw new Exception("Invalid base (" . $to_base . "). Max base can be 62. Min base can be 2.");
		}

		//OPTIMIZATION: no need to convert 0
		if ("{$number}" === '0') {
			return 0;
		}

		//OPTIMIZATION: if to and from base are same.
		if ($from_base == $to_base) {
			return $number;
		}

		//OPTIMIZATION: if base is lower than 36, use PHP internal function (only if we use the NORMAL charset)
		if ($from_base <= 36 && $to_base <= 36 && $charlist == static::CHARLIST_NORMAL) {
			// for lower base, use the default PHP function for faster results
			return base_convert($number, $from_base, $to_base);
		}

		// char list starts from 0-9 and then small alphabets and then capital alphabets
		// to make it compatible with eixisting base_convert function

		if ($from_base < $to_base) {
			// if converstion is from lower base to higher base
			// first get the number into decimal and then convert it to higher base from decimal;

			if ($from_base != 10) {
				$decimal = self::convert($number, $from_base, 10);
			} else {
				$decimal = intval($number);
			}

			//get the list of valid characters
			$charlist = substr($charlist, 0, $to_base);

			if ($number == 0) {
				return 0;
			}
			$converted = '';
			while ($number > 0) {
				$converted = $charlist{($number % $to_base)} . $converted;
				$number    = floor($number / $to_base);
			}
			return $converted;
		} else {
			// if conversion is from higher base to lower base;
			// first convert it into decimal and the convert it to lower base with help of same function.
			$number  = "{$number}";
			$length  = strlen($number);
			$decimal = 0;
			$i       = 0;
			while ($length > 0) {
				$char = $number{$length - 1};
				$pos  = strpos($charlist, $char);
				if ($pos === false) {
					trigger_error("Invalid character in the input number: " . ($char), E_USER_ERROR);
				}
				$decimal += $pos * pow($from_base, $i);
				$length--;
				$i++;
			}
			return self::convert($decimal, 10, $to_base);
		}
	}

	/**
	 * @param string $CHARLIST
	 */
	public static function setCharlist($CHARLIST) {
		self::$CHARLIST = $CHARLIST;
	}

	/**
	 * @return string
	 */
	public static function getCharlist() {
		return self::$CHARLIST;
	}

	/**
	 * @param int $MULTIPLIER
	 */
	public static function setMultiplier($MULTIPLIER) {
		self::$MULTIPLIER = $MULTIPLIER;
	}

	/**
	 * @return int
	 */
	public static function getMultiplier() {
		return self::$MULTIPLIER;
	}

	/**
	 * @param int $SUMATOR
	 */
	public static function setSumator($SUMATOR) {
		self::$SUMATOR = $SUMATOR;
	}

	/**
	 * @return int
	 */
	public static function getSumator() {
		return self::$SUMATOR;
	}


}
