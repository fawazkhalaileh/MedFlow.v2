<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $makeReceiptNumber = static function (?string $branchCode, string $receivedAt, int $transactionId): string {
            $paddedTransactionId = str_pad((string) $transactionId, 6, '0', STR_PAD_LEFT);
            $normalizedBranchCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $branchCode));

            if (blank($normalizedBranchCode)) {
                return 'RCPT-' . $paddedTransactionId;
            }

            return sprintf(
                'RCPT-%s-%s-%s',
                $normalizedBranchCode,
                Carbon::parse($receivedAt)->format('Ymd'),
                $paddedTransactionId
            );
        };

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('transaction_type');
        });

        DB::table('transactions')
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use ($makeReceiptNumber) {
                foreach ($transactions as $transaction) {
                    $branchCode = DB::table('branches')
                        ->where('id', $transaction->branch_id)
                        ->value('code');

                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'receipt_number' => $makeReceiptNumber(
                                $branchCode,
                                $transaction->received_at ?? Carbon::now()->toDateTimeString(),
                                $transaction->id
                            ),
                        ]);
                }
            });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
            $table->dropColumn('receipt_number');
        });
    }
};
