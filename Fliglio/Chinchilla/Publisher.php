<?php

namespace Fliglio\Chinchilla;

use Fliglio\Web\MappableApi;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class Publisher {

	protected $connection;
	protected $channel;

	public function __construct(AMQPConnection $connection) {
		$this->connection = $connection;
		$this->channel    = $connection->channel();
	}

	protected function toAMQPMessage(MappableApi $api, $headers = [], $msgId = null) {
		$apiClassName = get_class($api);

		$headers['created'] = ['T', time()];

		$vo = $apiClassName::getApiMapper()->marshal($api);

		$amqpHeaders = [
			'content_type'        => 'application/json', 
			'message_id'          => !is_null($msgId) ? $msgId : uniqid(),
			'delivery_mode'       => 2, // persistent : 2, non-persistent : 1
			'application_headers' => $headers
		];

		return new AMQPMessage(json_encode($vo), $amqpHeaders);
	}

	public function consumeOne($queueName) {
		$msg = $this->channel->basic_get($queueName, $ack=false);
		return $msg;
	}

	public function ack(AMQPMessage $msg) {
		$this->channel->basic_ack($msg->delivery_info['delivery_tag']);
	}

}
