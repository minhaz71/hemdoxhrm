<?php

namespace App\Http\Controllers;

use App\Services\ErrorLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ErrorLogController extends Controller
{
    public function __construct(private readonly ErrorLogService $logs) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'file' => ['nullable', 'string', 'max:120'],
            'level' => ['nullable', 'in:error,warning,info,debug'],
            'limit' => ['nullable', 'integer', 'min:25', 'max:500'],
        ]);

        $file = $filters['file'] ?? 'laravel.log';
        $level = $filters['level'] ?? null;
        $limit = (int) ($filters['limit'] ?? 100);

        return view('admin.error-logs.index', [
            'files' => $this->logs->files(),
            'entries' => $this->logs->entries($file, $level, $limit),
            'filters' => compact('file', 'level', 'limit'),
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'string', 'max:120'],
        ]);

        $this->logs->clear($data['file']);

        return redirect()->route('admin.error-logs.index', ['file' => $data['file']])
            ->with('success', 'Log file cleared.');
    }
}
