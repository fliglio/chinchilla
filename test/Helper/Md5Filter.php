<?php

namespace Fliglio\Chinchilla\Helper;

use Fliglio\Chinchilla\Filter;

class Md5Filter implements Filter {

	public function apply($str) {
		return md5($str);
	}
}