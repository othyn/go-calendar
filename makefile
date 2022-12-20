.PHONY: install
install:
	composer install

.PHONY: lint
lint:
	composer php-cs-fixer

.PHONY: build
build:
	bin/gocal build
