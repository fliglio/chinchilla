<?php

namespace Fliglio\Chinchilla;

use PhpAmqpLib\Connection\AMQPConnection;
use Fliglio\Chinchilla\Test\TestUser;
use Fliglio\Chinchilla\Test\TestUserReply;
use Fliglio\Chinchilla\Test\WorkerTestHelper;

class RPCPublisherTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		$conn = new AMQPConnection('localhost', '5672', 'guest', 'guest');

		$this->queueName = 'test.sandbox.replypublisher';
		$this->rpcWorker = new RPCPublisher($conn);
	}

	public function testConsumeReply() {
		// given
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$rpcWorker = $this->rpcWorker->publish(new TestUser, $this->queueName);
		$msgA = $rpcWorker->getAmqpMsg();

		// stub out injectable
		$messageInjectable = (new Message())->setHeaders([
			'x-message-id' => $msgA->get('message_id'),
			'x-reply-to'   => $msgA->get('application_headers')['reply_to']
		]);

		// when
		$this->rpcWorker->publishReply($messageInjectable, new TestUserReply);

		$msgB = $rpcWorker->getReply(5);

		// then 
		$this->assertEquals($msgA->get('message_id'), $msgB->get('message_id'));
		$this->assertEquals($msgB->body, '{"id":1}');
	}

	public function testConsumeReply_MultipleMessages() {
		// given
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$rpcWorker = $this->rpcWorker->publish(new TestUser, $this->queueName);
		$msgA = $rpcWorker->getAmqpMsg();

		// put 100 replies on the channel, with the last one having the correct msg id
		for ($i=0; $i < 100; $i++) { 
			$messageInjectable = (new Message())->setHeaders([
				'x-message-id' => uniqid(),
				'x-reply-to'   => $msgA->get('application_headers')['reply_to']
			]);
			$this->rpcWorker->publishReply($messageInjectable, new TestUserReply);
		}

		// correct reply
		$messageInjectable = (new Message())->setHeaders([
			'x-message-id' => $msgA->get('message_id'),
			'x-reply-to'   => $msgA->get('application_headers')['reply_to']
		]);

		// when
		$this->rpcWorker->publishReply($messageInjectable, new TestUserReply);

		$msgB = $rpcWorker->getReply(5);

		// then 
		$this->assertEquals($msgA->get('message_id'), $msgB->get('message_id'));
		$this->assertEquals($msgB->body, '{"id":1}');
	}

	/** 
	 * @expectedException Fliglio\Chinchilla\TimeoutException
	 */
	public function testConsume_Timeout() {
		$this->rpcWorker->publish(new TestUser, $this->queueName)->getReply(1);
	}

}
