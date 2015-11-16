<?php

namespace Fliglio\Chinchilla;

use Fliglio\Chinchilla\Test\TestUser;
use Fliglio\Chinchilla\Test\TopicTestHelper;

class TopicPublisherTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		Connection::addConfig(new Config);

		$this->testHelper = new TopicTestHelper();

		$this->publisher = new TopicPublisher(Connection::get()->channel(), $this->testHelper->exchangeName, 'test.sandbox.update');
	}

	public function teardown() {
		Connection::reset();
	}

	public function testPublish_GlobSubscriber() {
		// given
		$this->testHelper->createQueue('test.sandbox.*');

		// when
		$this->publisher->publish(new TestUser);
		$this->publisher->publish(new TestUser);
		$this->publisher->publish(new TestUser);

		// then 
		$msgs = $this->testHelper->getMessages('test.sandbox.*');

		$this->assertEquals(count($msgs), 3);
	}

	public function testPublish_NoSubscriber() {
		// given
		$this->testHelper->createQueue('test.sandbox.add');

		// when
		$this->publisher->publish(new TestUser);

		// then
		$msgs = $this->testHelper->getMessages('test.sandbox.add');

		$this->assertEquals(count($msgs), 0);
	}

	public function testPublish_DirectSubscriber() {
		// given
		$this->testHelper->createQueue('test.sandbox.update');

		// when
		$this->publisher->publish(new TestUser);

		// then
		$msgs = $this->testHelper->getMessages('test.sandbox.update');

		$this->assertEquals(count($msgs), 1);
	}

}
