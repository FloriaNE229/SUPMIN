<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Modules\Report\Services\ReportBuilderService;
use App\Modules\Mission\Models\Mission;
use Illuminate\Support\Facades\Log;

class GeneratePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $missionId;

    public function __construct($missionId)
    {
        $this->missionId = $missionId;
    }

 public function handle(): void
{
    try {

        Log::info('JOB START', [
            'mission_id' => $this->missionId
        ]);

        $builder = app(ReportBuilderService::class);

        $report = $builder->build($this->missionId);

        if (!$report) {
            Log::error('REPORT NULL', [
                'mission_id' => $this->missionId
            ]);
            return;
        }

        Log::info('REPORT OK');

        $pdf = Pdf::loadView('pdf.report', [
            'report' => $report
        ]);

        $fileName = 'pdfs/mission_' . $this->missionId . '_' . Str::uuid() . '.pdf';

        Storage::disk('public')->put($fileName, $pdf->output());

        Log::info('PDF SAVED', [
            'file' => $fileName
        ]);

        $mission = Mission::find($this->missionId);

        if (!$mission) {
            Log::error('MISSION NOT FOUND IN JOB');
            return;
        }

        $mission->update([
            'pdf_path' => $fileName
        ]);

        Log::info('PDF PATH SAVED');

    } catch (\Exception $e) {

        Log::error('PDF ERROR', [
            'mission_id' => $this->missionId,
            'message' => $e->getMessage()
        ]);
    }
}
}