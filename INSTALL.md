# Инструкция по установке

## Быстрый старт

### 1. Установка зависимостей

```bash
composer install
```

### 2. Настройка окружения

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Настройка прав доступа

```bash
chmod -R 775 storage bootstrap/cache
```

### 4. Запуск сервера

```bash
php artisan serve
```

Приложение будет доступно по адресу: `http://localhost:8000`

## Структура проекта

```
pdf_html/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── PdfGeneratorController.php  # Основной контроллер
│   │   └── Middleware/                      # Middleware для Laravel
│   └── Services/
│       ├── CsvParserService.php             # Парсинг CSV файлов
│       └── PdfService.php                   # Работа с PDF
├── config/                                  # Конфигурационные файлы
├── public/                                  # Публичная директория
├── resources/
│   └── views/
│       └── index.blade.php                  # Главная страница
├── routes/
│   └── web.php                              # Маршруты приложения
└── storage/
    └── app/
        ├── temp/                            # Временные файлы
        └── generated/                      # Сгенерированные PDF
```

## Использование

1. Откройте главную страницу в браузере
2. Загрузите CSV файл (первая строка должна содержать заголовки)
3. Загрузите PDF шаблон
4. Сопоставьте поля из CSV с переменными в PDF
5. Нажмите "Сгенерировать PDF"
6. Скачайте готовый файл

## Формат CSV

CSV файл должен иметь заголовки в первой строке:

```csv
name,email,date,amount
Иван Иванов,ivan@example.com,2024-01-15,1000
Петр Петров,petr@example.com,2024-01-16,2000
```

## Формат PDF шаблона

PDF шаблон может содержать переменные в следующих форматах:
- `{{variable}}`
- `{variable}`
- `[variable]`
- `$variable`

## Требования к серверу

- PHP >= 8.0
- Расширения: mbstring, xml, gd, zip
- Composer

## Решение проблем

### Ошибка "Class 'TCPDF' not found"
Убедитесь, что установлены все зависимости:
```bash
composer install
```

### Ошибка прав доступа
Установите правильные права:
```bash
chmod -R 775 storage bootstrap/cache
```

### Ошибка при загрузке файлов
Проверьте настройки `php.ini`:
- `upload_max_filesize`
- `post_max_size`
- `memory_limit`


