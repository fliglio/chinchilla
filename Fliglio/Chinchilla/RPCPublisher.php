<?php

namespace Fliglio\Chinchilla;

use Fliglio\Web\MappableApi;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RPCPublisher {

	private $connection;
	private $amqpMsg;

	public function __construct(AMQPConnection $conn, AMQPMessage $amqpMsg = null) {
		$this->connection = $conn;
		$this->amqpMsg    = $amqpMsg;
	}

	public function getAmqpMsg() {
		return $this->amqpMsg;
	}

	public function publish(MappableApi $api, $queueName) {
		$worker = new WorkerPublisher($this->connection, $queueName);

		return new self($this->connection, $worker->publish($api, [
			'reply_to' => $queueName .'.reply'
		]));
	}

	public function publishReply(Message $msg, MappableApi $api) {
		if (!$msg->getHeader('reply_to')) {
			return null;
		}

		$queueName = $msg->getHeader('reply_to');

		$worker = new WorkerPublisher($this->connection, $queueName);

		return $worker->publish($api, [
			'expiration' => ['T', strtotime('+5 minutes')],
		], $msg->getId());
	}

	// Polls a queue looking for message with a specific ID
	public function getReply($timeout = 60) {
		if (!$this->amqpMsg) {
			throw new Exception('AMQP message required to get reply');
		}

		$msgId = $this->amqpMsg->get('message_id');
		$queueName = $this->amqpMsg->get('application_headers')['reply_to'];

		$worker = new WorkerPublisher($this->connection, $queueName);

		$startTime = time();

		do {
			if (time() - $startTime > $timeout) {
				throw new TimeoutException(sprintf("Timeout of '%s' exceeded", $timeout));
			}

			$msg = $worker->consumeOne($queueName);

			if (is_null($msg)) {
				usleep(250000); // 125000 1/8s, 250000 1/4s

			} else {
				if ($msg && $msg->has('expiration') && $msg->has('expiration') < time()) {
					$worker->ack($msg);

				} else if ($msg->has('message_id') && $msg->get('message_id') == $msgId) {
					$worker->ack($msg);
					return $msg;
				}
			}

		} while (true);
	}

}
