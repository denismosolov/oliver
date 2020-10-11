
SHELL := /bin/bash

ifneq (,$(wildcard ./.env))
	include .env
	export $(shell sed 's/=.*//' .env)
endif

all: composer_install init_yc create_function create_dotenv

test:
	./vendor/bin/phpcs --standard=ruleset.xml
	./vendor/bin/phpunit

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
ifeq (,$(wildcard ./composer.phar))
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php -r "if (hash_file('sha384', 'composer-setup.php') === '795f976fe0ebd8b75f26a6dd68f78fd3453ce79f32ecb33e7fd087d39bfeb978342fb73ac986cd4f54edd0dc902601dc') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
	php composer-setup.php
	php -r "unlink('composer-setup.php');"
endif
	php composer.phar install

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