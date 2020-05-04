<?php

namespace Fliglio\Chinchilla;

use Fliglio\Chinchilla\Helper\Md5Filter;
use Fliglio\Chinchilla\Helper\StrRevFilter;
use Fliglio\Chinchilla\Helper\TestUser;
use Fliglio\Chinchilla\Helper\WorkerTestHelper;
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

		$this->assertEquals(3, count($msgs));
	}

	public function testPublish_canUseFilter() {
		// given
		$jsonEncodedUser = json_encode(TestUser::getApiMapper()->marshal(new TestUser()));

		// when
		$this->publisher->publish(new TestUser);
		$this->publisher->publish(new TestUser);

		$this->publisher->addFilter(new Md5Filter());
		$this->publisher->publish(new TestUser);

		// then
		$msgs = $this->testHelper->getMessages();

		$this->assertEquals(3, count($msgs));

		$filtered = array_pop($msgs);
		$this->assertEquals(md5($jsonEncodedUser), $filtered);

		foreach ($msgs as $msg) {
			$this->assertEquals($jsonEncodedUser, $msg);
		}
	}
	
	public function testPublish_canUseFilters() {
		// given
		$jsonEncodedUser = json_encode(TestUser::getApiMapper()->marshal(new TestUser()));
		
		// when
		$this->publisher->publish(new TestUser);

		$this->publisher->addFilter(new Md5Filter);
		$this->publisher->publish(new TestUser);

		$this->publisher->addFilter(new StrRevFilter);
		$this->publisher->publish(new TestUser);

		// then 
		$msgs = $this->testHelper->getMessages();

		$this->assertEquals(3, count($msgs));
		
		$strrev = array_pop($msgs);
		$md5 = array_pop($msgs);
		$unfiltered = array_pop($msgs);

		$this->assertEquals(strrev(md5($jsonEncodedUser)), $strrev);
		$this->assertEquals(md5($jsonEncodedUser), $md5);
		$this->assertEquals($jsonEncodedUser, $unfiltered);
		
	}
}
