# Мини-сервис авторизации 

> Авторизационный шлюз на Symfony 7 + PostgreSQL, реализующий управление пользователями, сессиями, токенами, отчётность и интеграцию через REST API и OAuth2.

---
# Архитектура
Проект включает в себя использование DTO, Repository, Service, что позволяет оставлять контроллеры тонкими.

## Запуск проекта

### Предварительные требования

- Установленные Docker, Docker-compose
- PHP 8.2+
- Git

### Шаги запуска

```bash
# 1. Клонировать репозиторий
git clone https://github.com/Artem-Zaxler/auth-service.git
cd auth-service

# 2. Запустить контейнер с базой данных
docker-compose up -d

# 3. Установить зависимости Composer
composer install

# 4. Применить миграции базы данных
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Загрузить фикстуры (создать тестовых пользователей с ролями ROLE_ADMIN и ROLE_USER, создать oauth2 клиент)
php bin/console doctrine:fixtures:load --no-interaction

# 6. (Опционально) Генерация OAuth2-ключей
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm RSA -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

Указанную passphrase вписать в переменную в JWT_PASSPHRASE в файле .env