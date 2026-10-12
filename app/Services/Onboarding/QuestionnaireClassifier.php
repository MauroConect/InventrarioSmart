<?php

namespace App\Services\Onboarding;

class QuestionnaireClassifier
{
    /**
     * @param  array<string, mixed>  $answers
     * @return array{profile: string, modules: list<string>, notes: list<string>}
     */
    public function classify(array $answers): array
    {
        $profile = (string) ($answers['rubro'] ?? 'base');
        $modules = ['core.catalog', 'core.stock', 'core.pos', 'core.cash', 'platform.support'];
        $notes = [];

        $saleTypes = $this->list($answers['sale_types'] ?? []);
        $peripherals = $this->list($answers['peripherals'] ?? []);
        $retailProfiles = ['retail', 'polirubro', 'farmacia'];
        $hospitalityProfiles = ['heladeria', 'gastronomia', 'bar'];
        $appointmentProfiles = ['servicios', 'consultorio'];

        if (in_array($profile, $hospitalityProfiles, true) || ($answers['open_orders'] ?? '') === 'yes') {
            $modules[] = 'sales.open_orders';
            $notes[] = 'Proponer módulo de ventas abiertas (heladería / bar / restaurant).';
        }

        if (($answers['uses_tables'] ?? '') === 'yes') {
            $notes[] = 'Pidió mesas/comandas: queda como módulo futuro sobre ventas abiertas, o desarrollo a medida.';
        }

        if (in_array($profile, $retailProfiles, true) || in_array('scanner', $peripherals, true) || ($answers['uses_scanner'] ?? '') === 'yes') {
            $modules[] = 'catalog.scanner';
        }

        if (in_array($profile, $retailProfiles, true) || (int) ($answers['monthly_sales'] ?? 0) >= 2000) {
            $notes[] = 'Volumen de mostrador: priorizar POS de polirubro (scanner, ticket rápido, caja concurrente).';
        }

        if (in_array('weight', $saleTypes, true)) {
            $modules[] = 'catalog.weighted';
        }

        if (in_array($profile, $appointmentProfiles, true) || ($answers['appointments'] ?? '') === 'yes') {
            $modules[] = 'services.appointments';
            $notes[] = 'Perfil de agenda: habilitar módulo de turnos.';
        }

        if (($answers['credit_accounts'] ?? '') === 'yes') {
            $modules[] = 'finance.credit';
        }

        if (($answers['cheques'] ?? '') === 'yes') {
            $modules[] = 'finance.cheques';
        }

        if (($answers['electronic_invoicing'] ?? '') === 'yes') {
            $modules[] = 'fiscal.afip';
            $notes[] = 'Confirmar condición fiscal y puntos de venta antes de activar AFIP.';
        }

        if (filled($answers['special_features'] ?? null)) {
            $notes[] = 'Hay pedidos de funciones especiales: evaluar si van a un módulo común o a desarrollo a medida.';
        }

        return [
            'profile' => $profile,
            'modules' => array_values(array_unique($modules)),
            'notes' => $notes,
        ];
    }

    /**
     * @return list<string>
     */
    private function list(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        return is_array($value) ? array_values(array_filter($value, fn ($item) => is_string($item) && $item !== '')) : [];
    }
}
