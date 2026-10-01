<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    // обовзяково public - іначе не скачає
    public $reportId;
    public $status;
    public $progress;

    // Передаем ID отчета и его новый статус ('completed' или 'error')
    public function __construct($reportId, string $status, float $progress)
    {
        $this->reportId = $reportId;
        $this->status = $status;
        $this->progress = $progress;
    }

    // Определяем обичний канал, на который подпишется фронтенд
    public function broadcastOn(): array
    {
        return [
            new Channel('reports.' . $this->reportId),
        ];
    }

    // Имя события, которое поймает Vue.js (точка перед именем важна во Vue)
    public function broadcastAs(): string
    {
        return 'report.status';
    }
}

