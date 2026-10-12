<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportRequest extends Model
{
    use BelongsToTenant, HasFactory;

    public const TYPE_SUPPORT = 'support';

    public const TYPE_BUG = 'bug';

    public const TYPE_FEATURE_REQUEST = 'feature_request';

    public const TYPES = [
        self::TYPE_SUPPORT,
        self::TYPE_BUG,
        self::TYPE_FEATURE_REQUEST,
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'support_conversation_id',
        'support_message_id',
        'assigned_to',
        'type',
        'status',
        'priority',
        'title',
        'description',
        'metadata',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    public function sourceMessage(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
