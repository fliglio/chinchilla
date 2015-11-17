<?php

namespace Fliglio\Chinchilla\Test;

use Fliglio\Web\ApiMapper;

class TestUserApiMapper implements ApiMapper {
	public function marshal($api) {
		return [
			'foo' => $api->getFoo(),
		];
	}
	public function unmarshal($params) {
		return (new User())->setFoo($params['foo']);
	}
}
