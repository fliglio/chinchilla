deps:
	composer up

test:
	php ./vendor/bin/phpunit -c phpunit.xml test/

testWithCoverage:
	php ./vendor/bin/phpunit -c phpunit-coverage.xml test/

.PHONY: test
