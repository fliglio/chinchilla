<?php

namespace Fliglio\Chinchilla\Helper;

use PhpAmqpLib\Connection\AMQPConnection;

class WorkerTestHelper extends TestHelper {

	public $queueName;
	public $queues = [];

	public function __construct(AMQPConnection $conn, $queueName) {
		parent::__construct($conn->channel());

		$this->queueName = $queueName;

		$this->channel->queue_declare($this->queueName, false, true, false, false);
	}

	public function getMessages() {
		$msgs = [];

		do {
			$msg = $this->consumeOne($this->queueName);
			if (!is_null($msg)) {
				$msgs[] = $msg;
			}
		} while (!is_null($msg));

		return $msgs;
	}

	public function tearDown() {
		$this->channel->queue_delete($this->queueName, false, false, true);
	}

}