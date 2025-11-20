<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use TCPDF;
use setasign\Fpdi\Tcpdf\Fpdi as TcpdfFpdi;

class PdfService
{
    /**
     * Извлекает поля из PDF шаблона
     * Ищет текстовые поля и аннотации формы
     */
    public function extractFields(string $pdfPath): array
    {
        $fullPath = Storage::path($pdfPath);

        if (!file_exists($fullPath)) {
            throw new \Exception('PDF файл не найден');
        }

        $fields = [];
        try {
            // Читаем содержимое PDF файла как текст для поиска переменных
            $pdfContent = file_get_contents($fullPath);

            // Ищем переменные в различных форматах прямо в содержимом PDF
            $patterns = [
                '/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/',  // {{variable}}
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',      // {variable}
                '/\[([a-zA-Z_][a-zA-Z0-9_]*)\]/',      // [variable]
                '/\$([a-zA-Z_][a-zA-Z0-9_]*)/',        // $variable
            ];
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $pdfContent, $matches)) {
                    foreach ($matches[1] as $field) {
                        if (!in_array($field, $fields)) {
                            $fields[] = $field;
                        }
                    }
                }
            }

            // Также ищем в декодированном виде (для некоторых форматов PDF)
            // Пытаемся найти переменные в текстовых потоках
            if (preg_match_all('/\(([^)]*\{\{[^}]*\}\}[^)]*)\)/', $pdfContent, $textMatches)) {
                foreach ($textMatches[1] as $text) {
                    foreach ($patterns as $pattern) {
                        if (preg_match_all($pattern, $text, $matches)) {
                            foreach ($matches[1] as $field) {
                                if (!in_array($field, $fields)) {
                                    $fields[] = $field;
                                }
                            }
                        }
                    }
                }
            }

            // Если не найдено в бинарном содержимом, пытаемся извлечь через TCPDF
            if (empty($fields)) {
                $pdf = new TcpdfFpdi();
                $pageCount = $pdf->setSourceFile($fullPath);

                $text = '';
                for ($i = 1; $i <= $pageCount; $i++) {
                    try {
                        $tpl = $pdf->importPage($i);
                        $size = $pdf->getTemplateSize($tpl);

                        // Создаем временный PDF для извлечения текста
                        $tempPdf = new TcpdfFpdi();
                        $tempPdf->AddPage($size['orientation'] ?? 'P', [$size['width'], $size['height']]);
                        $tempPdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                        // Пытаемся извлечь текст
                        $pageText = $tempPdf->getText();
                        if ($pageText) {
                            $text .= $pageText;
                        }
                    } catch (\Exception $e) {
                        // Пропускаем страницу, если не удалось обработать
                        continue;
                    }
                }

                // Ищем переменные в извлеченном тексте
                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $text, $matches)) {
                        foreach ($matches[1] as $field) {
                            if (!in_array($field, $fields)) {
                                $fields[] = $field;
                            }
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            // Если не удалось извлечь, возвращаем пустой массив
            // Пользователь сможет ввести поля вручную
            $fields = [];
        }

        return array_unique($fields);
    }

    /**
     * Генерирует PDF из шаблона с данными из CSV
     */
    public function generate(string $templatePath, array $csvData, array $fieldMapping): string
    {
        $templateFullPath = Storage::path($templatePath);

        if (!file_exists($templateFullPath)) {
            throw new \Exception('Шаблон PDF не найден');
        }

        // Создаем директорию для сгенерированных файлов
        $outputDir = storage_path('app/generated');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = 'generated_' . date('Y-m-d_H-i-s') . '.pdf';
        $outputPath = $outputDir . '/' . $filename;

        try {
            // Создаем новый PDF документ с поддержкой FPDI и UTF-8
            // TcpdfFpdi наследуется от TCPDF, поэтому используем те же параметры
            $pdf = new TcpdfFpdi(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Устанавливаем метаданные
            $pdf->SetCreator('CSV to PDF Generator');
            $pdf->SetAuthor('PDF Generator');
            $pdf->SetTitle('Generated PDF from CSV');

            // Убираем заголовок и футер по умолчанию
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Устанавливаем шрифт с поддержкой кириллицы (dejavusans поддерживает UTF-8)
            $pdf->SetFont('dejavusans', '', 10);

            // Используем FPDI для импорта страниц шаблона
            $pageCount = $pdf->setSourceFile($templateFullPath);

            // Обрабатываем каждую запись из CSV
            foreach ($csvData as $rowIndex => $row) {
                // Для каждой страницы шаблона
                for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                    $tpl = $pdf->importPage($pageNum);
                    $size = $pdf->getTemplateSize($tpl);

                    // Определяем ориентацию страницы
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

                    // Добавляем страницу в итоговый PDF
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);

                    // Используем шаблон
                    $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                    // Заменяем переменные в тексте
                    // Для этого нужно извлечь текст и заменить значения
                    $this->fillPdfFields($pdf, $row, $fieldMapping, $size);
                }

                // Добавляем разрыв страницы между записями (кроме последней)
                if ($rowIndex < count($csvData) - 1) {
                    $pdf->AddPage();
                }
            }

            // Сохраняем PDF
            $pdf->Output($outputPath, 'F');

            return $filename;

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при генерации PDF: ' . $e->getMessage());
        }
    }

    /**
     * Заполняет поля в PDF данными из CSV
     * Заменяет переменные в тексте шаблона
     */
    protected function fillPdfFields(TCPDF $pdf, array $row, array $fieldMapping, array $size): void
    {
        // Создаем массив замен для всех переменных
        $replacements = [];
        foreach ($fieldMapping as $pdfField => $csvField) {
            if (isset($row[$csvField])) {
                $value = $row[$csvField];
                // Поддерживаем различные форматы переменных
                $replacements['{{' . $pdfField . '}}'] = $value;
                $replacements['{' . $pdfField . '}'] = $value;
                $replacements['[' . $pdfField . ']'] = $value;
                $replacements['$' . $pdfField] = $value;
            }
        }

        // Добавляем данные в виде текста поверх шаблона
        // Используем шрифт с поддержкой кириллицы
        $yPosition = 30;
        $lineHeight = 8;
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($fieldMapping as $pdfField => $csvField) {
            if (isset($row[$csvField])) {
                $value = $row[$csvField];
                // Ограничиваем длину текста для отображения
                $displayValue = mb_substr($value, 0, 80, 'UTF-8');
                if (mb_strlen($value, 'UTF-8') > 80) {
                    $displayValue .= '...';
                }

                $pdf->SetXY(20, $yPosition);
                // Используем MultiCell для правильного отображения кириллицы
                $pdf->MultiCell(0, $lineHeight, $pdfField . ': ' . $displayValue, 0, 1, '', false);
                $yPosition = $pdf->GetY() + 2;

                // Если достигли конца страницы, начинаем с начала
                if ($yPosition > ($size['height'] - 30)) {
                    $yPosition = 30;
                }
            }
        }
    }
}

