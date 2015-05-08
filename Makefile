clean:
	rm -rf tests/Fixtures/*/build
	rm -f tests/Fixtures/fixtures_built

tests: clean
	phpunit

tests-debug: clean
	phpunit --stop-on-failure

