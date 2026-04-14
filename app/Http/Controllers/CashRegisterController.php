<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CashRegisterSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashRegisterController extends Controller
{
    public function open(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $branchId = $user->scopedBranchId() ?? $user->primary_branch_id;

        abort_unless($branchId, 403, 'A branch is required to open a cash register.');

        $validated = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $existingOpenSession = CashRegisterSession::query()
            ->forBranch($branchId)
            ->open()
            ->first();

        if ($existingOpenSession) {
            return back()->withErrors([
                'opening_balance' => __('finance_ui.register_already_open'),
            ]);
        }

        $session = CashRegisterSession::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'opening_balance' => round((float) $validated['opening_balance'], 2),
            'status' => CashRegisterSession::STATUS_OPEN,
            'opened_at' => now(),
            'opened_by' => $user->id,
            'notes' => $validated['notes'] ?? null,
            'expected_closing_balance' => round((float) $validated['opening_balance'], 2),
        ]);

        ActivityLog::record(
            'cash_register_opened',
            $session,
            "Cash register opened with balance {$session->opening_balance}.",
            [],
            [
                'opening_balance' => (float) $session->opening_balance,
                'branch_id' => $session->branch_id,
            ]
        );

        return redirect()->route('finance')->with('success', __('finance_ui.register_opened_success'));
    }

    public function close(Request $request, int $session): RedirectResponse
    {
        $user = Auth::user();
        $branchId = $user->scopedBranchId() ?? $user->primary_branch_id;

        abort_unless($branchId, 403, 'A branch is required to close a cash register.');

        $validated = $request->validate([
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $session = CashRegisterSession::query()
            ->forBranch($branchId)
            ->open()
            ->findOrFail($session);

        $session->refreshTotals();

        $closingBalance = round((float) $validated['closing_balance'], 2);
        $expectedClosingBalance = round((float) $session->expected_closing_balance, 2);

        $session->update([
            'closing_balance' => $closingBalance,
            'variance' => round($closingBalance - $expectedClosingBalance, 2),
            'closing_notes' => $validated['closing_notes'] ?? null,
            'closed_at' => now(),
            'closed_by' => $user->id,
            'status' => CashRegisterSession::STATUS_CLOSED,
        ]);

        ActivityLog::record(
            'cash_register_closed',
            $session,
            "Cash register closed at balance {$closingBalance}.",
            [],
            [
                'closing_balance' => (float) $session->closing_balance,
                'expected_closing_balance' => (float) $session->expected_closing_balance,
                'variance' => (float) $session->variance,
            ]
        );

        return redirect()->route('finance')->with('success', __('finance_ui.register_closed_success'));
    }
}
