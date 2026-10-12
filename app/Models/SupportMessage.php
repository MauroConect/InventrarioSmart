<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use BelongsToTenant, HasFactory;

    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    public const ROLE_PLATFORM = 'platform';

    public const ROLE_SYSTEM = 'system';

    public const ROLES = [
        self::ROLE_USER,
        self::ROLE_ASSISTANT,
        self::ROLE_PLATFORM,
        self::ROLE_SYSTEM,
    ];

    protected $fillable = [
        'support_conversation_id',
        'sender_id',
        'role',
        'body',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
