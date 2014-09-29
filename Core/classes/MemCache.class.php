<?php
/**
 * Copyright (C) 2013 David PHAM-VAN
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

class MemCache implements arrayaccess {

	private static $data = array();

	private $lifetime;
	private $apc;

	public function __construct($lifetime = MEMCACHE_LIFETIME) {
		$this->lifetime = $lifetime;
		$this->apc = false;function_exists('apc_store') && ini_get('apc.enabled') && !DEBUG;
	}


	public function offsetSet($offset, $value) {
		if (is_null($offset))
			throw Exception("MemCache offset cannot be null");

		self::$data[$offset] = $value;
		if ($this->apc) {
			apc_store(MEMCACHE_PREFIX . $offset, $value, $this->lifetime);
		}
	}


	public function offsetExists($offset) {
		if (isset(self::$data[$offset]))
			return true;
		if ($this->apc)
			return apc_exists(MEMCACHE_PREFIX . $offset);
		return false;
	}


	public function offsetUnset($offset) {
		unset(self::$data[$offset]);
		if ($this->apc)
			apc_delete(MEMCACHE_PREFIX . $offset);
	}


	public function offsetGet($offset) {
		if (isset(self::$data[$offset]))
			return self::$data[$offset];
		if ($this->apc)
			return apc_fetch(MEMCACHE_PREFIX . $offset);
		return null;
	}
}
