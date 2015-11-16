<?php

namespace Fliglio\Chinchilla;

class Config {

	// defaults
	private $name = 'default';
	private $host = 'localhost';
	private $virtualHost = '/';
	private $user = 'guest';
	private $password = 'guest';
	private $port = '5672';
	private $ssl = false;

	public function isSsl() {
		return $this->ssl;
	}

	public function getName() {
		return $this->name;
	}
	public function getHost() {
		return $this->host;
	}
	public function getVirtualHost() {
		return $this->virtualHost;
	}
	public function getUser() {
		return $this->user;
	}
	public function getPassword() {
		return $this->password;
	}
	public function getPort() {
		return $this->port;
	}

	public function setName($name) {
		$this->name = $name;
		return $this;
	}
	public function setHost($host) {
		$this->host = $host;
		return $this;
	}
	public function setVirtualHost($virtualHost) {
		$this->virtualHost = $virtualHost;
		return $this;
	}
	public function setUser($user) {
		$this->user = $user;
		return $this;
	}
	public function setPassword($password) {
		$this->password = $password;
		return $this;
	}
	public function setPort($port) {
		$this->port = $port;
		return $this;
	}
	public function setSsl($ssl) {
		$this->ssl = (bool)$ssl;
		return $this;
	}

}
