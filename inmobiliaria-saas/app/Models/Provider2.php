<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Provider2 extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $table = 'providers2';

    protected $fillable = [
        'company_id',
        'provider2_type_id',
        'name',
        'location',
        'document_number',
        'phone',
        'email',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Provider2Type::class, 'provider2_type_id');
    }
}
