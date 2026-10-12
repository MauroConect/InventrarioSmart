<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        $catalog = collect(config('modules.catalog', []));

        return view('marketing.landing', [
            'marketingBaseModules' => $catalog->where('family', 'base')->values()->all(),
            'marketingOptionalModules' => $catalog->where('is_ready', true)
                ->whereIn('family', ['retail', 'hospitality', 'services', 'finance'])
                ->map(fn (array $module) => collect($module)->only(['key', 'name', 'description'])->all())
                ->values()->all(),
        ]);
    }
}
