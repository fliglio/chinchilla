<?php

namespace Fliglio\Chinchilla;

use PhpAmqpLib\Connection\AMQPConnection;
use Fliglio\Web\MappableApi;

class TopicPublisher extends Publisher {

	private $exchangeName;

	public function __construct(AMQPConnection $connection, $exchangeName, $passive=false, $durable=true, $auto_delete=false) {
		parent::__construct($connection);

		$this->exchangeName = $exchangeName;

		$this->channel->exchange_declare(
			$this->exchangeName,
			$type = 'topic',
			$passive,
			$durable,
			$auto_delete
		);
	}

	public function publish(MappableApi $api, $routingKey, $headers=[]) {
		$msg = $this->toAMQPMessage($api, $headers);

		$this->channel->basic_publish(
			$msg, 
			$this->exchangeName, 
			$routingKey, 
			$mandatory=true
		);

		return $msg;
	}

}