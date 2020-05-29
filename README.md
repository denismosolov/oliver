# Оливер — навык для голосового помощника Алиса

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
Перед деплоем загляните в файл `.env` в корневой директории проекта и замените идентификатор пользователя Яндекса в `SESSION_USER_ID` на свой. Этот идентификатор используется для аутентификации в навыке, чтобы никто кроме вас не смог запустить ваш навык, зная активационное имя. Этот идентификатор из `.env` будет передан в переменную окружения функции в Яндекс.Облаке. Я не уверен, что это безопасно, поэтому используйте на свой страх и риск.

Для справки посмотрите session.user.user_id в https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#request.

А вот и команда для деплоя кода в Яндекс.Облако.
```
make create_version
```

Если всё сделаете правильно, то у функции `oliver` появится версия и вы увидите исходный код в Яндекс.Облаке, а так же переменную окружения `SESSION_USER_ID`.

## Навык в Яндекс.Диалоги
Справка https://yandex.ru/dev/dialogs/alice/doc/smart-home/start-docpage/

Не забудьте указать функцию
![Selection_018](https://user-images.githubusercontent.com/3057626/83176044-85456180-a125-11ea-994b-6087a78f42f8.png)

## Планы на будущее
1. Хочу в каталог навыков Алисы https://dialogs.yandex.ru/store (ждём  TinkoffCreditSystems/invest-openapi#217)
2. Хочу выставлять и отменять заявки на покупку и продажу акций через навык
