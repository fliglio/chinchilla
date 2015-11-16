<?php

namespace Fliglio\Chinchilla;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Fliglio\Web\MappableApi;

class TopicPublisher {

	public $channel;
	public $exchangeName;
	public $routingKey;

	public function __construct(AMQPChannel $channel, $exchangeName, $routingKey) {
		$this->channel      = $channel;
		$this->exchangeName = $exchangeName;
		$this->routingKey   = $routingKey;

		$this->channel->exchange_declare(
			$this->exchangeName,
			$type = 'topic',
			$passive = false,
			$durable = true,
			$auto_delete = false
		);
	}

	public function publish(MappableApi $api, $headers=[]) {
		$apiClassName = get_class($api);

		$headers['created'] = ['T', time()];
		$headers['test']    = ['I', (int) false];

		$vo = $apiClassName::getApiMapper()->marshal($api);

		$msg = new AMQPMessage(json_encode($vo), [
			'content_type'        => 'text/plain', 
			'message_id'          => uniqid(),
			'delivery_mode'       => 2, // persistent : 2, non-persistent : 1
			'application_headers' => $headers
		]);

		$this->channel->basic_publish(
			$msg, 
			$this->exchangeName, 
			$this->routingKey, 
			$mandatory=true
		);
	}

}