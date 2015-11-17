<?php

namespace Fliglio\Chinchilla\Test;

use Fliglio\Web\MappableApi;
use Fliglio\Web\MappableApiTrait;

class TestUser implements MappableApi {
	use MappableApiTrait;
	private $foo = 'bar';
	public function getFoo() {
		return $this->foo;
	}
	public function setFoo($foo) {
		$this->foo = $foo;
		return $this;
	}
}