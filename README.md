Вот простой и понятный пример. Мы сделаем генератор тяжелых PDF-отчетов.Пользователь нажимает кнопку во Vue,
запрос уходит на бэкенд, Laravel мгновенно ставит задачу в очередь и возвращает ответ. Фронтенд через Pinia
управляет состоянием, а статус задачи проверяется короткими опросами (polling) или веб-сокетами.Так как мы 
не используем Inertia, общение идет через стандартный REST API (Axios).


-laravel
-ручна настройка vuejs без inertia
-pinia
-sail artisan queue:work - черги
-звичайне опитування бекенда (polling) через setInterval
-install axios
-Настройка Фабрики для случайных данных
-Заполнение базы данных (Seeding)
-sail artisan db:seed
- обовязково створити sim-link: sail artisan storage:link - для картинок
- laravel echo pusher install
- Запуск WebSocket-сервера Reverb
- sail artisan reverb:start
- 
