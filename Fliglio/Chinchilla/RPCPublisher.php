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


	/**
	 * @param MappableApi $api
	 * @param $queueName
	 * @param Filter[] $filters
	 * @return RPCPublisher
	 */
	public function publish(MappableApi $api, $queueName, $filters = []) {
		$worker = new WorkerPublisher($this->connection, $queueName);

		foreach ($filters as $filter) {
			$worker->addFilter($filter);
		}

		return new self($this->connection, $worker->publish($api, [
			'reply_to' => $queueName .'.reply',
		]));
	}

	/**
	 * @param Message $msg
	 * @param MappableApi $api
	 * @param Filter[] $filters
	 * @return null|AMQPMessage
	 */
	public function publishReply(Message $msg, MappableApi $api, $filters = []) {
		if (!$msg->getReplyTo()) {
			return null;
		}

		$queueName = $msg->getReplyTo();

		$worker = new WorkerPublisher($this->connection, $queueName);

		foreach ($filters as $filter) {
			$worker->addFilter($filter);
		}

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

			/** @var \PhpAmqpLib\Message\AMQPMessage $msg */
			$msg = $worker->consumeOne($queueName);

			if (is_null($msg)) {
				usleep(250000); // 125000 1/8s, 250000 1/4s

			} else {

				if ($msg && $this->hasMessageExpired($msg)) {
					$worker->ack($msg);

				} else if ($msg->has('message_id') && $msg->get('message_id') == $msgId) {
					$worker->ack($msg);
					return $msg;
				}
			}

		} while (true);
	}

	private function hasMessageExpired($msg) {
		$appHeaders = $msg->get('application_headers')->getNativeData();
		return array_key_exists('expiration', $appHeaders)
			&& $appHeaders['expiration']->getTimestamp() < time();
	}

}
