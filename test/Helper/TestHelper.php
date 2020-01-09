<?php

namespace Fliglio\Chinchilla\Helper;

use PhpAmqpLib\Channel\AMQPChannel;

class TestHelper {

	protected $channel;

	public function __construct(AMQPChannel $channel) {
		$this->channel = $channel;
	}

	public function consumeOne($queueName) {
		$msg = $this->channel->basic_get($queueName, $ack=false);

		if ($msg) {
			$this->channel->basic_ack($msg->delivery_info['delivery_tag']);
			return $msg->body;
		} else {
			return null;
		}
	}

}