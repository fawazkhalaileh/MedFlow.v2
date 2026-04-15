<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $transaction->receipt_number }}</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.5;
        }

        .receipt-shell {
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 24px;
        }

        .row {
            width: 100%;
        }

        .row::after {
            content: "";
            display: block;
            clear: both;
        }

        .left {
            float: left;
            width: 62%;
        }

        .right {
            float: right;
            width: 34%;
            text-align: right;
        }

        .muted {
            color: #64748b;
        }

        .eyebrow {
            color: #2563eb;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .brand {
            font-size: 26px;
            font-weight: 700;
            margin: 6px 0 4px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .meta-card,
        .totals-card,
        .notice-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 18px;
        }

        .notice-card {
            background: #fff7ed;
            border-color: #fdba74;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 0;
            vertical-align: top;
        }

        th {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .amount {
            font-size: 28px;
            font-weight: 700;
            color: #059669;
            margin-top: 6px;
        }

        .small {
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="receipt-shell">
        <div class="row">
            <div class="left">
                <div class="eyebrow">{{ __('finance_ui.receipt') }}</div>
                <div class="brand">{{ $brandingName }}</div>
                <div class="muted">
                    {{ $transaction->branch?->address ?: $transaction->company?->address ?: config('app.name') }}
                </div>
            </div>
            <div class="right">
                <p class="title">{{ __('finance_ui.receipt_pdf') }}</p>
                <div class="amount">{{ $currency }} {{ number_format((float) $transaction->amount, 2) }}</div>
                <div class="muted small">{{ __('finance_ui.payment_collected_for') }}</div>
            </div>
        </div>

        <div class="meta-card">
            <div class="row">
                <div class="left">
                    <div><strong>{{ __('finance_ui.receipt_number') }}:</strong> {{ $transaction->receipt_number }}</div>
                    <div><strong>{{ __('finance_ui.transaction_number') }}:</strong> #{{ $transaction->id }}</div>
                    @if($transaction->cash_register_session_id)
                    <div><strong>{{ __('finance_ui.register_session_number') }}:</strong> #{{ $transaction->cash_register_session_id }}</div>
                    @endif
                </div>
                <div class="right">
                    <div><strong>{{ __('finance_ui.issued_at') }}:</strong> {{ $transaction->received_at?->format('Y-m-d h:i A') }}</div>
                    <div><strong>{{ __('finance_ui.payment_method') }}:</strong> {{ __('finance_ui.methods.' . $transaction->payment_method) }}</div>
                    <div><strong>{{ __('finance_ui.collected_by') }}:</strong> {{ $transaction->receivedBy?->full_name ?? __('finance_ui.system') }}</div>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ __('finance_ui.patient_name') }}</th>
                    <th>{{ __('finance_ui.service_plan') }}</th>
                    <th>{{ __('finance_ui.amount_paid_label') }}</th>
                    <th>{{ __('finance_ui.amount_received_label') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $transaction->patient?->full_name }}</td>
                    <td>{{ $transaction->treatmentPlan?->service?->name ?? $transaction->treatmentPlan?->name ?? __('finance_ui.plan_payment') }}</td>
                    <td>{{ $currency }} {{ number_format((float) $transaction->amount, 2) }}</td>
                    <td>{{ $currency }} {{ number_format((float) $transaction->amount_received, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals-card">
            <div class="row">
                <div class="left">
                    <div><strong>{{ __('finance_ui.change_returned') }}:</strong> {{ $currency }} {{ number_format((float) $transaction->change_returned, 2) }}</div>
                    @if($transaction->reference_number)
                    <div><strong>{{ __('finance_ui.reference_number') }}:</strong> {{ $transaction->reference_number }}</div>
                    @endif
                </div>
                <div class="right">
                    @if($transaction->notes)
                    <div><strong>{{ __('finance_ui.notes_label') }}:</strong> {{ $transaction->notes }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="notice-card">
            <strong>{{ __('finance_ui.no_refunds_badge') }}:</strong>
            {{ __('finance_ui.receipt_generated_for_payments_only') }}
        </div>
    </div>
</body>
</html>
