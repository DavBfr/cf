<?php

class Cli {
	private $args;
	private $commands;

	public function __construct($argv) {
		$this->args = $this->parseArguments($argv);
		$this->commands = array();
		$this->addCommand("help", array($this, "printHelp"), "Help messages");
	}


	public static function pr($s="") {
		print($s);
	}


	public static function pln($s="") {
		self::pr($s . "\n");
	}


	public function getCommand() {
		if (isset($this->args['input']) && isset($this->args['input'][1]))
			return $this->args['input'][1];
		return "help";
	}


	public function getArguments() {
		return $this->args;
	}


	public function addCommand($name, $callable, $help) {
		$this->commands[$name] = array($callable, $help);
	}


	public function handle($command, $params) {
		if (isset($this->commands[$command])) {
			return call_user_func($this->commands[$command][0], $params);
		}
		self::pln("Unknown command $command");
		$this->printHelp($params);
	}


	public function printHelp($args) {
		self::pln("Help " . $args["input"][0]);
		foreach ($this->commands as $name => $value) {
			self::pln("  $name : " . $value[1]);
		}
	}


	private function parseArguments($argv) {
		$_ARG = array();
		foreach ($argv as $arg) {
			if (preg_match('#^-{1,2}([a-zA-Z0-9]*)=?(.*)$#', $arg, $matches)) {
				$key = $matches[1];
				switch ($matches[2])
				{
					case '':
					case 'true':
					case 'yes':
						$arg = true;
						break;
					case 'false':
					case '0':
					case 'no':
						$arg = false;
						break;
					default:
						$arg = $matches[2];
				}
				$_ARG[$key] = $arg;
			} else {
				$_ARG['input'][] = $arg;
			}
		}
		return $_ARG;
	}


	public function question() {
		echo "Are you sure you want to do this?  Type 'yes' to continue: ";
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		if(trim($line) != 'yes'){
			echo "ABORTING!\n";
			exit;
		}
		echo "\n";
		echo "Thank you, continuing...\n";
	}

}
