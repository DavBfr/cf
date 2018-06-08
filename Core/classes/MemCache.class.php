<?php namespace DavBfr\CF;

/**
 * Copyright (C) 2013-2015 David PHAM-VAN
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

use Exception;


class MemCache implements \ArrayAccess {

	private static $data = array();

	private $lifetime;
	private $apc;


	/**
	 * MemCache constructor.
	 * @param int $lifetime
	 */
	public function __construct($lifetime = MEMCACHE_LIFETIME) {
		$this->lifetime = $lifetime;
		$this->apc = MEMCACHE_ENABLED && function_exists('apc_store') && ini_get('apc.enabled') && !DEBUG;
	}


	/**
	 * @param string $offset
	 * @param mixed $value
	 * @throws Exception
	 */
	public function offsetSet($offset, $value) {
		if (is_null($offset))
			throw new Exception("MemCache offset cannot be null");

		self::$data[$offset] = $value;
		if ($this->apc) {
			apc_store(MEMCACHE_PREFIX . $offset, $value, $this->lifetime);
		}
	}


	/**
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		if (isset(self::$data[$offset]))
			return true;
		if ($this->apc)
			return apc_exists(MEMCACHE_PREFIX . $offset);
		return false;
	}


	/**
	 * @param string $offset
	 */
	public function offsetUnset($offset) {
		unset(self::$data[$offset]);
		if ($this->apc)
			apc_delete(MEMCACHE_PREFIX . $offset);
	}


	/**
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		if (isset(self::$data[$offset]))
			return self::$data[$offset];
		if ($this->apc)
			return apc_fetch(MEMCACHE_PREFIX . $offset);
		return null;
	}
}
