<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeDoctor\ImportTimeDoctorCsvRequest;
use App\Models\TimeDoctorImport;
use App\Services\TimeDoctorCsvImportService;
use Illuminate\Http\RedirectResponse;

class TimeDoctorImportController extends Controller
{
    public function __construct(private readonly TimeDoctorCsvImportService $imports) {}

    public function index()
    {
        $imports = $this->imports->paginateImports();

        return view('time-doctor.imports.index', compact('imports'));
    }

    public function store(ImportTimeDoctorCsvRequest $request): RedirectResponse
    {
        $import = $this->imports->import($request->file('csv_file'), $request->user());

        return redirect()
            ->route('time-doctor.imports.show', $import)
            ->with('success', "Time Doctor CSV imported: {$import->processed_rows} processed, {$import->skipped_rows} skipped.");
    }

    public function show(TimeDoctorImport $import)
    {
        $records = $this->imports->paginateRecords($import);
        $leaveConflictCount = $import->records()->where('worked_on_leave', true)->count();
        $import->load('importer');

        return view('time-doctor.imports.show', compact('import', 'records', 'leaveConflictCount'));
    }
}
