
SHELL := /bin/bash

include .env
export $(shell sed 's/=.*//' .env)

all: composer_install init_yc create_function create_dotenv

test:
	./vendor/bin/phpcs --standard=PSR12 src/ tests/
	./vendor/bin/phpunit

fixpsr12:
	./vendor/bin/phpcbf --standard=PSR12 src/ tests/

init_yc:
	yc init

create_function:
	yc config profile activate oliver
	yc serverless function create \
		--name=oliver \
		--description="Обработчик навыка Оливер"

composer_install:
	composer install

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