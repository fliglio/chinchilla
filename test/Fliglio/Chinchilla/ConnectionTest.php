<?php

namespace Fliglio\Chinchilla;

class ConnectionTest extends \PHPUnit_Framework_TestCase {

	public function teardown() {
		Connection::reset();
	}

	public function testSingleConnectionHandling() {
		// given
		Connection::addConfig(new Config);

		// when
		$ch = Connection::get();

		// then 
		$this->assertTrue($ch->isConnected());
	}

	/**
	 * @expectedException Fliglio\Chinchilla\ConnectionException
	 */
	public function testConnectionRequired() {
		// when
		Connection::get();
	}

	public function testMultipleConnections() {
		// given
		$configs = [
			(new Config)->setName('dc1'),
			(new Config)->setName('dc2'),
		];
		Connection::configure($configs);
		$this->assertFalse(Connection::hasConnection());

		// when
		$ch = Connection::get('dc1');

		// then 
		$this->assertTrue($ch->isConnected());
		$this->assertTrue(Connection::hasConnection());

	}

}