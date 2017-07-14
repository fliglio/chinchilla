<?php


namespace Fliglio\Chinchilla\Test;


use Fliglio\Chinchilla\Filter;

class StrRevFilter implements Filter {

	public function apply($str) {
		return strrev($str);
	}
}