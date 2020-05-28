
all: composer_install init_yc create_function

init_yc:
	yc init

create_function:
	yc serverless function create \
		--name=oliver \
		--description="Обработчик навыка Оливер"

composer_install:
	composer install

create_version:
	zip oliver.zip index.php composer.json composer.lock src/Application.php
	yc serverless function version create \
		--function-name=oliver \
		--runtime php74 \
		--entrypoint index.main \
		--memory 128m \
		--execution-timeout 3s \
		--source-path ./oliver.zip