<?php

namespace Fliglio\Chinchilla;

use SensioLabs\Consul as consul;
use Symfony\Component\Yaml\Yaml;

class Application {

	const KV_PATH = 'chinchilla/endpoints/';

	private $config;
	private $rawContents;

	public function __construct($configPath = null) {
		$configPaths = [
			$configPath,
			__DIR__ . '/../../chinchilla.yml',
		];

		foreach ($configPaths as $path) {
			if (is_file($path)) {
				$this->rawContents = file_get_contents($path);
				$this->config = Yaml::parse($this->rawContents);
				break; // first come first serve
			}
		}

		if (!isset($this->config)) {
			throw new \Exception('chinchilla.yml not found.');
		}
	}

	public function run() {
		$name = $this->config['name'].'.yml';

		$sf = new consul\ServiceFactory();
		$kv = $sf->get('kv');

		$kv->put(self::KV_PATH.$name, $this->rawContents);

		return sprintf("\tInstalled chinchilla endpoint: %s\n", $name);
	}

}