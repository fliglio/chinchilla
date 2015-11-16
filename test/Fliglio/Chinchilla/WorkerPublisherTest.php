<?php

namespace Fliglio\Chinchilla;

use Fliglio\Chinchilla\Test\TestUser;
use Fliglio\Chinchilla\Test\WorkerTestHelper;

class WorkerPublisherTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		Connection::addConfig(new Config);

		$this->testHelper = new WorkerTestHelper('test.sandbox.worker');

		$this->publisher = new WorkerPublisher(Connection::get()->channel(), 'test.sandbox.worker');
	}

	public function teardown() {
		Connection::reset();
	}

	public function testPublish() {
		// when
		$this->publisher->publish(new TestUser);
		$this->publisher->publish(new TestUser);
		$this->publisher->publish(new TestUser);

		// then 
		$msgs = $this->testHelper->getMessages();

		$this->assertEquals(count($msgs), 3);
	}

}
