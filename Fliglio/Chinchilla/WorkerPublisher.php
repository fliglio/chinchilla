<?php

namespace Fliglio\Chinchilla;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Fliglio\Web\MappableApi;

class WorkerPublisher {

	public $channel;
	public $queueName;

	public function __construct(
			AMQPChannel $channel, 
			$queueName, 
			$passive=false, 
			$durable=true, 
			$exclusive=false, 
			$auto_delete=false, 
			$nowait=false, 
			$arguments=null) {

		$this->channel   = $channel;
		$this->queueName = $queueName;

		$channel->queue_declare(
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

		$this->channel->basic_publish($msg, '', $this->queueName);
	}

}