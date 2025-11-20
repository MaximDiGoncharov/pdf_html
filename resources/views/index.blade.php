<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CSV to PDF Generator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }

        .step {
            margin-bottom: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            border-left: 5px solid #667eea;
        }

        .step-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step-number {
            background: #667eea;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #667eea;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        input[type="file"]:hover {
            border-color: #764ba2;
            background: #f8f9fa;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .mapping-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .mapping-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .mapping-item select {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .arrow {
            color: #667eea;
            font-size: 1.5em;
        }

        .status {
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            display: none;
        }

        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status.show {
            display: block;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 CSV to PDF Generator</h1>
        <p class="subtitle">Загрузите CSV файл и PDF шаблон для генерации объединенного PDF</p>

        <!-- Шаг 1: Загрузка файлов -->
        <div class="step" id="step1">
            <div class="step-title">
                <span class="step-number">1</span>
                <span>Загрузка файлов</span>
            </div>
            <form id="uploadForm">
                <div class="form-group">
                    <label for="template_select">Выберите PDF шаблон</label>
                    <select id="template_select" name="template" required style="width: 100%; padding: 12px; border: 2px solid #667eea; border-radius: 10px; background: white; font-size: 1em; cursor: pointer;">
                        <option value="">-- Выберите шаблон --</option>
                        @foreach($templates as $template)
                            <option value="{{ $template['filename'] }}">{{ $template['name'] }}</option>
                        @endforeach
                    </select>
                    <div id="templateFieldsPreview" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; display: none;">
                        <strong>Поля в шаблоне:</strong>
                        <div id="templateFieldsList" style="margin-top: 5px; color: #666;"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="csv_file">CSV файл с данными</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required>
                </div>
                <button type="submit" class="btn">Загрузить CSV и выбрать шаблон</button>
            </form>
            <div class="status" id="uploadStatus"></div>
            <div class="loading" id="uploadLoading">
                <div class="spinner"></div>
                <p>Обработка файлов...</p>
            </div>
        </div>

        <!-- Шаг 2: Сопоставление полей -->
        <div class="step hidden" id="step2">
            <div class="step-title">
                <span class="step-number">2</span>
                <span>Сопоставление полей</span>
            </div>
            <div id="manualFieldsContainer" style="margin-bottom: 20px; display: none;">
                <label style="display: block; margin-bottom: 10px; font-weight: 500;">Если поля не были найдены автоматически, введите их вручную (через запятую):</label>
                <input type="text" id="manualFieldsInput" placeholder="name, email, date, amount, description, company" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px;">
                <button type="button" class="btn" id="addManualFieldsBtn" style="background: #28a745; margin-bottom: 20px;">Добавить поля</button>
            </div>
            <div id="mappingContainer"></div>
            <button type="button" class="btn" id="saveMappingBtn" style="margin-top: 20px;">Сохранить сопоставление</button>
            <div class="status" id="mappingStatus"></div>
        </div>

        <!-- Шаг 3: Генерация PDF -->
        <div class="step hidden" id="step3">
            <div class="step-title">
                <span class="step-number">3</span>
                <span>Генерация PDF</span>
            </div>
            <button type="button" class="btn" id="generateBtn" disabled>Сгенерировать PDF</button>
            <div class="status" id="generateStatus"></div>
            <div class="loading" id="generateLoading">
                <div class="spinner"></div>
                <p>Генерация PDF файла...</p>
            </div>
        </div>
    </div>

    <script>
        let csvHeaders = [];
        let pdfFields = [];

        // Загрузка полей шаблона при выборе
        document.getElementById('template_select').addEventListener('change', async function() {
            const template = this.value;
            const preview = document.getElementById('templateFieldsPreview');
            const fieldsList = document.getElementById('templateFieldsList');
            
            if (!template) {
                preview.style.display = 'none';
                return;
            }
            
            try {
                const response = await fetch('/get-template-fields', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ template: template })
                });
                
                const data = await response.json();
                
                if (data.success && data.fields.length > 0) {
                    fieldsList.textContent = data.fields.join(', ');
                    preview.style.display = 'block';
                } else {
                    fieldsList.textContent = 'Поля не найдены автоматически';
                    preview.style.display = 'block';
                }
            } catch (error) {
                fieldsList.textContent = 'Ошибка при загрузке полей';
                preview.style.display = 'block';
            }
        });

        // Загрузка файлов
        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('csv_file', document.getElementById('csv_file').files[0]);
            formData.append('template', document.getElementById('template_select').value);
            
            const loading = document.getElementById('uploadLoading');
            const status = document.getElementById('uploadStatus');
            
            loading.classList.add('show');
            status.classList.remove('show');
            
            try {
                const response = await fetch('/upload-csv', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    csvHeaders = data.csv_headers || [];
                    pdfFields = data.pdf_fields || [];
                    
                    status.textContent = `Успешно! Найдено ${data.csv_rows_count} записей в CSV и ${pdfFields.length} полей в PDF`;
                    status.className = 'status success show';
                    
                    // Показываем шаг 2 и 3
                    document.getElementById('step2').classList.remove('hidden');
                    document.getElementById('step3').classList.remove('hidden');
                    
                    // Если поля не найдены, показываем возможность ручного ввода
                    if (pdfFields.length === 0) {
                        document.getElementById('manualFieldsContainer').style.display = 'block';
                        status.textContent += '. Поля не найдены автоматически - введите их вручную.';
                        status.className = 'status error show';
                    }
                    
                    buildMappingInterface();
                } else {
                    status.textContent = data.message || 'Ошибка при загрузке файлов';
                    status.className = 'status error show';
                }
            } catch (error) {
                status.textContent = 'Ошибка: ' + error.message;
                status.className = 'status error show';
            } finally {
                loading.classList.remove('show');
            }
        });

        // Построение интерфейса сопоставления
        function buildMappingInterface() {
            const container = document.getElementById('mappingContainer');
            container.innerHTML = '';
            
            if (pdfFields.length === 0) {
                container.innerHTML = '<p style="color: #666; padding: 20px; text-align: center;">Поля не найдены. Введите их вручную выше.</p>';
                return;
            }
            
            pdfFields.forEach(pdfField => {
                const mappingItem = document.createElement('div');
                mappingItem.className = 'mapping-item';
                
                const label = document.createElement('label');
                label.textContent = pdfField;
                label.style.minWidth = '150px';
                
                const select = document.createElement('select');
                select.name = `mapping[${pdfField}]`;
                select.id = `mapping_${pdfField}`;
                
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = '-- Выберите поле --';
                select.appendChild(emptyOption);
                
                csvHeaders.forEach(header => {
                    const option = document.createElement('option');
                    option.value = header;
                    option.textContent = header;
                    select.appendChild(option);
                });
                
                mappingItem.appendChild(label);
                mappingItem.appendChild(document.createTextNode(' → '));
                mappingItem.appendChild(select);
                
                container.appendChild(mappingItem);
            });
        }

        // Добавление полей вручную
        document.getElementById('addManualFieldsBtn').addEventListener('click', () => {
            const input = document.getElementById('manualFieldsInput');
            const fieldsText = input.value.trim();
            
            if (!fieldsText) {
                alert('Пожалуйста, введите поля');
                return;
            }
            
            // Разбиваем по запятой и очищаем от пробелов
            const newFields = fieldsText.split(',').map(f => f.trim()).filter(f => f.length > 0);
            
            // Добавляем новые поля к существующим
            newFields.forEach(field => {
                if (!pdfFields.includes(field)) {
                    pdfFields.push(field);
                }
            });
            
            // Скрываем контейнер ручного ввода
            document.getElementById('manualFieldsContainer').style.display = 'none';
            
            // Перестраиваем интерфейс
            buildMappingInterface();
            
            // Обновляем статус
            document.getElementById('uploadStatus').textContent = `Успешно! Найдено ${csvHeaders.length} заголовков в CSV и ${pdfFields.length} полей в PDF`;
            document.getElementById('uploadStatus').className = 'status success show';
        });

        // Сохранение сопоставления
        document.getElementById('saveMappingBtn').addEventListener('click', async () => {
            const mapping = {};
            pdfFields.forEach(pdfField => {
                const select = document.getElementById(`mapping_${pdfField}`);
                if (select.value) {
                    mapping[pdfField] = select.value;
                }
            });
            
            if (Object.keys(mapping).length === 0) {
                document.getElementById('mappingStatus').textContent = 'Пожалуйста, выберите хотя бы одно поле для сопоставления';
                document.getElementById('mappingStatus').className = 'status error show';
                return;
            }
            
            try {
                const response = await fetch('/map-fields', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ field_mapping: mapping })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('mappingStatus').textContent = 'Сопоставление сохранено! Теперь можно генерировать PDF';
                    document.getElementById('mappingStatus').className = 'status success show';
                    document.getElementById('generateBtn').disabled = false;
                } else {
                    document.getElementById('mappingStatus').textContent = data.message || 'Ошибка при сохранении';
                    document.getElementById('mappingStatus').className = 'status error show';
                }
            } catch (error) {
                document.getElementById('mappingStatus').textContent = 'Ошибка: ' + error.message;
                document.getElementById('mappingStatus').className = 'status error show';
            }
        });

        // Генерация PDF
        document.getElementById('generateBtn').addEventListener('click', async () => {
            const loading = document.getElementById('generateLoading');
            const status = document.getElementById('generateStatus');
            
            loading.classList.add('show');
            status.classList.remove('show');
            
            try {
                const response = await fetch('/generate-pdf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    status.innerHTML = `PDF успешно сгенерирован! <a href="/download-pdf/${data.filename}" style="color: #155724; text-decoration: underline;">Скачать файл</a>`;
                    status.className = 'status success show';
                } else {
                    status.textContent = data.message || 'Ошибка при генерации PDF';
                    status.className = 'status error show';
                }
            } catch (error) {
                status.textContent = 'Ошибка: ' + error.message;
                status.className = 'status error show';
            } finally {
                loading.classList.remove('show');
            }
        });
    </script>
</body>
</html>

