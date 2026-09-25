<?php
namespace App\Jobs;

use App\Models\Report;
use App\Models\Order; // Ваша модель с данными
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $report;
    public $filters;

    public function __construct(Report $report, array $filters)
    {
        $this->report = $report;
        $this->filters = $filters;
    }

    public function handle(): void
    {
        try {
            // 1. Проверяем, что Job вообще стартовал
            Log::info("CreateReportJob стартовал. Фильтры:", $this->filters);

            // 1. Формируем запрос
            $query = Order::query()
                ->whereBetween('created_at', [$this->filters['start_date'] . ' 00:00:00', $this->filters['end_date'] . ' 23:59:59']);

            if (!empty($this->filters['status'])) {
                $query->where('status', $this->filters['status']);
            }

            // Проверяем, есть ли вообще данные по этим фильтрам
            if (!$query->exists()) {
                Report::where('id', $this->report->id)->update([
                    'status' => 'completed',
                    // Записываем сообщение во Vue, чтобы фронтенд знал, что файл пустой
                    'parsed_data' => ['message' => 'Нет данных за указанный период', 'file_path' => null]
                ]);
                return; // Останавливаем выполнение джоба, файл не создаем
            }

            // 2. Открываем временный файл для записи
            $fileName = 'reports/export_' . time() . '_' . $this->report->id . '.csv';
            $handle = fopen('php://temp', 'r+');

            // BOM для Excel (кириллица)
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ID Заказа', 'Клиент', 'Сумма', 'Статус', 'Дата']);

            $processedRows = 0;

            // Читаем чанками, чтобы не перегружать ОЗУ
            $query->chunk(100, function ($orders) use ($handle, &$processedRows) {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->id,
                        $order->customer_name,
                        $order->total_amount,
                        $order->status,
                        $order->created_at->format('Y-m-d H:i'),
                    ]);
                    $processedRows++;
                }
            });

            // Сохраняем файл в Storage
            rewind($handle);
            Storage::put($fileName, stream_get_contents($handle));
            fclose($handle);

            // 3. ФИНАЛ: Переводим статус в completed и сохраняем путь к файлу в parsed_data
            Report::where('id', $this->report->id)->update([
                'status' => 'completed',
                'parsed_data' => json_encode(['file_path' => $fileName]) // сохраняем путь для скачивания
            ]);

        } catch (\Exception $e) {
            // В случае ошибки пишем статус error
            Report::where('id', $this->report->id)->update([
                'status' => 'error',
                'parsed_data' => json_encode(['error' => $e->getMessage()])
            ]);
        }
    }
}
