<?php

namespace Fliglio\Chinchilla;

use Fliglio\Flfc\Context;
use Fliglio\Routing\Injectable;

class InjectableMessage implements Injectable {

	const MESSAGE_CLASSNAME = "Fliglio\Chinchilla\Message";


	public function getClassname() {
		return MESSAGE_CLASSNAME;
	}

	public function create(Context $context, $paramName) {
		$headers = $context->getRequest()->getHeaders();

		$msg = new Message();
	

		$created    = isset($headers['created']) ? $headers['created'] : null;
		$expiration = isset($headers['expiration']) ? $headers['expiration'] : null;

		$msg->setHeaders($headers);
		$msg->setExpiration($expiration);
		$msg->setCreated($created);

		return $msg;
	}

}

