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

	protected function toAMQPMessage(MappableApi $api, $headers = []) {
		$apiClassName = get_class($api);

		$headers['created'] = ['T', time()];

		$vo = $apiClassName::getApiMapper()->marshal($api);

		$msg = new AMQPMessage(json_encode($vo), [
			'content_type'        => 'application/json', 
			'message_id'          => uniqid(),
			'delivery_mode'       => 2, // persistent : 2, non-persistent : 1
			'application_headers' => $headers,
			'expiration'          => strtotime('+5 minutes'),
		]);

		return $msg;
	}

	protected function consumeOne($queueName) {
		$msg = $this->channel->basic_get($queueName, $ack=false);

		if ($msg && $msg->has('expiration') && $msg->has('expiration') < time()) {
			$this->channel->basic_ack($msg->delivery_info['delivery_tag']);
			return $this->consumeOne();
		}

		return $msg;
	}

}
