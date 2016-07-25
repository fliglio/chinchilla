<?php

namespace Fliglio\Chinchilla;

use PhpAmqpLib\Connection\AMQPConnection;
use Fliglio\Chinchilla\Test\TestUser;
use Fliglio\Chinchilla\Test\TestUserReply;
use Fliglio\Chinchilla\Test\WorkerTestHelper;

class RPCPublisherTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		$conn = new AMQPConnection('localhost', '5672', 'guest', 'guest');

		$this->testHelper = new WorkerTestHelper($conn, 'test.sandbox.replypublisher');

		$this->rpcWorker = new RPCPublisher($conn, 'test.sandbox.replypublisher');
	}

	public function testConsumeReply() {
		// given
		$this->rpcWorker->publish(new TestUser);
		$this->rpcWorker->publish(new TestUser);
		$msgA = $this->rpcWorker->publish(new TestUser);

		// stub out injectable
		$messageInjectable = (new Message())->setHeaders([
			'message_id' => $msgA->get('message_id'),
			'reply_to'   => $msgA->get('application_headers')['reply_to']
		]);

		// when
		$this->rpcWorker->publishReply($messageInjectable, new TestUserReply);

		$msgB = $this->rpcWorker->getReply($msgA);

		// then 
		$this->assertEquals($msgA->get('message_id'), $msgB->get('message_id'));
		$this->assertEquals($msgB->body, '{"id":1}');
	}

	/** 
	 * @expectedException Fliglio\Chinchilla\TimeoutException
	 */
	public function testConsume_Timeout() {
		// given
		$msgA = $this->rpcWorker->publish(new TestUser);

		// when
		$msgB = $this->rpcWorker->getReply($msgA, 2);
	}

}
