<?php

namespace Tests\Unit;

use App\Services\Onboarding\QuestionnaireClassifier;
use PHPUnit\Framework\TestCase;

class QuestionnaireClassifierTest extends TestCase
{
    public function test_it_suggests_modules_from_a_heladeria_profile(): void
    {
        $result = (new QuestionnaireClassifier)->classify([
            'rubro' => 'heladeria',
            'sale_types' => ['weight'],
            'peripherals' => ['scanner'],
            'open_orders' => 'yes',
            'credit_accounts' => 'yes',
            'electronic_invoicing' => 'yes',
            'monthly_sales' => 3000,
            'special_features' => 'Combo de temporada',
        ]);

        $this->assertSame('heladeria', $result['profile']);
        $this->assertContains('sales.open_orders', $result['modules']);
        $this->assertContains('catalog.weighted', $result['modules']);
        $this->assertContains('catalog.scanner', $result['modules']);
        $this->assertContains('finance.credit', $result['modules']);
        $this->assertContains('fiscal.afip', $result['modules']);
        $this->assertNotEmpty($result['notes']);
    }

    public function test_it_suggests_retail_scanner_for_a_pharmacy(): void
    {
        $result = (new QuestionnaireClassifier)->classify([
            'rubro' => 'farmacia',
            'monthly_sales' => 4000,
            'open_orders' => 'no',
            'appointments' => 'no',
        ]);

        $this->assertContains('catalog.scanner', $result['modules']);
        $this->assertNotContains('sales.open_orders', $result['modules']);
        $this->assertNotEmpty($result['notes']);
    }

    public function test_it_notes_appointments_for_a_consultorio(): void
    {
        $result = (new QuestionnaireClassifier)->classify([
            'rubro' => 'consultorio',
            'appointments' => 'yes',
            'open_orders' => 'no',
        ]);

        $this->assertContains('services.appointments', $result['modules']);
        $this->assertTrue(collect($result['notes'])->contains(fn ($note) => str_contains($note, 'turnos')));
    }
}
