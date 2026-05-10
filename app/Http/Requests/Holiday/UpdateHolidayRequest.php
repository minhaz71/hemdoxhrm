<?php

namespace App\Http\Requests\Holiday;

use Illuminate\Validation\Validator;

class UpdateHolidayRequest extends StoreHolidayRequest
{
    /**
     * Override to exclude the holiday currently being updated from the
     * overlap detection query.  The route-model-bound {holiday} parameter
     * is available via $this->route('holiday').
     */
    protected function validateNoOverlap(Validator $v, ?int $ignoreId = null): void
    {
        $holiday = $this->route('holiday');
        parent::validateNoOverlap($v, $holiday?->id);
    }
}
