<?php

namespace Fliglio\Chinchilla;

use Fliglio\Flfc\Context;
use Fliglio\Routing\Injectable;

class Message implements Injectable {

	private $headers;
	private $created;
	private $expiration;

	public function setHeaders($headers) {
		$this->headers = $headers;
		return $this;
	}
	public function setCreated($created) {
		$this->created = $created;
		return $this;
	}
	public function setExpiration($expiration) {
		$this->expiration = $expiration;
		return $this;
	}

	public function getHeader($key) {
		return isset($this->headers[$key]) ? $this->headers[$key] : null;
	}
	public function getCreated() {
		return $this->created;
	}
	public function getExpiration() {
		return $this->expiration;
	}

	public function getClassName() {
		return __CLASS__;
	}

	public function create(Context $context, $paramName) {
		$headers = $context->getRequest()->getHeaders();

		$msg = (new self());

		$created    = isset($headers['created']) ? $headers['created'] : null;
		$expiration = isset($headers['expiration']) ? $headers['expiration'] : null;

		$msg->setHeaders($headers);
		$msg->setExpiration($expiration);
		$msg->setCreated($created);

		return $msg;
	}

}
