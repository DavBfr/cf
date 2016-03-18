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

 /**
 * Based on Portable PHP password hashing framework.
 *
 * Version 0.3 / genuine.
 *
 * Written by Solar Designer <solar at openwall.com> in 2004-2006 and placed in
 * the public domain. Revised in subsequent years, still public domain.
 *
 * There's absolutely no warranty.
 *
 * The homepage URL for this framework is:
 *
 *	http://www.openwall.com/phpass/
 **/

use Exception;

configure("PASSWORD_ITERATION_COUNT", 8);
configure("PASSWORD_PORTABLE", false);


class Password {

	private $itoa64;
	private $iteration_count_log2;
	private $portable_hashes;
	private $random_state;


	public function __construct() {
		$iteration_count_log2 = PASSWORD_ITERATION_COUNT;
		$portable_hashes = PASSWORD_PORTABLE;

		$this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
			$iteration_count_log2 = 8;

		$this->iteration_count_log2 = $iteration_count_log2;

		$this->portable_hashes = $portable_hashes;

		$this->random_state = microtime();
		if (function_exists('getmypid'))
			$this->random_state .= getmypid();
	}


	public function getRandomBytes($count) {
		$output = '';
		if (is_readable('/dev/urandom') &&
		($fh = @fopen('/dev/urandom', 'rb'))) {
			$output = fread($fh, $count);
			fclose($fh);
		}

		if (strlen($output) < $count) {
			$output = '';
			for ($i = 0; $i < $count; $i += 16) {
				$this->random_state = md5(microtime() . $this->random_state);
				$output .= pack('H*', md5($this->random_state));
			}
			$output = substr($output, 0, $count);
		}

		return $output;
	}


	private function encode64($input, $count) {
		$output = '';
		$i = 0;
		do {
			$value = ord($input[$i++]);
			$output .= $this->itoa64[$value & 0x3f];
			if ($i < $count)
				$value |= ord($input[$i]) << 8;

			$output .= $this->itoa64[($value >> 6) & 0x3f];
			if ($i++ >= $count)
				break;

			if ($i < $count)
				$value |= ord($input[$i]) << 16;

			$output .= $this->itoa64[($value >> 12) & 0x3f];
			if ($i++ >= $count)
				break;

			$output .= $this->itoa64[($value >> 18) & 0x3f];
		} while ($i < $count);

		return $output;
	}


	private function gensalt_private($input) {
		$output = '$P$';
		$output .= $this->itoa64[min($this->iteration_count_log2 + ((PHP_VERSION >= '5') ? 5 : 3), 30)];
		$output .= $this->encode64($input, 6);

		return $output;
	}


	private function crypt_private($password, $setting) {
		$output = '*0';
		if (substr($setting, 0, 2) == $output)
			$output = '*1';

		$id = substr($setting, 0, 3);
		# We use "$P$", phpBB3 uses "$H$" for the same thing
		if ($id != '$P$' && $id != '$H$')
			return $output;

		$count_log2 = strpos($this->itoa64, $setting[3]);
		if ($count_log2 < 7 || $count_log2 > 30)
			return $output;

		$count = 1 << $count_log2;

		$salt = substr($setting, 4, 8);
		if (strlen($salt) != 8)
			return $output;

		# We're kind of forced to use MD5 here since it's the only
		# cryptographic primitive available in all versions of PHP
		# currently in use. To implement our own low-level crypto
		# in PHP would result in much worse performance and
		# consequently in lower iteration counts and hashes that are
		# quicker to crack (by non-PHP code).
		if (PHP_VERSION >= '5') {
			$hash = md5($salt . $password, true);
			do {
				$hash = md5($hash . $password, true);
			} while (--$count);
		} else {
			$hash = pack('H*', md5($salt . $password));
			do {
				$hash = pack('H*', md5($hash . $password));
			} while (--$count);
		}

		$output = substr($setting, 0, 12);
		$output .= $this->encode64($hash, 16);

		return $output;
	}


	private function gensalt_extended($input) {
		$count_log2 = min($this->iteration_count_log2 + 8, 24);
		# This should be odd to not reveal weak DES keys, and the
		# maximum valid value is (2**24 - 1) which is odd anyway.
		$count = (1 << $count_log2) - 1;

		$output = '_';
		$output .= $this->itoa64[$count & 0x3f];
		$output .= $this->itoa64[($count >> 6) & 0x3f];
		$output .= $this->itoa64[($count >> 12) & 0x3f];
		$output .= $this->itoa64[($count >> 18) & 0x3f];

		$output .= $this->encode64($input, 3);

		return $output;
	}


	private function gensalt_blowfish($input) {
		# This one needs to use a different order of characters and a
		# different encoding scheme from the one in encode64() above.
		# We care because the last character in our encoded string will
		# only represent 2 bits. While two known implementations of
		# bcrypt will happily accept and correct a salt string which
		# has the 4 unused bits set to non-zero, we do not want to take
		# chances and we also do not want to waste an additional byte
		# of entropy.
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = '$2a$';
		$output .= chr(ord('0') + $this->iteration_count_log2 / 10);
		$output .= chr(ord('0') + $this->iteration_count_log2 % 10);
		$output .= '$';

		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;
	}


	public function hash($password) {
		$random = '';

		if (CRYPT_BLOWFISH == 1 && !$this->portable_hashes) {
			$random = $this->getRandomBytes(16);
			$hash = crypt($password, $this->gensalt_blowfish($random));
			if (strlen($hash) == 60)
				return $hash;
		}

		if (CRYPT_EXT_DES == 1 && !$this->portable_hashes) {
			if (strlen($random) < 3)
				$random = $this->getRandomBytes(3);
			$hash = crypt($password, $this->gensalt_extended($random));
			if (strlen($hash) == 20)
				return $hash;
		}

		if (strlen($random) < 6)
			$random = $this->getRandomBytes(6);
		$hash = $this->crypt_private($password, $this->gensalt_private($random));
		if (strlen($hash) == 34)
			return $hash;

		# Returning '*' on error is safe here, but would _not_ be safe
		# in a crypt(3)-like function used _both_ for generating new
		# hashes and for validating passwords against existing hashes.
		return '*';
	}


	public function check($password, $stored_hash) {
		$hash = $this->crypt_private($password, $stored_hash);
		if ($hash[0] == '*')
			$hash = crypt($password, $stored_hash);

		return $hash == $stored_hash;
	}


	public function sanitizeUser($user) {
		/* Sanity-check the username, don't rely on our use of prepared statements
		* alone to prevent attacks on the SQL server via malicious usernames. */
		if (!preg_match('/^[a-zA-Z0-9_]{1,60}$/', $user))
			throw new Exception('Invalid username');
	}


	public function sanitizePass($pass) {
		/* Don't let them spend more of our CPU time than we were willing to.
		* Besides, bcrypt happens to use the first 72 characters only anyway. */
		if (strlen($pass) > 72)
			throw new Exception('The supplied password is too long');
	}


	protected function pwqcheckLite($newpass, $oldpass = '', $user = '', $aux = '', $minlen = 7) {
		/* Some really trivial and obviously-insufficient password strength checks -
		* we ought to use the pwqcheck(1) program instead. */

		if (strlen($newpass) < $minlen)
			return 'The new password is way too short';
		elseif (stristr($oldpass, $newpass) || (strlen($oldpass) >= 4 && stristr($newpass, $oldpass)))
			return 'The new password is based on the old one';
		elseif (stristr($user, $newpass) || (strlen($user) >= 4 && stristr($newpass, $user)))
			return 'The new password is based on the username';

		return true;
	}


	protected function pwqcheck($newpass, $oldpass = '', $user = '', $aux = '', $args = '') {
		$descriptorspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'));

		// Replace characters that would violate the protocol
		$newpass = strtr($newpass, "\n", '.');
		$oldpass = strtr($oldpass, "\n", '.');
		$user = strtr($user, "\n:", '..');

		// Trigger a "too short" rather than "is the same" message in this special case
		if (!$newpass && !$oldpass)
			$oldpass = '.';

		if ($args)
			$args = ' ' . $args;
		if (!$user)
			$args = ' -2' . $args; // passwdqc 1.2.0+

		$command = ''; // No need to keep the shell process around on Unix
		$command .= 'pwqcheck' . $args;
		if (($process = @proc_open($command, $descriptorspec, $pipes)) === false)
			return false;

		if (!is_resource($process)) {
			return false;
		}

		$err = 0;
		fwrite($pipes[0], "$newpass\n$oldpass\n") || $err = 1;
		if ($user)
			fwrite($pipes[0], "$user::::$aux:/:\n") || $err = 1;

		fclose($pipes[0]) || $err = 1;
		($output = stream_get_contents($pipes[1])) || $err = 1;
		fclose($pipes[1]);

		($error = stream_get_contents($pipes[2])) || $err = 1;
		fclose($pipes[2]);

		$status = proc_close($process);

		// There must be a linefeed character at the end. Remove it.
		if (substr($output, -1) === "\n")
			$output = substr($output, 0, -1);
		else
			$err = 1;


		if ($err !== 0)
			return false;

		if ($status !== 0 || $output !== 'OK')
			return $output;

		return true;
	}


	public function strengthCheck($newpass, $oldpass = '', $user = '', $aux = '', $minlen = 7) {
		$n0 = $minlen;
		$n1 = intval(ceil($minlen * 4 / 5));
		$n2 = intval(ceil($minlen * 3 / 4));
		$n3 = intval(ceil($minlen * 2 / 3));
		$n4 = intval(ceil($minlen * 1 / 2));
		$args = "max=72 min=$n0,$n1,$n2,$n3,$n4";

		$ret = $this->pwqcheck($newpass, $oldpass, $user, $aux, $args);
		if ($ret === false)
			return $this->pwqcheckLite($newpass, $oldpass, $user, $aux, $minlen);

		return $ret;
	}

}
