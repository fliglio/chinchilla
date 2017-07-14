<?php

namespace Fliglio\Chinchilla;

use Fliglio\Chinchilla\Test\TestUser;
use Fliglio\Chinchilla\Test\TopicTestHelper;
use PhpAmqpLib\Connection\AMQPConnection;

class TopicPublisherTest extends \PHPUnit_Framework_TestCase {

	/** @var TopicPublisher */
	private $publisher;

	/** @var TopicTestHelper */
	private $testHelper;

	public function setup() {
		$conn = new AMQPConnection('localhost', '5672', 'guest', 'guest');

		$this->testHelper = new TopicTestHelper($conn);

		$this->publisher = new TopicPublisher($conn, $this->testHelper->exchangeName);
	}

	public function testMessageId() {
		// given
		$this->testHelper->createQueue('test.sandbox.*');

		// when
		$msg = $this->publisher->publish(new TestUser, 'test.sandbox.update');

		// then 
		$this->assertTrue(strlen($msg->get('message_id')) > 0);
	}

	public function testPublish_GlobSubscriber() {
		// given
		$this->testHelper->createQueue('test.sandbox.*');

		// when
		$this->publisher->publish(new TestUser, 'test.sandbox.update');
		$this->publisher->publish(new TestUser, 'test.sandbox.update');
		$this->publisher->publish(new TestUser, 'test.sandbox.update');

		// then 
		$msgs = $this->testHelper->getMessages('test.sandbox.*');

		$this->assertEquals(count($msgs), 3);
	}

	public function testPublish_NoSubscriber() {
		// given
		$this->testHelper->createQueue('test.sandbox.add');

		// when
		$this->publisher->publish(new TestUser, 'test.sandbox.update');

		// then
		$msgs = $this->testHelper->getMessages('test.sandbox.add');

		$this->assertEquals(count($msgs), 0);
	}

	public function testPublish_DirectSubscriber() {
		// given
		$this->testHelper->createQueue('test.sandbox.update');

		// when
		$this->publisher->publish(new TestUser, 'test.sandbox.update');

		// then
		$msgs = $this->testHelper->getMessages('test.sandbox.update');

		$this->assertEquals(count($msgs), 1);
	}

}
