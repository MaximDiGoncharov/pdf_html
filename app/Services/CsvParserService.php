<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CsvParserService
{
    public function parse(string $csvPath): array
    {
        $fullPath = Storage::path($csvPath);
        
        if (!file_exists($fullPath)) {
            throw new \Exception('CSV файл не найден');
        }

        $data = [];
        $handle = fopen($fullPath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Не удалось открыть CSV файл');
        }

        // Определяем разделитель и кодировку
        $firstLine = fgets($handle);
        rewind($handle);
        
        $delimiter = $this->detectDelimiter($firstLine);
        
        // Читаем заголовки
        $headers = fgetcsv($handle, 0, $delimiter);
        
        if ($headers === false) {
            fclose($handle);
            throw new \Exception('Не удалось прочитать заголовки CSV');
        }

        // Очищаем заголовки от BOM и пробелов
        $headers = array_map(function($header) {
            return trim($header, "\xEF\xBB\xBF \t\n\r\0\x0B");
        }, $headers);

        // Читаем данные
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }

        fclose($handle);

        if (empty($data)) {
            throw new \Exception('CSV файл пуст или не содержит данных');
        }

        return $data;
    }

    protected function detectDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $delimiterCounts = [];

        foreach ($delimiters as $delimiter) {
            $delimiterCounts[$delimiter] = substr_count($line, $delimiter);
        }

        return array_search(max($delimiterCounts), $delimiterCounts) ?: ',';
    }
}


