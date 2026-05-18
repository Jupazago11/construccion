<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('item_mode')->default('product')->after('type');
        });

        DB::statement("
            UPDATE invoices
            SET item_mode = 'activity'
            WHERE EXISTS (
                SELECT 1
                FROM expenses
                WHERE expenses.invoice_id = invoices.id
                  AND expenses.status != 'deleted'
                  AND expenses.activity_id IS NOT NULL
            )
            AND NOT EXISTS (
                SELECT 1
                FROM expenses
                WHERE expenses.invoice_id = invoices.id
                  AND expenses.status != 'deleted'
                  AND expenses.product_id IS NOT NULL
            )
        ");

        DB::statement("
            UPDATE invoices
            SET item_mode = 'activity'
            WHERE EXISTS (
                SELECT 1
                FROM purchases
                WHERE purchases.invoice_id = invoices.id
                  AND purchases.status != 'deleted'
                  AND purchases.activity_id IS NOT NULL
            )
            AND NOT EXISTS (
                SELECT 1
                FROM purchases
                WHERE purchases.invoice_id = invoices.id
                  AND purchases.status != 'deleted'
                  AND purchases.product_id IS NOT NULL
            )
        ");
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('item_mode');
        });
    }
};
