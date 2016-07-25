<?php

namespace Fliglio\Chinchilla;

use PhpAmqpLib\Connection\AMQPConnection;
use Fliglio\Web\MappableApi;

class WorkerPublisher extends Publisher {

	private $queueName;

	public function __construct(
			AMQPConnection $connection, 
			$queueName, 
			$passive=false, 
			$durable=true, 
			$exclusive=false, 
			$auto_delete=false, 
			$nowait=false, 
			$arguments=null) {

		parent::__construct($connection);

		$this->queueName = $queueName;

		$this->channel->queue_declare(
			$queueName, 
			$passive,
			$durable,
			$exclusive,
			$auto_delete,
			$nowait,
			$arguments
		);
	}

	public function publish(MappableApi $api, $headers=[]) {
		$msg = $this->toAMQPMessage($api, $headers);

		$this->channel->basic_publish($msg, '', $this->queueName);

		return $msg;
	}

	// Polls a queue looking for message with a specific ID
	public function findMessage($msgId, $queueName=null, $timeout = 60) {
		$startTime = time();
		$queueName = $queueName ? $queueName : $this->queueName;

		do {
			if (time() - $startTime > $timeout) {
				throw new TimeoutException(sprintf("Timeout of '%s' exceeded", $timeout));
			}

			$msg = $this->consumeOne($queueName);

			if (is_null($msg)) {
				usleep(250000); // 125000 1/8s, 250000 1/4s
			} else {
				if ($msg->has('message_id') && $msg->get('message_id') == $msgId) {
					return $msg;
				}
			}

		} while (true);
	}

}
