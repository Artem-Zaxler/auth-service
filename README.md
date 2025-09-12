# Мини-сервис авторизации

> Авторизационный шлюз на Symfony 7 + PostgreSQL. Функциональность: управление пользователями, сессиями, токенами, отчётность; доступ через Admin UI, REST API, OAuth2. Документация API доступна через Swagger UI.

## Архитектура
- Слои: DTO + Валидаторы, Репозитории (Doctrine), Сервисы (бизнес-логика), тонкие Контроллеры, Twig-шаблоны для UI.
- Безопасность: Symfony Security, роли `ROLE_ADMIN`/`ROLE_USER`, JWT (LexikJWTAuthenticationBundle), OAuth2 (league/oauth2-server-bundle).
- Данные: PostgreSQL, миграции Doctrine, индексы на ключевых полях.
- Логирование: Monolog в ключевых потоках.

## Быстрый старт

### Предварительные требования
- Docker, Docker Compose
- PHP 8.2+
- Git, Composer

### Развёртывание
```bash
# 1) Клонирование
git clone https://github.com/Artem-Zaxler/auth-service.git
cd auth-service

# 2) Приложение и база в докере
docker-compose up -d database

# 3) Зависимости
composer install

# 4) Миграции
php bin/console doctrine:migrations:migrate --no-interaction

# 5) Фикстуры (создаются тестовые пользователи и OAuth2-клиент)
php bin/console doctrine:fixtures:load --no-interaction

# 6) Запуск встроенного сервера для разработки (8000 порт)
symfony server:start 

# 7) Запуск приложения в продакшене (8080 порт)
docker-compose down
docker-compose up -d --build
```

## Переменные окружения (.env/.env.local)
Минимально проверьте/установите:
- База данных: `DATABASE_URL="postgresql://auth:auth@127.0.0.1:5432/auth?serverVersion=16&charset=utf8"`
- JWT (Lexik):
  - `JWT_SECRET_KEY` — путь к приватному ключу (например, `config/jwt/private.pem`)
  - `JWT_PUBLIC_KEY` — путь к публичному ключу (например, `config/jwt/public.pem`)
  - `JWT_PASSPHRASE` — passphrase приватного ключа
- OAuth2 (League):
  - `OAUTH_PRIVATE_KEY` — путь к приватному ключу OAuth2
  - `OAUTH_PUBLIC_KEY` — путь к публичному ключу OAuth2
  - `OAUTH_ENCRYPTION_KEY` — ключ шифрования (строка, сгенерируйте: `php -r 'echo base64_encode(random_bytes(32));'`)
  - `OAUTH_PASSPHRASE` — passphrase приватного ключа

Пути могут быть абсолютными или относительными к корню проекта.

## Генерация ключей
### JWT (LexikJWTAuthenticationBundle)
```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm RSA -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
# Установите JWT_PASSPHRASE в .env.local
```

### OAuth2 (league/oauth2-server-bundle)
```bash
mkdir -p config/oauth
openssl genpkey -out config/oauth/private.key -aes256 -algorithm RSA -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/oauth/private.key -out config/oauth/public.key -pubout
# Установите OAUTH_PASSPHRASE, OAUTH_ENCRYPTION_KEY и пути к ключам в .env.local
```

## Тестовые учётные записи
- ADMIN: логин `admin`, пароль `adminpassword`
- USER: логин `user`, пароль `userpassword`

Создаются фикстурами: `App\DataFixtures\UserFixtures`.

## Роли и доступы
Ключевые правила (`config/packages/security.yaml`):
- `/admin/**` — доступ только `ROLE_ADMIN` (страницы админ-панели)
- `/user/auth/**` — публичные страницы логина пользователя
- `/authorize`, `/oauth2/**` — OAuth2-флоу, доступ для аутентифицированных
- `/api/auth/**`, `/api/doc/**` — публичные эндпоинты аутентификации и документации
- `/api/**` — требуются валидные токены (JWT) и/или права `ROLE_USER`

## Маршруты UI
- Админ-панель: `/admin/auth/login`, `/admin/users`, `/admin/reports`
- Пользовательский логин: `/user/auth/login`
- Окно согласия OAuth2: `/oauth2/consent`

## REST API
Swagger UI: `/api/doc` (NelmioApiDocBundle).

### Аутентификация
- `POST /api/auth/login` — вход пользователя.
  - Тело: `{ "username": "string", "password": "string" }`
  - Ответ: `{ "status": "success", "data": { "token": "<JWT>", "user": { ... } } }`
- `GET /api/auth/me` — данные текущего пользователя.
- `POST /api/auth/logout` — выход; ревокация OAuth2 refresh/access токенов пользователя.

### Пользователи
- `GET /api/users?page=1&limit=20` — список пользователей с пагинацией.

### Формат ответов/ошибок
Единый формат через `App\Dto\ApiResponseDto`:
```json
{
  "status": "success" | "error",
  "code": 200,
  "message": "...",
  "data": { ... },
  "errors": [ { "field": "...", "message": "..." } ],
  "timestamp": "2025-09-12T12:00:00+00:00"
}
```

## OAuth2
- Бандл: `league/oauth2-server-bundle`
- Конфиг: `config/packages/league_oauth2_server.yaml`
- Scope: `user:read`, `user:write`
- Флоу: клиент инициирует `/authorize`, пользователь логинится и видит `/oauth2/consent`, после согласия выдаются код/токены согласно конфигурации.

## Сессии
- Сущность `Session` фиксирует `startedAt`/`finishedAt`.
- При старте новой сессии все незавершённые сессии пользователя помечаются завершёнными (транзакция в `SessionRepository::create`).

## Отчёты
- UI: `/admin/reports`
- Реализация: `ReportService` + `ReportRepository` (DQL). Доступные типы: активность пользователей, регистрации пользователей (с фильтрами по датам).
- SQL-скрипт для отчётов (view/procedure): может быть добавлен отдельно при необходимости.

## Ограничения и планы
- Базовые автотесты отсутствуют и планируются к добавлению.

## Полезные команды
```bash
# Миграции
php bin/console doctrine:migrations:migrate --no-interaction

# Фикстуры
php bin/console doctrine:fixtures:load --no-interaction

# Очистка кеша
php bin/console cache:clear
```


