.RECIPEPREFIX +=

help:
    @echo
    @echo "\033[0;33mAvailable targets:\033[0m"
    @cat Makefile | sed 's/: /: → /' | GREP_COLORS="ms=00;32" grep --colour=always -P '^[a-z0-9].+:' | column -s ':' -t  | sed 's/^/  /'


test: test-integration


test-integration: phpunit-integration


phpunit-integration:
    vendor/bin/phpunit -c . --testsuite integration
