<?php

namespace App\Jobs;

use App\Jobs\Middleware\TenantJobContext;
use App\Support\Tenancy\TenantRuntime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

abstract class TenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public int $tenantId, public ?int $actorUserId = null)
    {
        if ($tenantId < 1 || ($actorUserId !== null && $actorUserId < 1)) { throw new \InvalidArgumentException('Identidad de job inválida.'); }
        $this->afterCommit();
    }

    final public function middleware(): array { return [new TenantJobContext]; }

    final public function handle(TenantRuntime $runtime): void { $this->perform($runtime); }

    abstract protected function perform(TenantRuntime $runtime): void;
}
