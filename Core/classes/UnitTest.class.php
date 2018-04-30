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

use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use ReflectionClass;

class UnitTest implements TestListener {
	const TESTS_DIR = "tests";
	private $curplugin;
	private $cursuite = "";
	private $curtest = "";
	private $dot = false;
	private $flushed = false;
	private $count = 0;


	/**
	 * UnitTest constructor.
	 * @param string $name
	 */
	public function __construct($name) {
		$this->curplugin = $name;
	}


	/**
	 *
	 */
	private function flushError() {
		if ($this->dot) {
			Cli::pcolorln(Cli::ansiinfo, "]");
			$this->dot = false;
		}
		$this->flushed = true;

		Cli::pinfo("    * Test for " . $this->curplugin . " :: " . $this->cursuite . " :: " . $this->curtest);
	}


	/**
	 * @param Test $test
	 * @param Exception $e
	 * @param int $time
	 */
	public function addError(Test $test, Exception $e, $time) {
		$this->flushError();
		Cli::pcolor(Cli::ansiwarn, "       Exception: ");
		Cli::pcolor(Cli::ansilerr, $e->getMessage());
		Cli::pcolor(Cli::ansicrit, " in ");
		Cli::pcolor(Cli::ansierr, $e->getFile());
		Cli::pcolor(Cli::ansicrit, " line ");
		Cli::pcolorln(Cli::ansierr, $e->getLine());

		$n = 0;
		foreach ($e->getTrace() as $item) {
			if ($n++ < 2) continue;

			if (!array_key_exists("file", $item))
				break;

			Cli::pr("         ");
			if (array_key_exists("class", $item) && $item["class"] != "") {
				Cli::pcolor(Cli::ansicrit, $item["class"] . "->");
			}
			Cli::pcolor(Cli::ansicrit, $item["function"] . "();");
			Cli::pcolor(Cli::ansicrit, " in ");
			Cli::pcolor(Cli::ansierr, $item["file"]);

			if (array_key_exists("line", $item)) {
				Cli::pcolor(Cli::ansicrit, " line ");
				Cli::pcolor(Cli::ansierr, $item["line"]);
			}
			Cli::pln("");
		}
	}


	/**
	 * @param Test $test
	 * @param Warning $e
	 * @param int $time
	 */
	public function addWarning(Test $test, Warning $e, $time) {
		$this->flushError();
		Cli::pcolor(Cli::ansiwarn, "       Warning: ");
		Cli::pcolorln(Cli::ansilerr, $e->getMessage());
		$trace = $e->getTrace();
		if (count($trace) >= 2) {
			$trace = $trace[2];
			Cli::pcolor(Cli::ansicrit, "         in ");
			Cli::pcolor(Cli::ansierr, $trace["file"]);
			Cli::pcolor(Cli::ansicrit, " line ");
			Cli::pcolorln(Cli::ansierr, $trace["line"]);
			Cli::perr("         '" . implode($trace["args"], "' '") . "'");
		}
	}


	/**
	 * @param Test $test
	 * @param AssertionFailedError $e
	 * @param int $time
	 */
	public function addFailure(Test $test, AssertionFailedError $e, $time) {
		$this->flushError();
		Cli::pcolor(Cli::ansiwarn, "       Failure: ");
		Cli::pcolorln(Cli::ansilerr, $e->getMessage());
		$trace = $e->getTrace();
		if (count($trace) >= 2) {
			$trace = $trace[2];
			Cli::pcolor(Cli::ansicrit, "         in ");
			Cli::pcolor(Cli::ansierr, $trace["file"]);
			Cli::pcolor(Cli::ansicrit, " line ");
			Cli::pcolorln(Cli::ansierr, $trace["line"]);
			Cli::perr("         '" . implode($trace["args"], "' '") . "'");
		}
	}


	/**
	 * @param Test $test
	 * @param Exception $e
	 * @param int $time
	 */
	public function addIncompleteTest(Test $test, Exception $e, $time) {
		$this->flushError();
		Cli::pcolor(Cli::ansiwarn, "       Incomplete: ");
		Cli::pcolorln(Cli::ansilerr, $e->getMessage());
	}


	/**
	 * @param Test $test
	 * @param Exception $e
	 * @param int $time
	 */
	public function addRiskyTest(Test $test, Exception $e, $time) {
		$this->flushError();
		Cli::pcolor(Cli::ansiwarn, "       Risky: ");
		Cli::pcolorln(Cli::ansilerr, $e->getMessage());
	}


	/**
	 * @param Test $test
	 * @param Exception $e
	 * @param int $time
	 */
	public function addSkippedTest(Test $test, Exception $e, $time) {
		$this->flushError();
		Cli::pcolor(Cli::ansiwarn, "       Skipped: ");
		Cli::pcolorln(Cli::ansilerr, $e->getMessage());
	}


	/**
	 * @param TestSuite $suite
	 */
	public function startTestSuite(TestSuite $suite) {
		$this->cursuite = $suite->getName();
	}


	/**
	 * @param TestSuite $suite
	 */
	public function endTestSuite(TestSuite $suite) {
		$this->cursuite = "";
	}


	/**
	 * @param Test $test
	 */
	public function startTest(Test $test) {
		$this->curtest = $test->getName();
	}


	/**
	 * @param Test $test
	 * @param int $time
	 */
	public function endTest(Test $test, $time) {
		if ($this->curtest != "") {
			if ($this->count++ >= 40) {
				$this->dot = false;
				Cli::pcolorln(Cli::ansiinfo, "]");
			}
			if (!$this->dot) {
				Cli::pcolor(Cli::ansiinfo, "  * ");
				Cli::pcolor(Cli::ansilog, $this->curplugin);
				Cli::pcolor(Cli::ansiinfo, " [");
				$this->dot = true;
				$this->count = 0;
			}
			Cli::pcolor(Cli::ansiinfo, ".");

		}
		$this->curtest = "";
	}


	/**
	 * @throws \ReflectionException
	 */
	public static function runtests() {
		Cli::pinfo("Running tests");
		ErrorHandler::unregister();

		// Create Suite
		foreach (Plugins::get_plugins() as $name) {
			$result = new TestResult();
			$listner = new self($name);
			$result->addListener($listner);
			$plugin = Plugins::get($name);
			$dir = $plugin->getDir() . DIRECTORY_SEPARATOR . self::TESTS_DIR;
			if (is_dir($dir)) {
				foreach (glob($dir . DIRECTORY_SEPARATOR . "*.test.php") as $file) {
					require_once($file);
					$testclassname = __NAMESPACE__ . "\\" . substr(basename($file), 0, -9) . "Test";
					$suite = new TestSuite(new ReflectionClass($testclassname));
					$suite->run($result);
				}
			}
			if ($listner->dot)
				Cli::pcolorln(Cli::ansiinfo, "]");

		}
	}

}
