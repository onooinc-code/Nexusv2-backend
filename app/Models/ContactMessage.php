<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'thread_id',
        'sender_contact_id',
        'channel',
        'source',
        'external_id',
        'sender_identifier',
        'sender_name',
        'direction',
        'body',
        'language',
        'attachments_metadata',
        'raw_metadata',
        'import_batch_id',
        'dedupe_hash',
        'source_timestamp',
    ];

    protected $casts = [
        'attachments_metadata' => 'array',
        'raw_metadata' => 'array',
        'source_timestamp' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function senderContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'sender_contact_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ContactMessageThread::class, 'thread_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ContactImportBatch::class, 'import_batch_id');
    }
}
