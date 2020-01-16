<?php

namespace Fliglio\Chinchilla\Helper;

use Fliglio\Web\ApiMapper;

class TestUserApiMapper implements ApiMapper {
	public function marshal($api) {
		return [
			'foo' => $api->getFoo(),
		];
	}
	public function unmarshal($params) {
		return (new TestUser())->setId($params['id']);
	}
}
