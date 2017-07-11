<?php

namespace Fliglio\Chinchilla;

use Fliglio\Flfc\Apps\App;
use Symfony\Component\Yaml\Yaml;

// requires consul to be running
class ApplicationTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		$sf = new \SensioLabs\Consul\ServiceFactory();
		$this->kv = $sf->get('kv');
	}

	public function teardown() {
		$this->kv->delete(Application::KV_PATH.'/test-endpoint.yml');
		$this->kv->delete(Application::KV_PATH . '/foo-bar-endpoint.yml');
		$this->kv->delete(Application::KV_PATH . '/foo-baz-endpoint.yml');
	}

	public function testSingleEndpoint() {
		// given
		$app = new Application(__DIR__.'/test-singleEndpoint.yml');

		// when
		$app->run();

		// then
		$value = $this->kv->get(Application::KV_PATH.'/test-endpoint.yml', ['raw' => true]);

		$yml = Yaml::parse($value->getBody());

		$this->assertEquals($yml['name'], 'test-endpoint');
	}

	public function testMultiEndpoint() {
		// given
		$app = new Application(__DIR__.'/test-multi.yml');

		// when
		$app->run();

		// then
		$value = $this->kv->get(Application::KV_PATH.'/foo-bar-endpoint.yml', ['raw' => true]);
		$yml = Yaml::parse($value->getBody());
		$this->assertEquals($yml['name'], 'foo-bar-endpoint');

		$value = $this->kv->get(Application::KV_PATH.'/foo-baz-endpoint.yml', ['raw' => true]);
		$yml = Yaml::parse($value->getBody());
		$this->assertEquals($yml['name'], 'foo-baz-endpoint');
	}

	public function testMultiEnvEndpoint() {
		// given
		$app = new Application(__DIR__.'/test-multiEnv.yml');
		$_SERVER['CHINCHILLA_ENV'] = 'dev';

		// when
		$app->run();

		// then
		$value = $this->kv->get(Application::KV_PATH.'/foo-bar-endpoint.yml', ['raw' => true]);
		$yml = Yaml::parse($value->getBody());
		$this->assertEquals($yml['name'], 'foo-bar-endpoint');

		$value = $this->kv->get(Application::KV_PATH.'/foo-baz-endpoint.yml', ['raw' => true]);
		$yml = Yaml::parse($value->getBody());
		$this->assertEquals($yml['name'], 'foo-baz-endpoint');
	}

	public function testMultiEndpoint_withNoEnvironments() {
		// given
		$app = new Application(__DIR__.'/test-multi.yml');
		$_SERVER['CHINCHILLA_ENV'] = 'dev';

		// when
		$app->run();

		// then
		$value = $this->kv->get(Application::KV_PATH.'/foo-bar-endpoint.yml', ['raw' => true]);
		$yml = Yaml::parse($value->getBody());
		$this->assertEquals($yml['name'], 'foo-bar-endpoint');

		$value = $this->kv->get(Application::KV_PATH.'/foo-baz-endpoint.yml', ['raw' => true]);
		$yml = Yaml::parse($value->getBody());
		$this->assertEquals($yml['name'], 'foo-baz-endpoint');
	}

	public function testMultiEnvEndpoint_withoutMatchingEnvironment() {
		// given
		$app = new Application(__DIR__.'/test-multiEnv.yml');
		$_SERVER['CHINCHILLA_ENV'] = $env = uniqid();

		// when
		$output = $app->run();

		// then
		$this->assertEquals("\tNo environment matching $env found.\n", $output);
	}

	public function testHandlingNullFile() {
		// given
		$app = new Application(__DIR__.'/doesnt-exist.yml');

		// when
		$output = $app->run();

		// then
		$this->assertEquals("\tNo chinchilla.yml found.\n", $output);
	}

}