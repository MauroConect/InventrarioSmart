<?php

namespace App\Services\Fiscal;

interface FiscalGateway
{
    public function lastVoucher(int $point, int $type): int;
    public function findVoucher(int $number, int $point, int $type): ?array;
    public function createVoucher(array $data): array;
}
