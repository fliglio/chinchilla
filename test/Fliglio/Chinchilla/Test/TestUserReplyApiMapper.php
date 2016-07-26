<?php

namespace Fliglio\Chinchilla\Test;

use Fliglio\Web\ApiMapper;

class TestUserReplyApiMapper implements ApiMapper {
	public function marshal($api) {
		return [
			'id' => $api->getId(),
		];
	}
	public function unmarshal($params) {
		return (new TestUserReply())->setFoo($params['foo']);
	}
}
