<?php

namespace Fliglio\Chinchilla;

use Fliglio\Chinchilla\Helper\Md5Filter;
use Fliglio\Chinchilla\Helper\TestUser;
use Fliglio\Chinchilla\Helper\TestUserReply;
use PhpAmqpLib\Connection\AMQPConnection;

class RPCPublisherTest extends \PHPUnit_Framework_TestCase {

	/** @var RPCPublisher */
	private $rpcWorker;
	private $queueName;
	/** @var  AMQPConnection */
	private $conn;

	public function setup() {
		$this->conn = new AMQPConnection('localhost', '5672', 'guest', 'guest');

		$this->queueName = 'test.sandbox.replypublisher';
		$this->rpcWorker = new RPCPublisher($this->conn);
	}

	public function tearDown() {
		$this->cleanQueue($this->queueName);
	}

	/**
	 * @expectedException Fliglio\Chinchilla\TimeoutException
	 */
	public function testConsume_Timeout() {
		$this->rpcWorker->publish(new TestUser, $this->queueName)->getReply(1);
	}

	public function testConsumeReply() {
		// given
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$rpcWorker = $this->rpcWorker->publish(new TestUser, $this->queueName);
		$msgA = $rpcWorker->getAmqpMsg();

		$headers = $msgA->get('application_headers')->getNativeData();

		// stub out injectable
		$messageInjectable = (new Message())->setHeaders([
			'x-message-id' => $msgA->get('message_id'),
			'x-reply-to'   => $headers['reply_to']
		]);

		// when
		$this->rpcWorker->publishReply($messageInjectable, new TestUserReply);

		$msgB = $rpcWorker->getReply(5);

		// then 
		$this->assertEquals($msgA->get('message_id'), $msgB->get('message_id'));
		$this->assertEquals(json_encode((new TestUserReply)->marshal()), $msgB->body);
	}

	public function testConsumeReply_withNoReplyToMessage() {
		// given
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$rpcWorker = $this->rpcWorker->publish(new TestUser, $this->queueName);
		$msgA = $rpcWorker->getAmqpMsg();

		// stub out injectable
		$messageInjectable = (new Message())->setHeaders([
			'x-message-id' => $msgA->get('message_id')
		]);

		// when
		$resp = $this->rpcWorker->publishReply($messageInjectable, new TestUserReply);

		// then 
		$this->assertNull($resp);
	}

	public function testPublish_canUseFilters() {
		// given
		$this->rpcWorker->publish(new TestUser, $this->queueName, [new Md5Filter]);
		$worker = (new WorkerPublisher($this->conn, $this->queueName));

		// when
		$msg = $worker->consumeOne($this->queueName);
		$worker->ack($msg);

		// then
		$this->assertEquals(md5(json_encode((new TestUser)->marshal())), $msg->body);
	}

	public function testPublishReply_canUseFilters() {
		// given
		$rpcWorker = $this->rpcWorker->publish(new TestUser, $this->queueName);
		$msgA = $rpcWorker->getAmqpMsg();

		$headers = $msgA->get('application_headers')->getNativeData();

		// stub out injectable
		$messageInjectable = (new Message())->setHeaders([
			'x-message-id' => $msgA->get('message_id'),
			'x-reply-to'   => $headers['reply_to']
		]);

		// when
		$this->rpcWorker->publishReply($messageInjectable, new TestUserReply, [new Md5Filter]);

		$msgB = $rpcWorker->getReply(5);

		// then
		$this->assertEquals($msgA->get('message_id'), $msgB->get('message_id'));
		$this->assertEquals(md5(json_encode((new TestUserReply)->marshal())), $msgB->body);
	}

	/** @expectedException \Exception */
	public function testGetReply_whenNoMessage() {
		// given
		$publisher = new RPCPublisher($this->conn);
		
		// when
		$publisher->getReply(1);

		// then
		$this->assertTrue(false);
	}

	public function testConsumeReply_MultipleMessages() {
		// given
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$this->rpcWorker->publish(new TestUser, $this->queueName);
		$rpcWorker = $this->rpcWorker->publish(new TestUser, $this->queueName);
		$msgA = $rpcWorker->getAmqpMsg();
		
		$headers = $msgA->get('application_headers')->getNativeData();

		// put 100 replies on the channel, with the last one having the correct msg id
		for ($i=0; $i < 100; $i++) { 
			$messageInjectable = (new Message())->setHeaders([
				'x-message-id' => uniqid(),
				'x-reply-to'   => $headers['reply_to']
			]);
			$this->rpcWorker->publishReply($messageInjectable, new TestUserReply);
		}

		// correct reply
		$messageInjectable = (new Message())->setHeaders([
			'x-message-id' => $msgA->get('message_id'),
			'x-reply-to'   => $headers['reply_to']
		]);

		// when
		$this->rpcWorker->publishReply($messageInjectable, new TestUserReply);

		$msgB = $rpcWorker->getReply(5);

		// then 
		$this->assertEquals($msgA->get('message_id'), $msgB->get('message_id'));
		$this->assertEquals($msgB->body, '{"id":1}');
	}

	private function cleanQueue($queueName) {
		$cleaner = new WorkerPublisher($this->conn, $queueName);

		do {
			$msg = $cleaner->consumeOne($queueName);
			
			if (is_null($msg)) {
				return;
			}

			$cleaner->ack($msg);

		} while (true);
	}

}
