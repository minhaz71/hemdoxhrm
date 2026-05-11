<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryHistory;
use App\Models\SalaryIncrementEmailLog;
use App\Services\IncrementEmailService;
use Illuminate\Http\Request;

class IncrementEmailController extends Controller
{
    public function __construct(
        private readonly IncrementEmailService $service,
    ) {}

    // ── GET /increment-emails ─────────────────────────────────────

    public function index(Request $request)
    {
        $filters = $request->only(['from', 'to', 'employee_id', 'type']);

        $records   = $this->service->paginate($filters);
        $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $template  = $this->service->getDefaultTemplate();

        // Map salary_history_id → latest sent log for "already sent" badges
        $historyIds = $records->pluck('id')->toArray();
        $sentLogs   = SalaryIncrementEmailLog::whereIn('salary_history_id', $historyIds)
            ->where('status', SalaryIncrementEmailLog::STATUS_SENT)
            ->orderByDesc('sent_at')
            ->get()
            ->keyBy('salary_history_id');

        $types = [
            SalaryHistory::TYPE_INCREMENT  => 'Increment',
            SalaryHistory::TYPE_DECREMENT  => 'Decrement',
            SalaryHistory::TYPE_ADJUSTMENT => 'Adjustment',
        ];

        return view('increment-emails.index', compact(
            'records', 'employees', 'filters', 'sentLogs', 'types', 'template',
        ));
    }

    // ── POST /increment-emails/template — save defaults ───────────

    public function templateSave(Request $request)
    {
        $data = $request->validate([
            'subject'           => ['required', 'string', 'max:255'],
            'intro'             => ['nullable', 'string', 'max:2000'],
            'closing'           => ['nullable', 'string', 'max:2000'],
            'signature_name'    => ['nullable', 'string', 'max:100'],
            'signature_title'   => ['nullable', 'string', 'max:100'],
            'signature_contact' => ['nullable', 'string', 'max:200'],
        ]);

        $this->service->saveDefaultTemplate($data);

        return redirect()->back()->with('success', 'Email template saved successfully.');
    }

    // ── POST /increment-emails/preview — show preview screen ──────

    public function preview(Request $request)
    {
        $request->validate([
            'selected'   => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:salary_histories,id'],
        ]);

        $ids      = $request->input('selected');
        $records  = $this->service->getByIds($ids);
        $template = $this->service->getDefaultTemplate();

        if ($records->isEmpty()) {
            return redirect()->route('increment-emails.index')
                ->with('error', 'No valid salary history records found for the selection.');
        }

        // Pre-render each email with the default template
        $previews = $records->map(fn ($sh) => [
            'salaryHistory' => $sh,
            'email'         => $sh->employee?->user?->email,
            'subject'       => $this->service->substitutePlaceholders($template['subject'], $sh),
            'body'          => $this->service->renderEmail($sh, $template),
        ]);

        return view('increment-emails.preview', compact('previews', 'ids', 'template'));
    }

    // ── POST /increment-emails/render — AJAX live preview ─────────

    public function render(Request $request)
    {
        $data = $request->validate([
            'salary_history_id' => ['required', 'integer', 'exists:salary_histories,id'],
            'subject'           => ['nullable', 'string', 'max:255'],
            'intro'             => ['nullable', 'string', 'max:2000'],
            'closing'           => ['nullable', 'string', 'max:2000'],
            'signature_name'    => ['nullable', 'string', 'max:100'],
            'signature_title'   => ['nullable', 'string', 'max:100'],
            'signature_contact' => ['nullable', 'string', 'max:200'],
        ]);

        $sh = SalaryHistory::with('employee.user')->findOrFail($data['salary_history_id']);

        $defaults = $this->service->getDefaultTemplate();

        $template = [
            'subject'           => $data['subject']           ?? $defaults['subject'],
            'intro'             => $data['intro']             ?? $defaults['intro'],
            'closing'           => $data['closing']           ?? $defaults['closing'],
            'signature_name'    => $data['signature_name']    ?? $defaults['signature_name'],
            'signature_title'   => $data['signature_title']   ?? $defaults['signature_title'],
            'signature_contact' => $data['signature_contact'] ?? $defaults['signature_contact'],
        ];

        try {
            $html = $this->service->renderEmail($sh, $template);
            return response()->json(['html' => $html]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── POST /increment-emails/send — fire the emails ─────────────

    public function send(Request $request)
    {
        $data = $request->validate([
            'selected'          => ['required', 'array', 'min:1'],
            'selected.*'        => ['integer', 'exists:salary_histories,id'],
            'subject'           => ['nullable', 'string', 'max:255'],
            'intro'             => ['nullable', 'string', 'max:2000'],
            'closing'           => ['nullable', 'string', 'max:2000'],
            'signature_name'    => ['nullable', 'string', 'max:100'],
            'signature_title'   => ['nullable', 'string', 'max:100'],
            'signature_contact' => ['nullable', 'string', 'max:200'],
        ]);

        $defaults = $this->service->getDefaultTemplate();

        // Merge submitted values over defaults; empty string falls back to default
        $template = [
            'subject'           => filled($data['subject']           ?? null) ? $data['subject']           : $defaults['subject'],
            'intro'             => $data['intro']             ?? $defaults['intro'],
            'closing'           => $data['closing']           ?? $defaults['closing'],
            'signature_name'    => filled($data['signature_name']    ?? null) ? $data['signature_name']    : $defaults['signature_name'],
            'signature_title'   => $data['signature_title']   ?? $defaults['signature_title'],
            'signature_contact' => $data['signature_contact'] ?? $defaults['signature_contact'],
        ];

        $results = $this->service->send($data['selected'], auth()->user(), $template);

        $msg   = "{$results['sent']} email(s) sent successfully.";
        $flash = 'success';

        if ($results['failed'] > 0) {
            $msg  .= " {$results['failed']} failed.";
            $flash = $results['sent'] === 0 ? 'error' : 'warning';
            if (! empty($results['errors'])) {
                $msg .= ' — ' . implode(' | ', array_slice($results['errors'], 0, 3));
            }
        }

        return redirect()->route('increment-emails.index')->with($flash, $msg);
    }

    // ── GET /increment-emails/logs ────────────────────────────────

    public function logs(Request $request)
    {
        $filters   = $request->only(['status', 'employee_id']);
        $logs      = $this->service->paginateLogs($filters);
        $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('increment-emails.logs', compact('logs', 'filters', 'employees'));
    }

    // ── GET /increment-emails/logs/{log} ──────────────────────────

    public function logShow(SalaryIncrementEmailLog $log)
    {
        $log->load(['employee', 'sentBy', 'salaryHistory.employee']);

        return view('increment-emails.log-show', compact('log'));
    }
}
