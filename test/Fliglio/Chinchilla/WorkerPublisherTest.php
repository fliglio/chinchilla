<?php

namespace Fliglio\Chinchilla;

use PhpAmqpLib\Connection\AMQPConnection;
use Fliglio\Chinchilla\Test\TestUser;
use Fliglio\Chinchilla\Test\WorkerTestHelper;

class WorkerPublisherTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		$conn = new AMQPConnection('localhost', '5672', 'guest', 'guest');

		$this->testHelper = new WorkerTestHelper($conn, 'test.sandbox.worker');

		$this->publisher = new WorkerPublisher($conn, 'test.sandbox.worker');
	}

	public function testConsumerPoll() {
		// given
		$msgA = $this->publisher->publish(new TestUser);

		// when
		$msgB = $this->publisher->findMessage($msgA->get('message_id'));

		// then 
		$this->assertEquals($msgA->get('message_id'), $msgB->get('message_id'));
	}

	/** 
	 * @expectedException Fliglio\Chinchilla\TimeoutException
	 */
	public function testConsumerPoll_Timeout() {
		// given
		$msgA = $this->publisher->publish(new TestUser);

		// when
		$msgB = $this->publisher->findMessage('wrong_msg_id', 'test.sandbox.worker', 1);
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
