<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Jobs\CreateReportJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    // Запуск генерации
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'status' => 'nullable|string',
        ]);

        // Создаем запись в вашей текущей таблице отчетов
        $report = Report::create([
            'status' => 'pending',
            'type' => 'export'
        ]);

        // Передаем модель и фильтры в Job
        CreateReportJob::dispatch($report, $request->all());

        return response()->json($report); // Возвращаем созданный репорт с ID
    }

    // Проверка статуса (Polling фронтендом по ID записи)
    public function checkStatus(int $id): JsonResponse
    {
        $report = Report::findOrFail($id);

        return response()->json([
            'status' => $report->status,
            'type' => 'export'
        ]);
    }

    // Скачивание готового файла после завершения
    public function download(int $id): BinaryFileResponse
    {
        $report = Report::findOrFail($id);
        $filePath = $report->parsed_data['file_path'] ?? null;

        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            abort(404, 'Файл отчета не найден.');
        }

        // Получаем полный абсолютный путь к файлу на сервере
        // (он автоматически вернет путь с учетом папки /private/)
        $absolutePath = Storage::disk('local')->path($filePath);

        // Отдаем файл как BinaryFileResponse и безопасно удаляем его после отправки
        return response()->download($absolutePath)->deleteFileAfterSend(true);
    }
}
