<?php

namespace Fliglio\Chinchilla;

use PhpAmqpLib\Connection\AMQPConnection;

// Supports multiple connections via configure([]);
class Connection {

	private static $configs = [];
	private static $connections = [];

	public static function configure(array $configs) {
		foreach ($configs as $config) {
			if (!$config->getName()) {
				throw new ConnectionException("Name must be set and uniqie for multiple connections.");
			}
			self::addConfig($config);
		}
	}

	public static function addConfig(Config $config) {
		if (isset(self::$configs[$config->getName()])) {
			throw new ConnectionException(sprintf("Config[%s] already set.", $config->getName()));
		}

		self::$configs[$config->getName()] = $config;
	}

	public static function hasConnection() {
		return count(self::$connections) > 0;
	}

	public static function hasConfigs() {
		return count(self::$configs) > 0;
	}

	public static function get($name = null) {
		if (!self::hasConfigs()) {
			throw new ConnectionException("No configs set on connection");
		}

		// first config is "default" connnection
		if (is_null($name)) {
			$configs = array_values(self::$configs);
			$name = $configs[0]->getName();
		}

		if (!isset(self::$connections[$name])) {
			$config = self::$configs[$name];

			self::$connections[$name] = new AMQPConnection(
				$config->getHost(), 
				$config->getPort(), 
				$config->getUser(), 
				$config->getPassword(), 
				$config->getVirtualHost()
			);
		}

		return self::$connections[$name];
	}
	
	public static function close() {
		foreach (self::$connections as $conn) {
			$conn->channel()->close();
			$conn->close();
		}

		self::$connections = [];
	}

	public static function reset() {
		self::close();
		self::$configs = [];
	}

}
