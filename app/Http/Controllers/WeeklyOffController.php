<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeeklyOff\StoreWeeklyOffRequest;
use App\Http\Requests\WeeklyOff\UpdateDefaultWeeklyOffRequest;
use App\Http\Requests\WeeklyOff\UpdateWeeklyOffRequest;
use App\Models\EmployeeWeeklyOff;
use App\Services\SettingService;
use App\Services\WeeklyOffService;
use Illuminate\Http\Request;

class WeeklyOffController extends Controller
{
    public function __construct(
        private readonly WeeklyOffService $weeklyOffs,
        private readonly SettingService   $settings,
    ) {}

    /**
     * Deny HR users if the admin has disabled HR weekly-off management.
     */
    private function guardHrAccess(): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;

        $hrAllowed = $this->settings->get('hr_manage_weekly_off', true);
        if (! $hrAllowed || $hrAllowed === 'false') {
            abort(403, 'HR management of weekly offs is disabled by the administrator. Contact an Admin.');
        }
    }

    public function index(Request $request)
    {
        $weeklyOffs = $this->weeklyOffs->paginate($request->only(['employee_id', 'day_of_week', 'status']));

        return view('weekly-offs.index', array_merge(
            $this->weeklyOffs->formOptions(),
            compact('weeklyOffs')
        ));
    }

    public function create()
    {
        $this->guardHrAccess();

        return view('weekly-offs.create', $this->weeklyOffs->formOptions());
    }

    public function store(StoreWeeklyOffRequest $request)
    {
        $this->guardHrAccess();

        $weeklyOff = $this->weeklyOffs->create($request->validated(), $request->user());

        return redirect()->route('weekly-offs.edit', $weeklyOff)->with('success', 'Weekly off assigned successfully.');
    }

    public function edit(EmployeeWeeklyOff $weekly_off)
    {
        $weeklyOff = $weekly_off;

        return view('weekly-offs.edit', array_merge($this->weeklyOffs->formOptions(), compact('weeklyOff')));
    }

    public function update(UpdateWeeklyOffRequest $request, EmployeeWeeklyOff $weekly_off)
    {
        $this->guardHrAccess();

        $this->weeklyOffs->update($weekly_off, $request->validated(), $request->user());

        return redirect()->route('weekly-offs.index')->with('success', 'Weekly off updated successfully.');
    }

    public function destroy(EmployeeWeeklyOff $weekly_off)
    {
        $this->guardHrAccess();

        $weekly_off->update(['status' => 'inactive', 'effective_to' => now()->toDateString()]);

        return back()->with('success', 'Weekly off deactivated.');
    }

    public function updateDefaults(UpdateDefaultWeeklyOffRequest $request)
    {
        $this->guardHrAccess();

        $this->weeklyOffs->updateDefaultDays($request->validated('default_weekly_off_days') ?? []);

        return back()->with('success', 'Default company weekly off updated.');
    }
}
