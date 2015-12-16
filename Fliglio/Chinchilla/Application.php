<?php

namespace Fliglio\Chinchilla;

use SensioLabs\Consul as consul;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;

class Application {

	const KV_PATH = 'chinchilla/endpoints/';

	private $config;
	private $kv;

	public function __construct($configPath, $options = []) {
		if (is_file($configPath)) {
			$this->config = Yaml::parse(file_get_contents($configPath));

			$sf = new consul\ServiceFactory($options);
			$this->kv = $sf->get('kv');
		}
	}

	public function run() {
		if (!$this->config) {
			return "\tNo chinchilla.yml found.\n";
		}

		$output = '';

		if (isset($this->config['endpoints'])) {
			foreach ($this->config['endpoints'] as $config) {
				$output .= $this->register($config);
			}

		} else {
			$output = $this->register($this->config);
		}

		return $output;
	}

	private function register($config) {
		$name = $config['name'].'.yml';
		$yml = (new Dumper())->dump($config, 2); // indent level 2 is standard yml format 

		$this->kv->put(self::KV_PATH.$name, $yml);

		return sprintf("\tInstalled chinchilla endpoint: %s\n", $name);
	}

}
