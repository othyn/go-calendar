dc = docker compose $(1)
gocal = $(call dc,run -it --rm gocal $(1))
composer = $(call dc,run -it --rm --entrypoint /usr/local/bin/composer gocal $(1))

.PHONY: build
build:
	$(call dc,build)

.PHONY: up
up:
	$(call dc,up -d)

.PHONY: down
down:
	$(call dc,down)

.PHONY: restart
restart: down up

.PHONY: install
install: up
	$(call composer,install)

.PHONY: lint
lint: up
	$(call composer,php-cs-fixer)

.PHONY: gen
gen: up
	$(call gocal,gen)

.PHONY: site
site: up
	$(call gocal,site)

.PHONY: all
all: gen site
