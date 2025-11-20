# Доступные PDF шаблоны

## 1. Счет на оплату (invoice.pdf)

**Поля в шаблоне:**
- `company` - Название компании
- `name` - Контактное лицо
- `email` - Email адрес
- `date` - Дата
- `description` - Описание услуги/товара
- `amount` - Сумма к оплате

**Пример CSV:**
```csv
name,email,date,amount,description,company
Иван Иванов,ivan@example.com,2024-01-15,15000,Оплата за услуги консультации,ООО "ТехноСервис"
```

---

## 2. Договор на оказание услуг (contract.pdf)

**Поля в шаблоне:**
- `contract_date` - Дата заключения договора
- `contract_number` - Номер договора
- `executor_company` - Название компании исполнителя
- `executor_name` - Контактное лицо исполнителя
- `executor_phone` - Телефон исполнителя
- `client_company` - Название компании заказчика
- `client_name` - Контактное лицо заказчика
- `client_email` - Email заказчика
- `service_description` - Описание услуги
- `service_price` - Стоимость услуги

**Пример CSV:**
```csv
contract_date,contract_number,executor_company,executor_name,executor_phone,client_company,client_name,client_email,service_description,service_price
2024-01-15,ДОГ-001,ООО "Исполнитель",Иванов Иван,8-800-123-45-67,ООО "Заказчик",Петров Петр,client@example.com,Разработка и поддержка веб-сайта,50000
```

---

## 3. Акт выполненных работ (act.pdf)

**Поля в шаблоне:**
- `act_date` - Дата акта
- `act_number` - Номер акта
- `performer_company` - Название компании исполнителя
- `performer_contact` - Контактные данные исполнителя
- `customer_company` - Название компании заказчика
- `customer_contact` - Контактные данные заказчика
- `work_description` - Описание выполненных работ
- `total_amount` - Общая сумма к оплате

**Пример CSV:**
```csv
act_date,act_number,performer_company,performer_contact,customer_company,customer_contact,work_description,total_amount
2024-01-20,АКТ-001,ООО "Исполнитель",Иванов И.И., тел. 8-800-123-45-67,ООО "Заказчик",Петров П.П., тел. 8-800-765-43-21,Выполнены работы по разработке веб-сайта,50000
```

---

## Добавление новых шаблонов

Чтобы добавить новый шаблон:
1. Создайте PDF файл с переменными в формате `{{variable_name}}`
2. Сохраните файл в директорию `storage/app/templates/`
3. Обновите метод `getTemplateName()` в `PdfGeneratorController.php` для добавления человекочитаемого имени


