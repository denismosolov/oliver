
SHELL := /bin/bash

ifneq (,$(wildcard ./.env))
	include .env
	export $(shell sed 's/=.*//' .env)
endif

all: init_yc create_function create_dotenv

test: phpcs phpunit

develop: composer_install

phpcs:
	docker pull php:7.4-cli
	docker run --rm --interactive --tty \
		--volume ${PWD}:/usr/src/oliver \
		--workdir /usr/src/oliver \
		php:7.4-cli ./vendor/bin/phpcs --standard=ruleset.xml

phpunit:
	docker pull php:7.4-cli
	docker run --rm --interactive --tty \
		--volume ${PWD}:/usr/src/oliver \
		--workdir /usr/src/oliver \
		php:7.4-cli ./vendor/bin/phpunit

logs:
	yc config profile activate oliver
	yc serverless function logs oliver

fixpsr12:
	./vendor/bin/phpcbf --standard=ruleset.xml

init_yc:
	yc init

create_function:
	yc config profile activate oliver
	yc serverless function create \
		--name=oliver \
		--description="Обработчик навыка Оливер"

composer_install:
	docker pull composer:1.10.13
	docker run --rm --interactive --tty \
	  --user $$(id -u):$$(id -g) \
	  --volume ${PWD}:/app \
	  composer:1.10.13 install

create_dotenv:
	cp -i .env.example .env

create_version:
	zip -r oliver.zip src index.php composer.json composer.lock
	yc config profile activate oliver
	yc serverless function version create \
		--function-name=oliver \
		--runtime php74 \
		--entrypoint index.main \
		--memory 128m \
		--execution-timeout 3s \
		--environment="TINKOFF_OPEN_API_EXCHANGE=${TINKOFF_OPEN_API_EXCHANGE}" \
		--source-path ./oliver.zip