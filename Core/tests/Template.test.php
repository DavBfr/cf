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

class TemplateTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @throws \Exception
	 */
	public function testVariables() {
        $tpt = new Template(array("myvar" => "456"));

        $this->assertEquals($tpt->get("myvar"), "456");
        $tpt->set("myvar", 123);
        $this->assertEquals($tpt->get("myvar"), "123");
    }


	/**
	 * @throws \Exception
	 */
	public function testFilter() {
        $tpt = new Template();

        $tpt->set("rawvar", array());
        $this->assertEquals($tpt->get("rawvar", "raw"), array());
        $tpt->set("trvar", "yes");
        $this->assertEquals($tpt->get("trvar", "tr"), Lang::get("yes"));
        $tpt->set("escvar", "<b>");
        $this->assertEquals($tpt->get("escvar", "esc"), "&lt;b&gt;");
        $tpt->set("stvar", "<b>bold</b>");
        $this->assertEquals($tpt->get("stvar", "st"), "bold");
        $tpt->set("intvar", 456);
        $this->assertEquals($tpt->get("intvar", "int"), "456");
    }


	/**
	 * @throws \Exception
	 */
	public function testParsing() {
        $tpt = new Template(array("myvar" => "456"));
        $data = $tpt->parse("test.template.php");

        $this->assertEquals(trim($data), 'test 123 456');
    }

}
