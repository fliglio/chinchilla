<?php

namespace Fliglio\Chinchilla;

use Symfony\Component\Yaml\Yaml;

// requires consul to be running
class ApplicationTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		$sf = new \SensioLabs\Consul\ServiceFactory();
		$this->kv = $sf->get('kv');
	}

	public function teardown() {
		$this->kv->delete(Application::KV_PATH.'/test-endpoint.yml');
	}

	public function testApplication() {
		// given
		$app = new Application(__DIR__.'/test.yml');

		// when
		$app->run();

		// then
		$value = $this->kv->get(Application::KV_PATH.'/test-endpoint.yml', ['raw' => true]);

		$yml = Yaml::parse($value->getBody());

		$this->assertEquals($yml['name'], 'test-endpoint');
	}

}