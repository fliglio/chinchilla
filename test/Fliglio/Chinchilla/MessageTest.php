<?php

namespace Fliglio\Chinchilla;

use Fliglio\Flfc\Context;
use Fliglio\Flfc\Request;
use Fliglio\Flfc\Response;

class MessageTest extends \PHPUnit_Framework_TestCase {

	public function testHeaderParsing() {
		// given
		$req = new Request();
		$req->addHeader('created', '2010-10-10');
		$req->addHeader('expiration', '2010-10-11');
		$req->addHeader('custom-header', 'foo');
		
		$ctx = new Context($req, new Response);
		$injectable = new Message();

		// when
		$msg = $injectable->create($ctx, '');

		// then
		$this->assertEquals($msg->getCreated(), '2010-10-10');
		$this->assertEquals($msg->getExpiration(), '2010-10-11');
		$this->assertEquals($msg->getHeader('custom-header'), 'foo');
	}

}