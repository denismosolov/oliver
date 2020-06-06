
SHELL := /bin/bash

include .env
export $(shell sed 's/=.*//' .env)

all: composer_install init_yc create_function create_dotenv

test:
	./vendor/bin/phpunit

init_yc:
	yc init

create_function:
	yc serverless function create \
		--name=oliver \
		--description="Обработчик навыка Оливер"

composer_install:
	composer install

create_dotenv:
	cp -i .env.example .env

create_version: test
	zip oliver.zip index.php composer.json composer.lock src/Application.php
	yc serverless function version create \
		--function-name=oliver \
		--runtime php74 \
		--entrypoint index.main \
		--memory 128m \
		--execution-timeout 3s \
		--environment="SESSION_USER_ID=${SESSION_USER_ID}" \
		--source-path ./oliver.zip