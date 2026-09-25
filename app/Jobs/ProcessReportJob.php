<?php
namespace App\Jobs;

use App\Models\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    // Сделаем свойство public для избежания проблем сериализации в PHP
    public $report; // щоб знати report_id
    public $filePath; // щоб узнати путь файла

    public function __construct(Report $report, string $filePath) {
        $this->report = $report;
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        try {
            // Проверяем, существует ли файл в хранилище storage
            if (!Storage::exists($this->filePath)) {
                Report::where('id', $this->report->id)->update(['status' => 'error']);
                return;
            }

            $absolutePath = Storage::path($this->filePath);
            $extension = pathinfo($absolutePath, PATHINFO_EXTENSION);

            // Пример чтения содержимого, если это JSON:
            if ($extension === 'json') {
                $jsonContent = Storage::get($this->filePath);
                $dataArray = json_decode($jsonContent, true);
                // Если JSON невалидный — выходим с ошибкой
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($dataArray)) {

                    Report::where('id', $this->report->id)->update(['status' => 'error']);
                    Storage::delete($this->filePath);
                    return;
                }

                // 2. Считаем общее количество полезных элементов (объектов)
                $totalRows = count($dataArray);
                $dummyParsedItems = [];
                $i = 0;
                $lastLoggedProgress = 0;

                // 3. Цикл перебора элементов вместо fgetcsv
                foreach ($dataArray as $key => $item) {
                    $i++;

                    // Формируем структуру данных для Vue
                    $dummyParsedItems[] = [
                        'row' => $key,
                        // Сохраняем весь объект текущей строки в JSON-строку, как это делалось для CSV
                        'data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                        'parsed_at' => now()->toTimeString()
                    ];

                    // Расчет и обновление прогресс-бара
                    if ($totalRows > 0) {
                        $currentProgress = min(100, intval(($i / $totalRows) * 100));

                        if ($currentProgress > $lastLoggedProgress || $i === $totalRows) {
                            Report::where('id', $this->report->id)->update([
                                'progress' => $currentProgress
                            ]);

                            $lastLoggedProgress = $currentProgress;
                        }
                    }

                    // Симулируем задержку для плавной анимации фронтенда
//                usleep(50000);
                }
                // 4. Запись финального результата
                $report = Report::find($this->report->id);
                if ($report) {
                    $report->update([
                        'status' => 'completed',
                        'progress' => 100,
                        'parsed_data' => array_slice($dummyParsedItems, 0, 10)
                    ]);
                }

                // Удаляем временный файл и завершаем выполнение джобы, чтобы код CSV ниже не сработал
                Storage::delete($this->filePath);
                return;
            }
            // Считаем точное количество строк в файле без загрузки всего файла в память
            $totalRows = 0;
            if (($handle = fopen($absolutePath, 'r')) !== false) {
                // Безопасное построчное чтение: цикл прервется, если читать нечего
                while (($line = fgets($handle)) !== false) {
                    $totalRows++;
                }
                fclose($handle);
            }
        } catch (\Throwable $e) {
                Log::error('Критическая ошибка в ProcessReportJob: ' . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);

                // Обновляем статус в базе, чтобы фронтенд вывел ошибку, а не зависал на processing
                Report::where('id', $this->report->id)->update([
                    'status' => 'error',
                    'parsed_data' => json_encode(['error' => $e->getMessage()])
                ]);

                // Перевыбрасываем ошибку, чтобы Laravel зафиксировал fail в failed_jobs
                throw $e;
            }

        // CSV
        $dummyParsedItems = [];
        $i = 0; // Счетчик обработанных строк данных
        $lastLoggedProgress = 0; // Запоминаем последний записанный процент

        if (($handle = fopen($absolutePath, 'r')) !== false) {
            // Пропускаем хедеры = читаєм перший рядок до коми
            $headers = fgetcsv($handle, 1000, ",");
            $realTotalRows = max(1, $totalRows - 1); // Если мы пропустили хедер, реальное количество строк данных уменьшилось на 1

            while (($rowColumns = fgetcsv($handle, 1000, ",")) !== false) {
                $i++;

                $dummyParsedItems[] = [
                    'row' => $i,
                    'data' => json_encode($rowColumns, JSON_UNESCAPED_UNICODE),
                    'parsed_at' => now()->toTimeString()
                ];

                $currentProgress = min(100, intval(($i / $realTotalRows) * 100));
                if ($currentProgress > $lastLoggedProgress || $i === $realTotalRows) {
                    Report::where('id', $this->report->id)->update([
                        'progress' => $currentProgress
                    ]);

                    $lastLoggedProgress = $currentProgress;
                }
            }
            fclose($handle);
        }

        $report = Report::find($this->report->id);
        if ($report) {
            $report->update([
                'status' => 'completed',
                'progress' => 100,
                'parsed_data' => array_slice($dummyParsedItems, 0, 10)
            ]);
        }

        // Удаляем временный файл
        Storage::delete($this->filePath);
    }
}
