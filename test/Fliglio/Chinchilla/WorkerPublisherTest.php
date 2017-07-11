<?php

namespace Fliglio\Chinchilla;

use Fliglio\Chinchilla\Test\TestUser;
use Fliglio\Chinchilla\Test\WorkerTestHelper;
use PhpAmqpLib\Connection\AMQPConnection;

class WorkerPublisherTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		$conn = new AMQPConnection('localhost', '5672', 'guest', 'guest');

		$this->testHelper = new WorkerTestHelper($conn, 'test.sandbox.workertest');

		$this->publisher = new WorkerPublisher($conn, 'test.sandbox.workertest');
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
