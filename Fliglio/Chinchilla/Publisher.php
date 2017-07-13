<?php

namespace Fliglio\Chinchilla;

use Fliglio\Web\MappableApi;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class Publisher {

	protected $connection;
	protected $channel;
	/** @var Filter[] */
	protected $filters = [];

	/**
	 * Publisher constructor.
	 * @param AMQPConnection $connection
	 * @param Filter[] $filters
	 */
	public function __construct(AMQPConnection $connection, $filters = []) {
		$this->connection = $connection;
		$this->channel    = $connection->channel();
		foreach ($filters as $filter) {
			$this->addFilter($filter);
		}
	}

	public function addFilter(Filter $filter) {
		$this->filters[] = $filter;
	}

	protected function toAMQPMessage(MappableApi $api, $headers = [], $msgId = null) {
		$apiClassName = get_class($api);

		$headers['created'] = ['T', time()];

		$vo = $apiClassName::getApiMapper()->marshal($api);

		$body = json_encode($vo);

		foreach ($this->filters as $filter) {
			$body = $filter->apply($body);
		}

		$amqpHeaders = [
			'content_type'        => 'application/json',
			'message_id'          => !is_null($msgId) ? $msgId : uniqid(),
			'reply_to'            => isset($headers['reply_to']) ? $headers['reply_to'] : null,
			'message-ttl'         => isset($headers['expiration']) ? $headers['expiration'] : null,
			'delivery_mode'       => 2, // persistent : 2, non-persistent : 1
			'application_headers' => $headers
		];

		return new AMQPMessage($body, $amqpHeaders);
	}

	public function consumeOne($queueName) {
		$msg = $this->channel->basic_get($queueName, $ack=false);
		return $msg;
	}

	public function ack(AMQPMessage $msg) {
		$this->channel->basic_ack($msg->delivery_info['delivery_tag']);
	}

}
