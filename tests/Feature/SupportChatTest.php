<?php

namespace Tests\Feature;

use App\Http\Controllers\PlatformSupportController;
use App\Http\Controllers\SupportChatController;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SupportChatService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'support_testing',
            'database.connections.support_testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge();

        Schema::disableForeignKeyConstraints();
        foreach ([
            'support_requests',
            'support_messages',
            'support_conversations',
            'tenant_user',
            'tenants',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('rubro_profile')->default('base');
            $table->json('features')->nullable();
            $table->string('status')->default('active');
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('vendedor');
            $table->boolean('is_platform_superadmin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('tenant_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('user_id');
            $table->string('role');
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('support_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('user_id');
            $table->string('subject');
            $table->string('status');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('support_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('support_conversation_id');
            $table->foreignId('sender_id')->nullable();
            $table->string('role');
            $table->text('body');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('support_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('support_conversation_id')->unique();
            $table->foreignId('support_message_id')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->string('type');
            $table->string('status');
            $table->string('priority');
            $table->string('title');
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        Schema::disableForeignKeyConstraints();
        foreach ([
            'support_requests',
            'support_messages',
            'support_conversations',
            'tenant_user',
            'tenants',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_authenticated_tenant_user_starts_a_classified_conversation_without_real_api_calls(): void
    {
        [$tenant, $user] = $this->tenantAndUser('uno');
        app(TenantContext::class)->set($tenant);
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.test/api/v1',
        ]);
        Http::fake([
            'openrouter.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'Te ayudo a revisar el problema.']]],
            ]),
        ]);

        $request = Request::create('/api/support/conversations', 'POST', [
            'message' => 'Error urgente: no puedo vender.',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app(SupportChatController::class)->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('support_conversations', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => SupportConversation::STATUS_WAITING_PLATFORM,
        ]);
        $this->assertDatabaseHas('support_requests', [
            'tenant_id' => $tenant->id,
            'type' => SupportRequest::TYPE_BUG,
            'priority' => 'high',
        ]);
        $this->assertDatabaseHas('support_messages', [
            'tenant_id' => $tenant->id,
            'role' => SupportMessage::ROLE_ASSISTANT,
            'body' => 'Te ayudo a revisar el problema.',
        ]);
        Http::assertSentCount(1);
    }

    public function test_missing_openrouter_configuration_uses_fallback_and_tenant_scope_stays_closed(): void
    {
        [$tenantA, $user] = $this->tenantAndUser('a');
        [$tenantB] = $this->tenantAndUser('b');
        config(['services.openrouter.key' => null]);
        Http::fake();

        app(TenantContext::class)->run(
            $tenantA,
            fn () => app(SupportChatService::class)->start($user, 'Necesito ayuda con una consulta.')
        );

        $fallbackMessage = app(TenantContext::class)->run(
            $tenantA,
            fn () => SupportMessage::query()->where('role', SupportMessage::ROLE_ASSISTANT)->firstOrFail()
        );
        $this->assertTrue($fallbackMessage->metadata['fallback']);
        $this->assertSame(
            0,
            app(TenantContext::class)->run($tenantB, fn () => SupportConversation::query()->count())
        );
        $this->assertSame(0, SupportConversation::query()->count());
        Http::assertNothingSent();
    }

    public function test_platform_reply_is_visible_to_tenant_polling(): void
    {
        [$tenant, $user] = $this->tenantAndUser('reply');
        config(['services.openrouter.key' => null]);
        Http::fake();
        $conversation = app(TenantContext::class)->run(
            $tenant,
            fn () => app(SupportChatService::class)->start($user, 'Necesito ayuda.')
        );

        $operator = User::query()->create([
            'name' => 'Plataforma',
            'email' => 'platform@example.test',
            'password' => 'secret',
        ]);
        $operator->forceFill(['is_platform_superadmin' => true])->save();

        $replyRequest = Request::create('/platform/support/reply', 'POST', [
            'message' => 'Estamos revisando tu consulta.',
        ]);
        $replyRequest->setUserResolver(fn () => $operator);
        $response = app(PlatformSupportController::class)->reply($replyRequest, $conversation->id);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertFalse(app(TenantContext::class)->hasTenant());
        $this->assertDatabaseHas('support_messages', [
            'tenant_id' => $tenant->id,
            'support_conversation_id' => $conversation->id,
            'role' => SupportMessage::ROLE_PLATFORM,
            'body' => 'Estamos revisando tu consulta.',
        ]);

        $pollRequest = Request::create('/api/support/poll', 'GET', ['after_id' => 0]);
        $pollRequest->setUserResolver(fn () => $user);
        $payload = app(TenantContext::class)->run(
            $tenant,
            fn () => app(SupportChatController::class)->poll($pollRequest, $conversation->id)->getData(true)
        );

        $this->assertContains(
            SupportMessage::ROLE_PLATFORM,
            array_column($payload['messages'], 'role')
        );
    }

    /**
     * @return array{Tenant, User}
     */
    private function tenantAndUser(string $suffix): array
    {
        $tenant = Tenant::query()->create([
            'slug' => 'tenant-'.$suffix,
            'name' => 'Tenant '.$suffix,
        ]);
        $user = User::query()->create([
            'name' => 'Usuario '.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'secret',
        ]);

        return [$tenant, $user];
    }
}
