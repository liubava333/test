<?php
namespace App\Http\Controllers;

use App\Jobs\ProcessReportJob;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Jobs\CreateReportJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportParseController extends Controller
{

    public function checkParseStatus($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        // 2. КРИТИЧЕСКИЙ ШАГ: Принудительно сбрасываем кэш этой модели
        // и перечитываем данные прямо из диска базы данных
        $report->refresh();
        // 3. Возвращаем чистый JSON-ответ
        return response()->json([
            'id' => $report->id,
            'status' => $report->status ?? 'processing',
            'progress' => (int)($report->progress ?? 0),
            'parsed_data' => $report->parsed_data
        ])
            // 4. Намертво запрещаем кэширование этого ответа на пути от Laravel до Axios
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function parseData(Request $request) {
        // Валидируем, что файл действительно загружен
        $request->validate([
            'uploaded_file' => 'required|file|mimes:csv,json,xlsx,xls|max:10240', // макс 10мб
        ]);

        if ($request->hasFile('uploaded_file')) {
            $file = $request->file('uploaded_file');

            // Сохраняем файл локально в storage. Laravel сгенерирует уникальное имя
            $filePath = $file->store('uploads');
            // Создаем запись отчета
            $report = Report::create([
                'status' => 'processing',
                'progress' => 0
            ]);

            // Создаем объект через конструктор и задаем соединение напрямую
            $job = (new ProcessReportJob($report, $filePath))->onConnection('database');

            // Отправляем в очередь через глобальный хелпер dispatch()
            dispatch($job);

            return response()->json(['report_id' => $report->id], 202);
        }

        return response()->json(['error' => 'Файл не загружен'], 400);
    }
}
