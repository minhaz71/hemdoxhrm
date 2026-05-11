<?php

namespace App\Http\Controllers;

use App\Http\Requests\Holiday\ImportHolidayCsvRequest;
use App\Services\HolidayCsvImportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HolidayCsvImportController extends Controller
{
    public function __construct(private readonly HolidayCsvImportService $imports) {}

    public function index(Request $request)
    {
        return view('holidays.import', $this->imports->formData() + [
            'report' => $request->session()->get('holiday_csv_import_report'),
        ]);
    }

    public function store(ImportHolidayCsvRequest $request)
    {
        $report = $this->imports->import($request->file('csv_file'), $request->user());

        $message = "Holiday CSV import complete: {$report['created']} created, {$report['failed']} failed.";

        return redirect()
            ->route('holidays.import.index')
            ->with('holiday_csv_import_report', $report)
            ->with($report['failed'] > 0 ? 'warning' : 'success', $message);
    }

    public function sample(): BinaryFileResponse
    {
        return response()->download(
            $this->imports->sampleDownloadPath(),
            'holiday-import-sample.csv',
            ['Content-Type' => 'text/csv']
        );
    }
}
