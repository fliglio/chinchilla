<?php

namespace Fliglio\Chinchilla\Helper;

use PhpAmqpLib\Connection\AMQPConnection;

class TopicTestHelper extends TestHelper {

	public $exchangeName;
	public $queues = [];

	public function __construct(AMQPConnection $conn) {
		parent::__construct($conn->channel());

		$this->exchangeName = 'TestTopic_'.uniqid();

		$this->channel->exchange_declare($this->exchangeName, 'topic', false, true, false);
	}

	public function createQueue($routingKey) {
		list($queueName, , ) = $this->channel->queue_declare('');

		$this->channel->queue_bind($queueName, $this->exchangeName, $routingKey);

		$this->queues[$routingKey] = $queueName;

		return $queueName;
	}

	public function getMessages($routingKey) {
		$msgs = [];

		if (isset($this->queues[$routingKey])) {
			$queueName = $this->queues[$routingKey];
			
			do {
				$msg = $this->consumeOne($queueName);
				if (!is_null($msg)) {
					$msgs[] = $msg;
				}
			} while (!is_null($msg));
		}

		return $msgs;
	}

	public function teardown() {
		foreach ($this->queues as $routingKey => $queueName) {
			$this->channel->queue_unbind($queueName, $this->exchangeName, $routingKey);
			$this->channel->queue_delete($queueName, false, false, true);
		}

		$this->channel->exchange_delete($this->exchangeName);
	}

}