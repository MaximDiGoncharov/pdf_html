<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CsvParserService;
use App\Services\PdfService;
use Illuminate\Support\Facades\Session;

class PdfGeneratorController_bc extends Controller
{
    protected $csvParser;
    protected $pdfService;

    public function __construct(CsvParserService $csvParser, PdfService $pdfService)
    {
        $this->csvParser = $csvParser;
        $this->pdfService = $pdfService;
    }

    public function index()
    {
        // Получаем список доступных шаблонов
        $templates = $this->getAvailableTemplates();
        return view('index', compact('templates'));
    }

    /**
     * Получает список доступных шаблонов
     */
    public function getAvailableTemplates(): array
    {
        $templatesDir = storage_path('app/templates');
        $templates = [];

        if (is_dir($templatesDir)) {
            $files = glob($templatesDir . '/*.pdf');
            foreach ($files as $file) {
                $filename = basename($file);
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $templates[] = [
                    'filename' => $filename,
                    'name' => $this->getTemplateName($name),
                    'path' => 'templates/' . $filename
                ];
            }
        }

        return $templates;
    }

    /**
     * Получает человекочитаемое имя шаблона
     */
    protected function getTemplateName(string $filename): string
    {
        $names = [
            'invoice' => 'Счет на оплату',
            'contract' => 'Договор на оказание услуг',
            'act' => 'Акт выполненных работ',
            'document'=> 'Платежка Череповец'
        ];

        return $names[$filename] ?? ucfirst($filename);
    }

    /**
     * Получает поля из выбранного шаблона
     */
    public function getTemplateFields(Request $request)
    {
        $request->validate([
            'template' => 'required|string',
        ]);

        try {
            $templatePath = 'templates/' . $request->template;
            $fullPath = Storage::path($templatePath);

            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Шаблон не найден'
                ], 404);
            }

            $pdfFields = $this->pdfService->extractFields($templatePath);

            return response()->json([
                'success' => true,
                'fields' => $pdfFields
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при извлечении полей: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'template' => 'required|string',
        ]);

        try {
            // Сохраняем загруженный CSV
            $csvPath = $request->file('csv_file')->store('temp');

            // Определяем путь к шаблону
            $templatePath = 'templates/' . $request->template;
            $fullTemplatePath = Storage::path($templatePath);

            if (!file_exists($fullTemplatePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Выбранный шаблон не найден'
                ], 404);
            }

            // Парсим CSV
            $csvData = $this->csvParser->parse($csvPath);

            // Извлекаем поля из PDF шаблона
            $pdfFields = $this->pdfService->extractFields($templatePath);

            // Сохраняем в сессию
            Session::put('csv_data', $csvData);
            Session::put('csv_path', $csvPath);
            Session::put('pdf_path', $templatePath);
            Session::put('pdf_fields', $pdfFields);
            Session::put('csv_headers', array_keys($csvData[0] ?? []));
            Session::put('selected_template', $request->template);

            return response()->json([
                'success' => true,
                'csv_headers' => Session::get('csv_headers'),
                'pdf_fields' => $pdfFields,
                'csv_rows_count' => count($csvData)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке файлов: ' . $e->getMessage()
            ], 500);
        }
    }

    public function mapFields(Request $request)
    {
        $request->validate([
            'field_mapping' => 'required|array',
        ]);

        Session::put('field_mapping', $request->field_mapping);

        return response()->json([
            'success' => true,
            'message' => 'Сопоставление полей сохранено'
        ]);
    }

    public function generatePdf(Request $request)
    {
        $csvData = Session::get('csv_data');
        $pdfPath = Session::get('pdf_path');
        $fieldMapping = Session::get('field_mapping');

        if (!$csvData || !$pdfPath || !$fieldMapping) {
            return response()->json([
                'success' => false,
                'message' => 'Отсутствуют необходимые данные. Пожалуйста, загрузите файлы заново.'
            ], 400);
        }

        try {
//            dd($fieldMapping);
            $filename = $this->pdfService->generate($pdfPath, $csvData, $fieldMapping);

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'PDF файл успешно сгенерирован'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при генерации PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadPdf($filename)
    {
        $path = storage_path('app/generated/' . $filename);

        if (!file_exists($path)) {
            abort(404, 'Файл не найден');
        }

        return response()->download($path);
    }
}

