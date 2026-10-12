<?php

namespace Tests\Unit;

use App\Models\SupportRequest;
use App\Services\SupportRequestClassifier;
use PHPUnit\Framework\TestCase;

class SupportRequestClassifierTest extends TestCase
{
    public function test_it_classifies_bugs_features_and_support_with_a_safe_default(): void
    {
        $classifier = new SupportRequestClassifier;

        $bug = $classifier->classify('Error urgente: no puedo vender porque la pantalla falla.');
        $feature = $classifier->classify('Sería bueno que pudieran agregar una nueva función.');
        $support = $classifier->classify('Necesito ayuda para entender el cierre de caja.');

        $this->assertSame(SupportRequest::TYPE_BUG, $bug['type']);
        $this->assertSame('high', $bug['priority']);
        $this->assertSame(SupportRequest::TYPE_FEATURE_REQUEST, $feature['type']);
        $this->assertSame(SupportRequest::TYPE_SUPPORT, $support['type']);
    }
}
