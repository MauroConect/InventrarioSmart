<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Modules\TenantFeatureService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class MySqlTenantTestCase extends TestCase
{
    private static ?\Illuminate\Database\Connection $schemaConnection = null;
    private static bool $schemaReady = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$schemaReady = false;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName(), 'Only the disposable testing database may be reset.');
        if (! self::$schemaReady) {
            $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
            self::$schemaReady = true;
        }
        self::$schemaConnection = DB::connection();
        DB::beginTransaction();
        config(['tenancy.legacy_mode' => false]);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        while (DB::transactionLevel() > 0) { DB::rollBack(); }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$schemaConnection?->getDatabaseName() === 'testing') {
            self::$schemaConnection->getSchemaBuilder()->dropAllTables();
        }
        self::$schemaConnection = null;
        self::$schemaReady = false;
        parent::tearDownAfterClass();
    }

    protected function commerce(string $name = 'Comercio A', string $role = 'admin'): array
    {
        $user = User::create(['name' => 'Operador ficticio', 'email' => str()->random(12).'@example.test', 'password' => 'Ficticio-12345!', 'role' => 'admin']);
        $user->forceFill(['email_verified_at' => now()])->save();
        $tenant = Tenant::create(['name' => $name, 'slug' => str()->slug($name).'-'.str()->random(8), 'onboarding_completed_at' => now()]);
        $tenant->users()->attach($user, ['role' => $role, 'status' => 'active', 'is_default' => true]);
        app(TenantFeatureService::class)->enableDefaults($tenant, $user->id);
        return [$tenant, $user];
    }

    protected function signIn(Tenant $tenant, User $user): void
    {
        $this->actingAs($user, 'web')->withSession(['tenant_id' => $tenant->id]);
        app(TenantContext::class)->set($tenant);
    }
}
