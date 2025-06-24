<?php

namespace app\controller\v1;

use app\model\Balance;
use app\model\Order;
use app\model\Wallet;
use support\Response;
use support\DB;
use support\Log;
use Webman\Http\Request;

class WalletController
{
    /**
     * Close an order and update the user’s balance.
     *
     * @param int   $userId
     * @param Order $order   Eloquent model with at least:
     *                       - price (open price)
     *                       - quantity (open quantity)
     *                       - leverage
     *                       - side    ('BUY' or 'SELL')
     *                       - close_price
     *                       - close_quantity
     * @return array{
     *   success: bool,
     *   pnl: float|null,
     *   released: float|null,
     *   new_balance: float|null,
     *   error?: string
     * }
     */
    public function updateOnOrderClose(int $userId, Order $order): array
    {
        try {
            DB::beginTransaction();

            // 1) Compute locked total (open_price * qty * leverage)
            $openValue   = $order->price * $order->quantity;
            $leverage    = $order->leverage ?? 1.0;
            $lockedTotal = $openValue * $leverage;

            // 2) Lock and fetch balance
            /** @var \app\model\Balance $balance */
            $balance = Wallet::where('user_id', $userId)
                ->where('currency', $order->currency ?? 'USDT')
                ->lockForUpdate()
                ->first();
            if (! $balance) {
                throw new \Exception("Balance not found");
            }

            // 3) Release locked funds
            $balance->locked_amount = max(0, $balance->locked_amount - $lockedTotal);

            // 4) Calculate PnL based on side
            $closePrice    = $order->close_price;
            $openPrice     = $order->price;
            $qty           = $order->close_quantity ?? $order->quantity;

            if (strtoupper($order->side) === 'BUY') {
                $pnl = ($closePrice - $openPrice) * $qty * $leverage;
            } else { // SELL
                $pnl = ($openPrice - $closePrice) * $qty * $leverage;
            }

            // 5) Update available amount
            $balance->amount += $pnl;
            $balance->updated_at = date('Y-m-d H:i:s');
            // 6) Persist
            $balance->save();
            DB::commit();

            Log::info("Balance updated on close", [
                'user_id'       => $userId,
                'order_id'      => $order->id,
                'released'      => $lockedTotal,
                'pnl'           => $pnl,
                'new_balance'   => $balance->amount,
            ]);

            return [
                'success'     => true,
                'pnl'         => $pnl,
                'released'    => $lockedTotal,
                'new_balance' => $balance->amount,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Balance update failed: " . $e->getMessage(), [
                'user_id'  => $userId,
                'order_id' => $order->id ?? null,
                'trace'    => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
    /**
     * Khóa tiền khi accept order
     */
    public function lockFundsForOrder($userId, $order): bool
    {
        DB::beginTransaction();

        try {
            $requiredAmount = $order->quantity * $order->price;
            $leverageFactor = $order->leverage ?? 1.0;
            $totalToLock = $requiredAmount * $leverageFactor;

            $balance = Wallet::where('user_id', $userId)
                ->where('currency', $order->currency ?? 'USDT')
                ->lockForUpdate()
                ->first();

            // Kiểm tra số dư khả dụng
            $available = $balance->amount - $balance->locked_amount;
            if ($available < $totalToLock) {
                throw new \Exception("Insufficient available balance");
            }

            // Khóa tiền
            $balance->increment('locked_amount', $totalToLock);
            $balance->save();

            DB::commit();

            Log::info("Funds locked for order", [
                'user_id' => $userId,
                'order_id' => $order->id,
                'amount_locked' => $totalToLock
            ]);

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to lock funds: " . $e->getMessage());
            return false;
        }
    }
    public function getAvailableBalance($userId, $currency = 'USDT'): float
    {
        $balance = Wallet::where('user_id', $userId)
            ->where('currency', $currency)
            ->first();

        return $balance ? ($balance->amount - $balance->locked_amount) : 0;
    }
    public function getBalance(Request $request)
    {
        try {
            // 1. Xác thực người dùng
            if (!isset($request->user['id'])) {
                return json([
                    'success' => false,
                    'message' => 'Unauthorized: Invalid or missing token'
                ], 401)->withHeader('WWW-Authenticate', 'Bearer');
            }

            $userId = $request->user['id'];
            $wallet = Wallet::where('user_id', $userId)->first();

            $data = null;
            $message = "";
            $status_code = 200;

            if ($wallet) {
                $data = $wallet;
            } else {
                $message = 'Not Found wallet';
                $status_code = 404;
            }
            // return new Response($status_code, ['Content-Type' => 'application/json'], json_encode(['data' => $data, 'message' => $message], JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to get balance: " . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e->getTraceAsString()
            ]);
            $status_code = 500;
            $data = null;
            $message = 'Failed to get balance. ' . $e->getMessage();
        }
        return new Response($status_code, ['Content-Type' => 'application/json'], json_encode(['data' => $data, 'message' => $message], JSON_UNESCAPED_UNICODE));
    }
}
