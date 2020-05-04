<?php

namespace Fliglio\Chinchilla;

use Fliglio\Chinchilla\Helper\TestUser;
use Fliglio\Chinchilla\Helper\TopicTestHelper;
use PhpAmqpLib\Connection\AMQPConnection;

class TopicPublisherTest extends \PHPUnit_Framework_TestCase {

	/** @var TopicPublisher */
	private $publisher;

	/** @var TopicTestHelper */
	private $testHelper;

	public function setUp() {
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

		$this->assertEquals(3, count($msgs));
	}

	public function testPublish_NoSubscriber() {
		// given
		$this->testHelper->createQueue('test.sandbox.add');

		// when
		$this->publisher->publish(new TestUser, 'test.sandbox.update');

		// then
		$msgs = $this->testHelper->getMessages('test.sandbox.add');

		$this->assertEquals(0, count($msgs));
	}

	public function testPublish_DirectSubscriber() {
		// given
		$this->testHelper->createQueue('test.sandbox.update');

		// when
		$this->publisher->publish(new TestUser, 'test.sandbox.update');

		// then
		$msgs = $this->testHelper->getMessages('test.sandbox.update');

		$this->assertEquals(1, count($msgs));
	}

}
