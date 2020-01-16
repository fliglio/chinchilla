<?php

namespace Fliglio\Chinchilla\Helper;

use Fliglio\Chinchilla\Filter;

class StrRevFilter implements Filter {

	public function apply($str) {
		return strrev($str);
	}
}