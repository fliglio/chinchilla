<?php

namespace Fliglio\Chinchilla;

use Fliglio\Flfc\Context;

class Message {

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

	public function getId() {
		return $this->getHeader('message_id');
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

}
