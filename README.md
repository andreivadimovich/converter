#Конвертер валют


Для запуска проекта:

1) склонировать репозиторий


2) дать докеру доступ на директории /docker/mysql/ , /docker/nginx/ , /app/


3) в директории /docker выполнить:
<pre>
docker-compose up --build -d
</pre>


4) подключиться к контейнеру PHP:
<pre>
docker exec -it php74-container bash
</pre>


5) выполнить в контейнере:
<pre>
composer update
</pre>


6) выполнить миграцию:
<pre>
php bin/console doctrine:migrations:migrate
</pre>


7) импортировать данные из источников:
<pre>
php bin/console import-currency
</pre>


Перейти браузером на URL - http://localhost:8080.

Настройки источников данных находятся в /app/src/Command/importCurrencyCommand.php