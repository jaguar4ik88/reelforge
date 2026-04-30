# ReelForge

Веб-приложение для создания видео из фотографий с возможностью выбора шаблонов и добавления текста.

## 🎯 Описание

ReelForge позволяет пользователям:
- Регистрироваться и входить в систему
- Создавать проекты из 3-5 фотографий
- Добавлять заголовок, цену и описание
- Выбирать шаблон для видео
- Генерировать MP4 видео в формате 9:16
- Скачивать готовые видео

## 🏗️ Архитектура

**API-first подход** с подготовкой к мобильной версии:
- **Backend**: Laravel 11 + MySQL + Redis + FFmpeg
- **Frontend**: React + Vite + Tailwind CSS
- **Инфраструктура**: Docker + Nginx

## 🚀 Быстрый старт

### Локально без Docker (разработка)

**Требования:** PHP 8.2+, Composer, Node.js 18+, доступная MySQL (локально или удалённый хост в `backend/.env`), по желанию Redis для очередей.

```bash
# 1. Backend
cd backend
composer install
cp .env.example .env   # если ещё нет .env — настройте DB_*, REDIS_*
php artisan key:generate
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000

# 2. Frontend (второй терминал)
cd frontend
npm install
VITE_API_URL=http://127.0.0.1:8000 npm run dev
```

- Сайт: http://localhost:5173  
- API: http://127.0.0.1:8000  

Для генерации видео в фоне в третьем терминале: `cd backend && php artisan queue:work` (нужен работающий Redis из `.env`).

### Запуск через Docker
```bash
# Запуск всех сервисов
docker-compose up -d

# Проверка статуса
docker-compose ps
```

### Доступные URL
- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

### Кабинет (после входа)

| Маршрут | Описание |
|---------|----------|
| `/dashboard` | Главная, статистика и быстрые ссылки |
| `/templates` | Шаблоны-примеры, сетка и фильтры по категориям |
| `/products` | Продукция — список ваших проектов |
| `/gallery` | Всё созданное, фильтр по продукции и типу |
| `/projects/new` | Создание проекта |

### Тестовые пользователи
- **Тест**: `test@example.com` / `password`
- **Админ**: `admin@example.com` / `admin123`

## 📋 Функциональность MVP

- ✅ Регистрация и авторизация (Sanctum)
- ✅ Dashboard для управления проектами
- ✅ Создание проектов с загрузкой изображений
- ✅ Выбор шаблонов видео
- ✅ Генерация MP4 через FFmpeg
- ✅ Система очередей (Redis)
- ✅ Ограничения: 10 видео в месяц

## 🛠️ Технологии

### Backend
- Laravel 11 + PHP 8.2+
- MySQL 8.0
- Redis (Queue + Cache)
- Sanctum (Authentication)
- FFmpeg (Video processing)

### Frontend
- React 18 + Vite
- Tailwind CSS
- Axios (HTTP client)
- React Router

## 📁 Структура проекта

```
ReelForge/
├── backend/           # Laravel API
├── frontend/          # React приложение
├── docker/           # Docker конфигурации
├── docs/             # Документация (в .gitignore)
├── docker-compose.yml
└── README.md
```

## 🔧 Разработка

### Backend команды
```bash
# Вход в контейнер Laravel
docker-compose exec app bash

# Миграции
php artisan migrate

# Сиды
php artisan db:seed

# Очереди
php artisan queue:work
```

### Frontend команды
```bash
# Вход в контейнер React
docker-compose exec frontend bash

# Установка зависимостей
npm install

# Запуск dev сервера
npm run dev
```

## 🤖 AI Агенты

Проект использует специализированных AI агентов с правилами кодирования:
- **Frontend Agent**: React/JavaScript стандарты
- **Backend Agent**: Laravel/PHP стандарты  
- **General Agent**: Общие правила проекта

Правила находятся в `.cursor/rules/`

## 📚 Документация

Техническая документация находится в папке `docs/` (не попадает в git):
- План реализации
- Руководства по тестированию
- API документация
- Отчеты об изменениях

## 🔐 Безопасность

- API защищен через Sanctum Bearer токены
- Валидация данных на уровне Request классов
- Policy классы для авторизации
- Безопасная загрузка файлов

## 📌 Что ещё по плану (не сделано)

В `docs/IMPLEMENTATION_PLAN.md` часть чекбоксов устарела: база, API, проекты и генерация уже есть в коде. **Крупный невыполненный блок:**

- **Система кредитов** — баланс, транзакции, списание за генерацию (см. `docs/CREDITS_SYSTEM_PLAN.md`)
- **Покупка пакетов кредитов и подписки** (Stripe)
- **Связка тарифов на странице Pricing с реальным биллингом**

Мелкие доработки из плана: полное покрытие тестами, продакшен S3, админка кредитов.

## 📈 Планы развития

### Фаза 2
- Интеграция с S3 для файлов
- Stripe для подписок
- Больше шаблонов видео
- Email уведомления

### Фаза 3
- React Native / Flutter приложения
- PWA версия
- Расширенная кастомизация

## 🚀 Автодеплой (CI/CD)

Деплой при merge в **`main`**: GitHub Actions + SSH и скрипт **`scripts/deploy.sh`**. Подробно: [docs/DEPLOYMENT_AUTOMATION.md](docs/DEPLOYMENT_AUTOMATION.md).

## 📄 Лицензия

Проект разработан для внутреннего использования.

---

**ReelForge** - создавай видео из фотографий легко и быстро! 🎬