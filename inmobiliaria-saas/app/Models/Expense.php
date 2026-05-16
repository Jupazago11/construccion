<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'company_id',
        'project_id',
        'category_id',
        'subcategory_id',
        'auxiliary_id',
        'provider_id',
        'invoice_id',
        'product_id',
        'created_by',
        'expense_number',
        'expense_date',
        'payment_method',
        'description',
        'subtotal_amount',
        'unit_price',
        'quantity',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'subtotal_amount' => 'decimal:2',
            'unit_price' => 'decimal:4',
            'quantity' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function auxiliary(): BelongsTo
    {
        return $this->belongsTo(Auxiliary::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class);
    }
}
