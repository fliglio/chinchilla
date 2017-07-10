<?php

namespace Fliglio\Chinchilla;

use SensioLabs\Consul as consul;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml;

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

		if (isset($this->config['environments'])) {
			if (isset($this->config['environments'][$_SERVER['ENVIRONMENT']])) {
				$output = $this->parseConfigs($this->config['environments'][$_SERVER['ENVIRONMENT']]);
			} else {
				return "\tNo matching environment found.\n";
			}
		} else {
			$output = $this->parseConfigs($this->config);
		}

		return $output;
	}

	private function parseConfigs($config) {

		$output = '';

		if (isset($config['endpoints'])) {
			foreach ($config['endpoints'] as $conf) {
				$output .= $this->register($conf);
			}

		} else {
			$output = $this->register($config);
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
