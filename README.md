# Оливер — навык для голосового помошника Алиса

Оливер расскажет о стоимости ваших акций в Тинькофф Инвестиции.

## Установка (Ubuntu 20.04)

### Необходимые пакеты
```
apt update
apt install php7.4 php7.4-curl make
```
### Composer
Инструкция https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos

### YC CLI
Инструкция https://cloud.yandex.ru/docs/cli/operations/install-cli

## Инициализация
```
git clone https://github.com/denismosolov/oliver.git
cd oliver
make
```
В процессе интерактивного создания профиля CLI будет поэтапно предлагать задать базовые параметры профиля. Cправка https://cloud.yandex.ru/docs/cli/operations/profile/profile-create#interactive-create

В качестве имени профиля укажите `oliver`, а в качестве каталога по умолчанию создайте новый каталог `oliver`. Можете указать другие названия, это ни на что не повлияет.

Если всё сделаете правильно, то появится функция с именем `oliver` в Яндекс.Облаке.

## Деплой в Яндекс.Облако
```
make create_version
```

Если всё сделаете правильно, то у функции `oliver` появится версия и вы увидите исходный код в Яндекс.Облаке.

## Навык в Яндекс.Диалоги


## Планы на будущее
1. Хочу в каталог навыков Алисы https://dialogs.yandex.ru/store (ждём  TinkoffCreditSystems/invest-openapi#217)
2. Хочу выставлять и отменять заявки на покупку и продажу акций через навык