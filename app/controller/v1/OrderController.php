<?php

namespace app\controller\v1;

use app\middleware\AuthMiddleware;
use app\model\Order;
use app\repositories\order\OrderInterface;
use Exception;
use support\Request;
use support\Response;
use support\DB;
use support\Log;

class OrderController
{
    protected OrderInterface $oderInterface;

    public function __construct(OrderInterface $oderInterface)
    {
        $this->oderInterface = $oderInterface;
    }

    /**
     * Get all orders for the authenticated user.
     * Method: GET /api/orders
     */
    public function getByUserID(Request $request)
    {

        $userId = $request->user['id'];
        $params = [
            'start' => (int)$request->input('start', 0),
            'limit' => (int)$request->input('limit', 0),
            'id' => $request->input('id', null),
            'order_field' => $request->input('order_field', 'id'),
            'order_by' => $request->input('order_by', null),
            'per_page' => (int)$request->input('per_page', 15),
            'page' => (int)$request->input('page', 1),
        ];
        $orders = $this->oderInterface->getByUserID($userId, $params, $params['per_page']);
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'success' => true,
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ]
        ], JSON_UNESCAPED_UNICODE));
    }


    /**
     * Place a new order.
     * Method: POST /api/orders/place
     */
    public function place(Request $request)
    {
        // $userId = $request->attributes['user_id'] ?? null;
        try {

            $userId = $request->user['id'];
            if (! $userId) {
                return json([
                    'success' => false,
                    'error'   => 'Unauthorized: missing user_id'
                ], 401);
            }

            $payload = $request->json(true);
            // basic validation
            foreach (['id', 'params'] as $f) {
                if (! isset($payload[$f])) {
                    return json([
                        'success' => false,
                        'error'   => "Missing field: {$f}"
                    ], 400);
                }
            }
            $p = $payload['params'];
            foreach (['symbol', 'side', 'type', 'timeInForce', 'price', 'quantity', 'timestamp', 'signature'] as $f) {
                if (! isset($p[$f])) {
                    return json([
                        'success' => false,
                        'error'   => "Missing params.{$f}"
                    ], 400);
                }
            }

            // Lấy pre_hash từ order cuối cùng
            // $last = DB::table('orders')
            //     ->orderBy('id', 'desc')
            //     ->limit(1)
            //     ->first(['hash']);
            $last = $this->oderInterface->getLastHash();
            $preHash = $last->hash ?? str_repeat('0', 64);

            // Tính hash dựa trên pre_hash + timestamp + price + user_id + quantity
            $dataToHash = $preHash
                . $p['timestamp']
                . $p['price']
                . $userId
                . $p['quantity'];
            $hash = hash('sha256', $dataToHash);
            $payload = [
                'request_id'    => $payload['id'],
                'user_id'       => $userId,
                'symbol'        => $p['symbol'],
                'side'          => $p['side'],
                'type'          => $p['type'],
                'time_in_force' => $p['timeInForce'],
                'price'         => $p['price'],
                'quantity'      => $p['quantity'],
                'volume'        => $p['volume'],
                'leverage'      => $p['leverage'],
                'timestamp'     => $p['timestamp'],
                'pre_hash'      => $preHash,
                'hash'          => $hash,
                'status'        => 'pending',
                'response'      => null,
                'created_at'    => date('Y-m-d H:i:s'),
            ];
            // Insert
            try {
                $order_id = $this->oderInterface->create($payload);
            } catch (\Throwable $e) {
                Log::error('OrderController@place error: ' . $e->getMessage(), [
                    'user_id' => $userId,
                    'payload' => $payload,
                ]);
                return Response::ServerError('Failed to create order');
            }

            return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success'     => true,
                'order_db_id' => $order_id,
                'pre_hash'    => $preHash,
                'hash'        => $hash,
            ], JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            Log::error('OrderController@place error: ' . $e->getMessage(), [
                'user_id' => $userId ?? null,
                'payload' => $payload ?? null,
            ]);
            return Response::ServerError('Failed to place order');
        }
    }
    /**
     * accept an order.
     * Method: POST /api/orders/accept?order_id=
     */
    public function accept(Request $request): Response
    {
        DB::beginTransaction();

        try {

            $userId = $request->user['id'];
            $orderId = $request->get('order_id');

            // 1. Kiểm tra order
            $order = Order::where('id', $orderId)
                ->where('status', 'pending')
                ->firstOrFail();

            // 2. Khóa tiền
            $balanceController = new WalletController();
            if (!$balanceController->lockFundsForOrder($userId, $order)) {
                throw new \Exception("Failed to lock funds");
            }

            // 3. Cập nhật order
            $order->update([
                'status' => 'open',
                'accepted_at' => date('Y-m-d H:i:s')
            ]);

            DB::commit();

            return new Response(200, [], json_encode(['success' => true], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            DB::rollBack();
            return new Response(500, [], json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
        }
    }
    /**
     * Close an order.
     * Method: POST /api/orders/close?order_id=
     */
    public function close(Request $request)
    {
        try {
            $userId = $request->user['id'];

            // 2) Validate input
            $data = $request->json(true);
            foreach (['order_id', 'close_price', 'close_quantity', 'close_timestamp'] as $f) {
                if (empty($data[$f])) {
                    return json([
                        'success' => false,
                        'error' => "Missing parameter: {$f}"
                    ], 400);
                }
            }
            $orderId       = $data['order_id'];
            $closePrice    = $data['close_price'];
            $closeQuantity = $data['close_quantity'];
            $closeTs       = $data['close_timestamp'];

            // 3) Load order and check ownership + status
            // $order = Order::where('id', $orderId)
            //     ->where('user_id', $userId)
            //     ->where('status', 'open')
            //     ->first();
            $order = $this->oderInterface->findByID($orderId, $userId)->where('status', 'open')->first();
            if (! $order) {
                return json([
                    'success' => false,
                    'error' => 'Order not found or not open'
                ], 404);
            }
            if ($closeQuantity > $order->quantity) {
                return json(['error' => 'Invalid quantity'], 406);
            }
            $order->close_price    = $closePrice;
            $order->close_quantity = $closeQuantity;
            $order->close_timestamp       = $closeTs;

            // 5) Perform balance update & close
            DB::beginTransaction();
            $result = (new WalletController())->updateOnOrderClose($userId, $order);

            if (! $result['success']) {
                throw new \Exception("Balance update failed: " . ($result['error'] ?? ''));
            }

            // you now have:
            $pnl         = $result['pnl'];
            $released    = $result['released'];
            $newBalance  = $result['new_balance'];
            $payload_update_order = [
                'status' => 'closed',
                'closed_at' => date('Y-m-d H:i:s', intval($closeTs / 1000)),
            ];
            // mark order closed
            $order->status     = 'closed';
            $order->closed_at  = date('Y-m-d H:i:s', intval($closeTs / 1000));
            $order->save();

            DB::commit();

            return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'data' => [
                    'order_id' => $orderId,
                    'new_status' => 'closed',
                    'pnl' => $pnl,
                    'released' => $released,
                    'newBalance' => $newBalance
                ]
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('OrderController@close error: ' . $e->getMessage(), [
                'user_id' => $userId ?? null,
                'order_id' => $orderId ?? null,
            ]);
            return new Response(500, [], json_encode([
                'success' => false,
                'error' => 'Failed to close order'
            ], JSON_UNESCAPED_UNICODE));
        }
    }


    /**
     * Cancel an existing order (set status to 'cancelled')
     * Method: POST /api/orders/cancel?order_id=}
     */
    public function cancel(Request $request)
    {
        try {


            $userId = $request->user['id'];

            $order_id = $request->get('order_id');

            // 2. Kiểm tra order tồn tại và thuộc về user
            $order = DB::table('orders')
                ->where('id', $order_id)
                ->where('user_id', $userId)
                ->first();

            if (!$order) {
                return json([
                    'success' => false,
                    'error' => "Order {$order_id} not found"
                ], 404);
            }

            // 3. Chỉ hủy order ở trạng thái 'pending' hoặc 'open'
            if (!in_array($order->status, ['pending', 'open'])) {
                return json([
                    'success' => false,
                    'error' => "Cannot cancel order with status: {$order->status}"
                ], 400);
            }

            // 4. Cập nhật trạng thái (sử dụng transaction để đảm bảo toàn vẹn dữ liệu)
            DB::beginTransaction();

            $updated = DB::table('orders')
                ->where('id', $order_id)
                ->where('user_id', $userId)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            DB::commit();

            // 5. Ghi log và trả về kết quả
            Log::info("Order cancelled", [
                'id' => $order_id,
                'user_id' => $userId,
                'previous_status' => $order->status
            ]);

            return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'data' => [
                    'cancelled_order_id' => $order_id,
                    'new_status' => 'cancelled'
                ]
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to cancel order {$order_id}: " . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e->getTraceAsString()
            ]);

            return new Response(500, [], json_encode([
                'success' => false,
                'error' => 'Failed to cancel order',
                'system_message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
        }
    }
}
