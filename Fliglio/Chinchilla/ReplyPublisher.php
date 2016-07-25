<?php

namespace Fliglio\Chinchilla;

use Fliglio\Web\MappableApi;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ReplyPublisher extends Publisher {

	public function publish(Message $msg, MappableApi $api) {
		if (!$msg->getHeader('reply_to')) {
			return null;
		}

		$this->mkQueue($msg->getHeader('reply_to'));

		$amqpMsg = $this->toAMQPMessage(
			$api, 
			[], 
			$msg->getHeader('message_id'), 
			strtotime('+5 minutes')
		);

		$this->channel->basic_publish($amqpMsg, '', $msg->getHeader('reply_to'));
	}

	// Polls a queue looking for message with a specific ID
	public function getReply(AMQPMessage $amqpMsg, $timeout = 60) {
		$msgId = $amqpMsg->get('message_id');
		$queueName = $amqpMsg->get('application_headers')['reply_to'];

		$this->mkQueue($queueName);

		$startTime = time();

		do {
			if (time() - $startTime > $timeout) {
				throw new TimeoutException(sprintf("Timeout of '%s' exceeded", $timeout));
			}

			$msg = $this->consumeOne($queueName);

			if (is_null($msg)) {
				usleep(250000); // 125000 1/8s, 250000 1/4s

			} else {
				if ($msg && $msg->has('expiration') && $msg->has('expiration') < time()) {
					$this->channel->basic_ack($msg->delivery_info['delivery_tag']);

				} else if ($msg->has('message_id') && $msg->get('message_id') == $msgId) {
					return $msg;
				}
			}

		} while (true);
	}

	private function mkQueue($queueName) {
		$this->channel->queue_declare(
			$queueName, 
			$passive     = false, 
			$durable     = true, 
			$exclusive   = false, 
			$auto_delete = false, 
			$nowait      = false, 
			$arguments   = null
		);
	}

}
