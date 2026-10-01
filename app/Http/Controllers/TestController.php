<?php
namespace App\Http\Controllers;

use App\Jobs\CreateReportJob;
use App\Jobs\TestJob;
use App\Models\Image;
use App\Models\Report;
use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Class TestController extends Controller{

    public function generate(Request $request): JsonResponse {
        Log::info('controller generate');
        $request->validate([
            'start_date' => 'date|required',
            'end_date' => 'date|required',
            'status' => 'nullable|string'
        ]);


        $report = Report::create([
            'status' => 'pending',
            'type' => 'export'
        ]);

        TestJob::dispatch($report, $request->all());
        return response()->json($report);

    }
    public function download ($id): BinaryFileResponse {
        $report = Report::findOrFail($id);
        $filePath = $report->parsed_data['file_path'] ?? null;
        Log::info($filePath);
        if(!$filePath || !Storage::disk('local')->exists($filePath)) {
            abort(404, 'not found');
        }

        $absolutePath = Storage::disk('local')->path($filePath);
        Log::info($absolutePath);
        return response()->download($absolutePath)->deleteFileAfterSend();
    }
}
