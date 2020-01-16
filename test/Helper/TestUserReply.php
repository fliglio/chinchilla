<?php

namespace Fliglio\Chinchilla\Helper;

use Fliglio\Web\MappableApi;
use Fliglio\Web\MappableApiTrait;

class TestUserReply implements MappableApi {
	use MappableApiTrait;

	private $id = 1;

	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
		return $this;
	}

}