# Тестовое задание от Freedom Finance
Это `REST API` приложение, без `GUI`.

## Состав
Проект представляет собой сборку из докер образов, аккамулированных в `docker-compose.yml`.
В составе:
1. Symfony app на базе ` php:8.3-cli-alpine`. Также в этом контейнере работает `OpenRC` для запуска Симфони воркера.
2. MySQL.
3. PhpMyAdmin.
4. RabbitMQ.

## Эндпоинты
### http://127.0.0.1:3810/exchange
Главный url проекта. Принимаает на вход только `POST` запросы с `JSON` пэйлопдом в `RAW` формате.
Его вид следующий:
```
{
    "date": "2024-06-05",
    "currency": "USD"
}
```
`date` - Обязательный параметр, который принимает дату только в формате `Y-m-d`

`currency` - Обязательный парамтр, который принимает код валюты из списка: `AUD`,`AZN`,`GBP`,`AMD`,`BYN`,`BGN`,`BRL`,`HUF`,`VND`,`HKD`,
`GEL`,`DKK`, `AED`,`USD`,`EUR`,`EGP`,`INR`,`IDR`,`KZT`,`CAD`,
`QAR`,`KGS`,`CNY`,`MDL`,`NZD`,`NOK`,`PLN`,`RON`,`XDR`,`SGD`,
`TJS`,`THB`,`TRY`,`TMT`,`UZS`,`UAH`,`CZK`,`SEK`,`CHF`,`RSD`,
`ZAR`,`KRW`,`JPY`.

`baseCurrency` - Опциональный парамтр, который принимает только `RUR`.

`source` - Опциональный парамтр, который принимает только `cbr.ru`.

### http://127.0.0.1:3811
Url `PhpMyAdmin`.

### http://127.0.0.1:3813
Url дешборда `RabbitMQ`. Детали: `guest`/`guest`

## Испорльзование
1. Открыть консоль в корне проекта и выполнить `make init`. Эта комманда выполнить установку и инициализацию всех компонентов проекта. После сообщения в консоли "Project initialized!" можно следовать дальнейшим шагам.
2. В той же консоли выполнить комманду `rc-service symfony-worker start`, которая запустит фоновый процесс Симфони воркера.
3. В той же консоли выполнить комманду `php bin/console app:fetch-exchange-rates`. У этой комманды есть два опциональных параметра: `--days` с указание количества дней для фетча (по умолчанию 180) и `--source` для указания источника фетча (по-умолчанию cbr.ru и это единственный вариант).
4. Открыть `Postman` или любую другую утилиту для отправки `HTTP` запросов и протестировать работу эндпоинта http://127.0.0.1:3810/exchange с телом запроса из примера выше.

## Внутреннее устройство
Архитектура Симфони приложения старается следовать полиморфному принципу ООП, а потому обращение к cbr.ru происходит через интерфейс.
По этой же причине в приложении заложен параметр `source` в теле `JSON`, который может быть использован если будет добавлен новый источник данных о стоимости валют.
Тоже относится и к команде фетча, которая тоже реализует соответсвуюший интерфейс.

Изучая доки сbr.ru я так и не смог найти способ задать базовую валюту вместо `RUR`, так что эта валюта является базовой в прокте без возможности сменить её.

При выполнении команды `app:fetch-exchange-rates` в `message bus` кладутся команды, реализующие интерфейс `FetchExchangeRatesCommandInterface`.
Объекты-команды, реализующие интерфейс, предполагают хранение всех необходимых параметров для выполнения фетча и сохранении ответа в БД, в таблице `exchange` с указанием источника.
Часть этих параметров зашито в сам объект, а дата передаётся через сеттер.
Конкретная объект-команда определяется через `TaggedLocator` Симфони, через опцию `--source`. Такой же механизм работает и в контроллере.

## Детали работы с брокером сообщений
В конфиге `config/packages/messenger.yaml` содержится логика взаимодействия с брокером. Создано два транспорта: `async_fetch_exchange_rate_from_cbr` для данных специфичных cbr.ru и `failure_fetch_exchange_rate_from_cbr` для хранения неуспешно отправленных сообшений cbr.ru. 

Для лимитирования сообщений к cbr.ru используется `rate_limiter` с ограничением на 1 сообщение раз в 10 секунд по `fixed_window` политике.

Транспорт `async_fetch_exchange_rate_from_cbr` настроен для коммуникации с `RabbitMQ`, тогда как `failure_fetch_exchange_rate_from_cbr` работает с доктриной и складывает неуспешно отправленные сообщения в таблицу `messenger_failed_transport`.

