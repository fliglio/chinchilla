<?php

namespace Fliglio\Chinchilla;

use Fliglio\Web\MappableApi;
use PhpAmqpLib\Connection\AMQPConnection;

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

	public function publish(MappableApi $api, $headers=[], $msgId=null) {
		$msg = $this->toAMQPMessage($api, $headers, $msgId);

		$this->channel->basic_publish($msg, '', $this->queueName);

		return $msg;
	}

}
