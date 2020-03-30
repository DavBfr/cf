<?php namespace DavBfr\CF;

use Exception;

class SetupRest extends Rest {

	/**
	 *
	 */
	public function getRoutes() {
		$this->addRoute("/", "GET", "get_template_home");
		$this->addRoute("/config", "GET", "get_template_config");
		$this->addRoute("/json", "GET", "get_template_json");
		$this->addRoute("/cmd", "GET", "get_template_cmd");
		$this->addRoute("/cmd/:name", "GET", "run_cmd");
		$this->addRoute("/db", "GET", "get_template_db");
		$this->addRoute("/schema", "GET", "get_template_schema");
		$this->addRoute("/queue", "GET", "get_template_queue");
		$this->addRoute("/mq", "GET", "process_queue");
		$this->addRoute("/api", "GET", "get_template_api");
		$this->addRoute("/openapi.json", "GET", "get_openapi_file");
	}


	/**
	 * @return array
	 */
	protected function getMenu() {
		return array(
			"config" => "Configuration",
			"json" => "Json",
			"cmd" => "Commands",
			"db" => "Database",
			"schema" => "Schema",
			"api" => "Api",
			"queue" => "Queue",
		);
	}


	/**
	 * @param $name
	 * @param array $params
	 * @throws \ReflectionException
	 * @throws Exception
	 */
	protected function defaultTemplate($name, $params = array()) {
		$plugins = array("Bdd", "WebSetup", "Angular", "Bootstrap", "Core");
		foreach ($plugins as $plugin) {
			Plugins::add($plugin);
		}
		$resources = new Resources("setup.min");
		Plugins::dispatchTo($plugins, "resources", $resources);

		foreach (Plugins::findFrom(array("WebSetup"), "www/setup") as $dir) {
			$resources->addDir($dir);
		}

		foreach (Plugins::findFrom($plugins, "www/app") as $dir) {
			$resources->addDir($dir);
		}

		$params["scripts"] = $resources->getScripts();
		$params["stylesheets"] = $resources->getStylesheets();

		$tpt = new Template(array_merge(array(
			"title" => "Setup",
			"baseline" => CorePlugin::getBaseline(),
			"menu" => $this->getMenu(),
			"active" => $name,
			"tpt" => $name
		), $params));
		$tpt->output("websetup-index.php");
	}


	/**
	 *
	 * @throws \ReflectionException
	 */
	protected function get_template_home() {
		$this->defaultTemplate("home");
	}


	/**
	 *
	 * @throws \ReflectionException
	 */
	protected function get_template_config() {
		$options = array();
		foreach (Options::getAll() as $key => $val) {
			if (strpos($key, 'PASS') !== false)
				$options[$key] = array("****", Options::description($key), Options::updated($key));
			else {
				if (is_bool($val))
					$val = $val ? "Yes" : "No";
				$options[$key] = array($val, Options::description($key), Options::updated($key));
			}
		}

		ksort($options);

		$this->defaultTemplate("config", array(
			"options" => $options,
		));
	}


	/**
	 *
	 * @throws \ReflectionException
	 */
	protected function get_template_json() {
		$config = Config::getInstance();

		$this->defaultTemplate("json", array(
			"config" => $config->getAsJson(),
		));
	}


	/**
	 *
	 * @throws \ReflectionException
	 */
	protected function get_template_cmd() {
		$cli = new WebCommands(array());
		Plugins::dispatchAll("cli", $cli);
		//$cli->handle($cli->getCommand(), $cli->getArguments());
		$cli->printHelp($cli->getArguments());

		$this->defaultTemplate("cmd", array(
			"commands" => $cli->getCommands(),
		));
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function run_cmd($r) {
		Input::ensureRequest($r, array("name"));
		$cli = new WebCommands(array("setup", $r["name"]));
		Plugins::dispatchAll("cli", $cli);
		$cli->handle($cli->getCommand(), $cli->getArguments());
		echo "<pre>";
		echo $cli->getOutput();
		echo "</pre>";
		die();
	}


	/**
	 *
	 * @throws \ReflectionException
	 */
	protected function get_template_db() {
		$config = Config::getInstance();
		$a = array();
		foreach ($config->get("model", array()) as $name => $model) {
			$a[] = $name;
		}

		$this->defaultTemplate("db", array(
			"models" => $a,
		));
	}


	/**
	 *
	 * @throws \ReflectionException
	 */
	protected function get_template_schema() {
		$this->defaultTemplate("schema", array(
			"dot" => GraphViz::DBSchema(),
		));
	}


	/**
	 * @throws \ReflectionException
	 */
	protected function get_openapi_file() {
		ErrorHandler::RaiseExceptionOnError();
		$api = json_encode(Rest::getOpenApi());
		Output::file("openapi.json", $api, $type = "text/json");
	}


	/**
	 *
	 * @throws \ReflectionException
	 */
	protected function get_template_api() {
		$this->defaultTemplate("api");
	}


	/**
	 *
	 * @throws \ReflectionException
	 */
	protected function get_template_queue() {
		$mq = MessageQueue::getInstance();
		$this->defaultTemplate("queue", array(
			"queue" => $mq->stat(),
		));
	}


	/**
	 *
	 * @throws Exception
	 */
	protected function process_queue() {
		$mq = MessageQueue::getInstance();
		$mq->dispatch(0, 0);
		Output::redirect(REST_PATH . '/setup/queue');
	}

}
