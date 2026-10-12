<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TenantOnboarding;
use App\Services\Onboarding\AssistedOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private readonly AssistedOnboardingService $onboardingService)
    {
    }

    public function show(Request $request): View|RedirectResponse
    {
        $onboarding = $this->case($request);

        if ($onboarding->isDelivered() && $onboarding->tenant?->isActive()) {
            $request->session()->put(config('tenancy.session_key', 'tenant_id'), $onboarding->tenant_id);

            return redirect()->route('home');
        }

        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (! $onboarding->isSubmitted()) {
            return view('onboarding.questionnaire', [
                'onboarding' => $onboarding,
                'answers' => $onboarding->answers(),
                'rubros' => config('onboarding.rubros'),
            ]);
        }

        return view('onboarding.status', [
            'onboarding' => $onboarding,
            'answers' => $onboarding->answers(),
            'publicNotes' => $onboarding->notes()->where('is_internal', false)->latest()->get(),
            'events' => $onboarding->events()->latest()->limit(8)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $onboarding = $this->case($request);
        abort_if($onboarding->isDelivered(), 409, 'El onboarding ya fue entregado.');

        $answers = $request->validate($this->rules());
        $intent = $request->input('intent', 'draft');

        if ($intent === 'submit') {
            $this->onboardingService->submit($onboarding, $answers);

            return redirect()->route('onboarding.show')->with('success', 'Recibimos la información de tu comercio.');
        }

        $this->onboardingService->saveDraft($onboarding, $answers);

        return back()->with('success', 'Guardamos tu avance. Podés continuar cuando quieras.');
    }

    private function case(Request $request): TenantOnboarding
    {
        return TenantOnboarding::query()->firstOrCreate(
            ['user_id' => $request->user()->getKey()],
            [
                'step' => $request->user()->hasVerifiedEmail() ? 'questionnaire' : 'verify_email',
                'status' => $request->user()->hasVerifiedEmail() ? 'questionnaire_pending' : 'registered',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'locality' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'rubro' => ['required', 'string', 'max:50'],
            'secondary_activities' => ['nullable', 'string', 'max:500'],
            'branches_count' => ['required', 'integer', 'min:1', 'max:200'],
            'cash_registers_count' => ['required', 'integer', 'min:1', 'max:200'],
            'users_count' => ['required', 'integer', 'min:1', 'max:500'],
            'desired_go_live' => ['nullable', 'date'],
            'monthly_sales' => ['required', 'integer', 'min:0', 'max:1000000'],
            'products_count' => ['required', 'integer', 'min:0', 'max:1000000'],
            'peak_hours' => ['nullable', 'string', 'max:255'],
            'sale_types' => ['nullable', 'array'],
            'sale_types.*' => ['string', 'max:40'],
            'peripherals' => ['nullable', 'array'],
            'peripherals.*' => ['string', 'max:40'],
            'stock_management' => ['required', 'in:yes,no,partial'],
            'open_orders' => ['required', 'in:yes,no'],
            'uses_tables' => ['nullable', 'in:yes,no'],
            'appointments' => ['required', 'in:yes,no'],
            'credit_accounts' => ['required', 'in:yes,no'],
            'cheques' => ['required', 'in:yes,no'],
            'identify_customers' => ['required', 'in:yes,no'],
            'electronic_invoicing' => ['required', 'in:yes,no'],
            'fiscal_condition' => ['nullable', 'string', 'max:255'],
            'payment_methods' => ['nullable', 'string', 'max:255'],
            'current_system' => ['nullable', 'string', 'max:255'],
            'data_to_migrate' => ['nullable', 'string', 'max:1000'],
            'pain_points' => ['nullable', 'string', 'max:1000'],
            'required_reports' => ['nullable', 'string', 'max:1000'],
            'special_features' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
