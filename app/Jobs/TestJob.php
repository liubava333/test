<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

Class TestJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue,Queueable,SerializesModels;


}
