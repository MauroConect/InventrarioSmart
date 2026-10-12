<?php

namespace Tests\Feature;

use App\Jobs\TenantJob;
use App\Logging\TenantLogContext;
use App\Support\Tenancy\{TenantContext, TenantRuntime, TenantScheduler};
use Illuminate\Support\Facades\{Bus, Log};
use Monolog\Handler\TestHandler;
use RuntimeException;
use Tests\MySqlTenantTestCase;

class TenantProbeJob extends TenantJob
{
    public static array $seen = [];
    public bool $fail = false;

    protected function perform(TenantRuntime $runtime): void
    {
        self::$seen[] = [$this->tenantId, app(TenantContext::class)->id(), $runtime->remember('same-key', 60, fn () => $this->tenantId), $runtime->privatePath('probe.txt')];
        if ($this->fail) { throw new RuntimeException('Simulated job failure'); }
    }
}

class TenantBackgroundJobsTest extends MySqlTenantTestCase
{
    public function test_consecutive_jobs_cache_storage_and_exceptions_do_not_mix_context(): void
    {
        [$a, $userA] = $this->commerce('A'); [$b, $userB] = $this->commerce('B');
        TenantProbeJob::$seen = [];
        Bus::dispatchSync((new TenantProbeJob($a->id, $userA->id))->beforeCommit());
        $this->assertFalse(app(TenantContext::class)->hasTenant());
        Bus::dispatchSync((new TenantProbeJob($b->id, $userB->id))->beforeCommit());
        $this->assertSame([$a->id, $a->id, $a->id, 'tenants/'.$a->uuid.'/probe.txt'], TenantProbeJob::$seen[0]);
        $this->assertSame([$b->id, $b->id, $b->id, 'tenants/'.$b->uuid.'/probe.txt'], TenantProbeJob::$seen[1]);
        $failure = (new TenantProbeJob($a->id))->beforeCommit(); $failure->fail = true;
        try { Bus::dispatchSync($failure); $this->fail('Failure expected'); } catch (RuntimeException $e) { $this->assertSame('Simulated job failure', $e->getMessage()); }
        $this->assertFalse(app(TenantContext::class)->hasTenant());
    }

    public function test_inactive_commerce_and_foreign_actor_are_rejected_and_scheduler_filters(): void
    {
        [$a, $userA] = $this->commerce('A'); [$b, $userB] = $this->commerce('B');
        $b->update(['status' => 'suspended']);
        foreach ([new TenantProbeJob($b->id), new TenantProbeJob($a->id, $userB->id)] as $job) {
            try { Bus::dispatchSync($job->beforeCommit()); $this->fail('Unauthorized job accepted'); } catch (RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
            $this->assertFalse(app(TenantContext::class)->hasTenant());
        }
        $seen = [];
        app(TenantScheduler::class)->forEachActive(function ($tenant) use (&$seen) { $seen[] = app(TenantContext::class)->id(); });
        $this->assertSame([$a->id], $seen);
        $this->assertFalse(app(TenantContext::class)->hasTenant());
    }

    public function test_log_context_is_dynamic_and_redacts_secrets(): void
    {
        [$a] = $this->commerce();
        config(['logging.channels.tenant_test' => ['driver' => 'monolog', 'handler' => TestHandler::class, 'tap' => [TenantLogContext::class]]]);
        $logger = Log::channel('tenant_test');
        app(TenantContext::class)->run($a, fn () => $logger->info('test.event', ['passphrase_certificado' => 'SENTINEL-SECRET']));
        $logger->info('platform.event');
        $records = $logger->getLogger()->getHandlers()[0]->getRecords();
        $this->assertSame($a->id, $records[0]->extra['tenant_id']);
        $this->assertSame('[REDACTED]', $records[0]->context['passphrase_certificado']);
        $this->assertNull($records[1]->extra['tenant_id']);
    }
}
