<?php

namespace Fliglio\Chinchilla;

use Fliglio\Chinchilla\Test\TestUser;
use Fliglio\Chinchilla\Test\WorkerTestHelper;
use PhpAmqpLib\Connection\AMQPConnection;

class WorkerPublisherTest extends \PHPUnit_Framework_TestCase {

	/** @var  WorkerTestHelper */
	private $testHelper;
	
	/** @var  WorkerPublisher */
	private $publisher;
	
	public function setup() {
		$conn = new AMQPConnection('localhost', '5672', 'guest', 'guest');

		$this->testHelper = new WorkerTestHelper($conn, 'test.sandbox.workertest');

		$this->publisher = new WorkerPublisher($conn, 'test.sandbox.workertest');
	}

	public function testPublish() {
		// when
		$this->publisher->publish(new TestUser);
		$this->publisher->publish(new TestUser);
		$this->publisher->publish(new TestUser);

		// then 
		$msgs = $this->testHelper->getMessages();

		$this->assertEquals(count($msgs), 3);
	}
	
	public function testPublish_shouldUseFilters() {
		// given
		$jsonEncodedUser = json_encode(TestUser::getApiMapper()->marshal(new TestUser()));
		
		// when
		$this->publisher->publish(new TestUser);
		$this->publisher->publish(new TestUser);
		
		$this->publisher->addFilter(new Md5Filter());
		$this->publisher->publish(new TestUser);

		// then 
		$msgs = $this->testHelper->getMessages();

		$this->assertEquals(count($msgs), 3);
		
		$filtered = array_pop($msgs);
		$this->assertEquals(md5($jsonEncodedUser), $filtered);

		foreach ($msgs as $msg) {
			$this->assertEquals($jsonEncodedUser, $msg);
		}
	}
}

class Md5Filter implements Filter {

	public function apply($str) {
		return md5($str);
	}
}
