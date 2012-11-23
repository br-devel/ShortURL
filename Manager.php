<?php

class EtuDev_ShortURL_Manager {

	/**
	 * @var string
	 */
	static protected $BASE_SHORT_URL;

	static $init = false;

	/**
	 * @param string|null                        $baseShortUrl
	 * @param Zend_Db_Table_Abstract|string|null $table
	 *
	 * @return bool
	 * @throws EtuDev_ShortURL_Exception
	 */
	static public function init($baseShortUrl = null, $table = null) {
		if (!$baseShortUrl && !$table && static::$BASE_SHORT_URL && static::$shortUrlTable) {
			return true;
		}

		if (!$baseShortUrl) {
			if (static::$BASE_SHORT_URL) {
				$baseShortUrl = static::$BASE_SHORT_URL;
			} else {
				if (defined('BASE_SHORT_URL')) {
					$baseShortUrl = BASE_SHORT_URL;
				}
			}
		}

		if (!$table) {
			if (static::$shortUrlTable) {
				$table = static::$shortUrlTable;
			} else {
				if (defined('SHORT_URL_TABLE')) {
					$table = SHORT_URL_TABLE;
				}
			}
		}

		if ($baseShortUrl) {
			static::setBaseShortURL($baseShortUrl);
		}

		if (!$table || !static::setTable($table)) {
			throw new EtuDev_ShortURL_Exception('no table given');
		}

		static::$init = true;
		return true;
	}

	/**
	 * @param int $short_url_id
	 *
	 * @return null|string
	 */
	static public function getShortURLById($short_url_id) {
		if (!static::$init) {
			static::init();
		}

		$id = (int) $short_url_id;
		if ($id) {
			return static::$BASE_SHORT_URL . '/u' . EtuDev_ShortURL_Conversor::fromDecimal($id);
		}

		return null;
	}

	/**
	 * @param string $key
	 *
	 * @return null|string
	 */
	static public function getLongURLByKey($key) {
		if (!static::$init) {
			static::init();
		}

		$u = static::getShortURLRowByKey($key);
		if (!$u) {
			return null;
		}

		return $u->long_url;
	}

	/**
	 * @param string $long_url
	 *
	 * @return string
	 */
	static public function loadOrCreateShortURL($long_url) {
		if (!static::$init) {
			static::init();
		}

		$id = static::createShortURL($long_url);
		return static::getShortURLById($id);
	}

	/**
	 * @param string $long_url
	 *
	 * @return int id
	 */
	static public function createShortURL($long_url) {
		if (!static::$init) {
			static::init();
		}

		$x = static::getShortURLRowByLongURL($long_url);
		if ($x) {
			return $x->id;
		}

		$t = static::getTable();
		$x = $t->createRow();

		$x->long_url = $long_url;
		try {
			$x->save();
		} catch (PDOException $e) {
			//retry to get in case that there already is one
			$x = static::getShortURLRowByLongURL($long_url);
			if ($x) {
				return $x->id;
			}

			//if not, retry
			$x->save();
		}

		return $x->id;
	}


	static protected function getShortURLByLongURL($longURL) {
		$u = static::getShortURLRowByLongURL($longURL);
		if (!$u) {
			return null;
		}

		return static::getShortURLById($u->id);
	}

	/**
	 * @param string $key
	 *
	 * @return Zend_Db_Table_Row|null
	 */
	static protected function getShortURLRowByKey($key) {
		$id = EtuDev_ShortURL_Conversor::toDecimal($key);
		if ($id) {
			return static::getShortURLRowById($id);
		}

		return null;
	}

	/**
	 * @param int $id
	 *
	 * @return Zend_Db_Table_Row|null
	 */
	static protected function getShortURLRowById($id) {
		if (@static::$cached_entities_id[$id]) {
			return static::$cached_entities_id[$id];
		}
		$t = static::getTable();
		$x = $t->fetchRow(array('id = ?' => $id));
		static::cacheRow($x);
		return $x;
	}

	/**
	 * @param string $long_url
	 *
	 * @return Zend_Db_Table_Row|null
	 */
	static protected function getShortURLRowByLongURL($long_url) {
		if (@static::$cached_entities_long_url[$long_url]) {
			return static::$cached_entities_long_url[$long_url];
		}
		$t = static::getTable();
		$x = $t->fetchRow(array('long_url = ?' => $long_url));
		static::cacheRow($x);
		return $x;
	}

	static protected function cacheRow(Zend_Db_Table_Row $x = null) {
		static::$cached_entities_long_url[$x->long_url] = $x;
		static::$cached_entities_id[$x->id]             = $x;
	}

	static protected $cached_entities_long_url = array();
	static protected $cached_entities_id = array();

	/**
	 * @var Zend_Db_Table_Abstract
	 */
	static protected $shortUrlTable;

	/**
	 * @param string|Zend_Db_Table_Abstract $table
	 *
	 * @return bool
	 */
	static public function setTable($table) {
		if (!$table) {
			static::$shortUrlTable = null;
			return true;

		} elseif ($table instanceof Zend_Db_Table_Abstract) {
			static::$shortUrlTable = $table;
			return true;

		} elseif (is_string($table) || (is_object($table) && method_exists($table, '__toString'))) {
			if (is_object($table)) {
				$table = $table->__toString();
			}
			if (class_exists($table)) {
				$t = new $table();
				if ($t instanceof Zend_Db_Table) {
					static::$shortUrlTable = $t;
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return Zend_Db_Table_Abstract|null
	 */
	static public function getTable() {
		return static::$shortUrlTable;
	}

	/**
	 * @param string $BASE_SHORT_URL
	 */
	public static function setBaseShortURL($BASE_SHORT_URL) {
		self::$BASE_SHORT_URL = $BASE_SHORT_URL;
	}

	/**
	 * @return string
	 */
	public static function getBaseShortURL() {
		return self::$BASE_SHORT_URL;
	}


}

