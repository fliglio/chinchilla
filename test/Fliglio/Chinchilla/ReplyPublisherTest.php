<?php

namespace Fliglio\Chinchilla;

use PhpAmqpLib\Connection\AMQPConnection;
use Fliglio\Chinchilla\Test\TestUser;
use Fliglio\Chinchilla\Test\TestUserReply;
use Fliglio\Chinchilla\Test\WorkerTestHelper;

class ReplyPublisherTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		$conn = new AMQPConnection('localhost', '5672', 'guest', 'guest');

		$this->testHelper = new WorkerTestHelper($conn, 'test.sandbox.replypublisher');

		$this->worker = new WorkerPublisher($conn, 'test.sandbox.replypublisher');
		$this->reply  = new ReplyPublisher($conn);
	}

	public function testConsumeReply() {
		// given
		$this->worker->publish(new TestUser);
		$this->worker->publish(new TestUser);
		$msgA = $this->worker->publish(new TestUser);

		// stub out injectable
		$messageInjectable = (new Message())->setHeaders([
			'message_id' => $msgA->get('message_id'),
			'reply_to'   => $msgA->get('application_headers')['reply_to']
		]);

		// when
		$this->reply->publish($messageInjectable, new TestUserReply);

		$msgB = $this->reply->getReply($msgA);

		// then 
		$this->assertEquals($msgA->get('message_id'), $msgB->get('message_id'));
	}

	/** 
	 * @expectedException Fliglio\Chinchilla\TimeoutException
	 */
	public function testConsume_Timeout() {
		// given
		$msgA = $this->worker->publish(new TestUser);

		// when
		$msgB = $this->reply->getReply($msgA, 2);
	}

}
