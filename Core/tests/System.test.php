<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2016 David PHAM-VAN
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

class SystemTest extends \PHPUnit\Framework\TestCase {

	public function testRelativePath() {
		$rp = System::relativePath("/var/www/blog/files", "/var/www/cf");
		$this->assertEquals($rp, '../../cf');

		$rp = System::relativePath("/home/dad/test/one", "/home/dad/test/two");
		$this->assertEquals($rp, '../two');

		$rp = System::relativePath("/home/test/www/one", "/home/cf/Bootstrap/www/vendor/fonts/one");
		$this->assertEquals($rp, '../../../cf/Bootstrap/www/vendor/fonts/one');

		$rp = System::relativePath("/home/test/www/one", "/home/test/www/one/two");
		$this->assertEquals($rp, 'two');
		
		$rp = System::relativePath("/home/dad/www/BlogMVC/CoreFramework", "/home/dad/www/cf");
		$this->assertEquals($rp, '../../cf');
	}


	public function testAbsPath() {
		$this->assertEquals(System::absPath("one/../two"), 'two');
		$this->assertEquals(System::absPath("one/./two"), 'one/two');
	}

}
