<?php

namespace app\repositories\order;

use app\model\Order;
use support\DB;
use support\Log;

class OrderRepository implements OrderInterface
{
    protected $modelClass = Order::class;
    public function getByUserID(int $userId, array $params = [], int $perPage = 15)
    {
        try {
            $query = ($this->modelClass)::where('user_id', $userId);

            if (!empty($params['id'])) {
                $query->where('id', $params['id']);
            }
            if (!empty($params['order_by']) && in_array($params['order_by'], ['asc', 'desc'], true)) {
                $query->orderBy($params['order_field'] ?? 'id', $params['order_by']);
            }
            if (!empty($params['limit'])) {
                $query->offset(max(0, $params['start'] ?? 0))->limit($params['limit']);
            }
            $page = (int)request()->input('page', 1);
            return $query->paginate($perPage, ['*'], 'page', $page);
        } catch (\Throwable $e) {
            Log::error('OrderRepository@getByUserID error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function listOrders(int $userId, int $perPage = 15)
    {
        try {
            $query = ($this->modelClass)::where('user_id', $userId);
            $page = (int)request()->input('page', 1);
            $paginated = $query->paginate($perPage, ['*'], 'page', $page);
            return $paginated;
        } catch (\Throwable $e) {
            Log::error('OrderRepository@listOrders error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function findByID(int $orderId, int $userId)
    {
        return ($this->modelClass)::where('id', $orderId)->where('user_id', $userId)->first();
    }
    public function create(array $payload)
    {
        try {
            return ($this->modelClass)::insertGetId($payload);
        } catch (\Throwable $e) {
            Log::error('OrderRepository@create error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function update(int $orderId, array $payload, int $userId)
    {
        try {
            $order = $this->findByID($orderId, $userId);
            if (!$order) {
                throw new \Exception('Order not found');
            }
            $allowedFields = ['status', 'total_amount', 'closed_at', 'close_price', 'close_quantity', 'close_timestamp', 'price', 'quantity', 'timestamp', 'volume', 'leverage'];
            foreach ($payload as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $order->$key = $value;
                }
            }
            return $order->save();
        } catch (\Throwable $e) {
            Log::error('OrderRepository@update error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function getLastHash()
    {
        try {
            $lastOrder = ($this->modelClass)::orderBy('id', 'desc')->limit(1)->first();
            return $lastOrder ? $lastOrder->hash : null;
        } catch (\Throwable $e) {
            Log::error('OrderRepository@getLastHash error: ' . $e->getMessage());
            throw $e;
        }
    }
}
