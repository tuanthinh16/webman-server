<?php

namespace app\controller;

use support\Request;
use support\Response;
use support\DB;
use support\Log;
use support\Redis;

class TransactionController
{
    /**
     * GET /api/transactions
     * Optional query params: id, start (offset), limit
     */
    public function index(Request $request)
    {
        try {
            $query = DB::table('transactions');

            if ($id = $request->input('id')) {
                $query->where('id', $id);
            }
            if (! is_null($start = $request->input('start'))) {
                $query->offset((int)$start);
            }
            if ($limit = $request->input('limit')) {
                $query->limit((int)$limit);
            }

            $data = $query->orderBy('create_time', 'desc')->get();
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['data' => $data], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('TransactionController@index error: ' . $e->getMessage());
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Server error'], JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /**
     * POST /api/transactions
     * body JSON: tx_type, currency, tx_hash, status, amount, fee, etc.
     */
    public function create(Request $request)
    {

        try {
            $userId = $request->user['id'] ?? null;
            if (! $userId) {
                Log::error('TransactionController@update unauthorized request');
                return new Response(
                    401,
                    ['Content-Type' => 'application/json'],
                    json_encode(['message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE)
                );
            }
            $data = $request->json();
            $required = ['tx_type', 'currency', 'status', 'amount', 'fee'];
            foreach ($required as $field) {
                if (! isset($data[$field])) {
                    Log::error("TransactionController@create missing field: $field");
                    return new Response(
                        400,
                        ['Content-Type' => 'application/json'],
                        json_encode(['message' => "Missing field: $field"], JSON_UNESCAPED_UNICODE)
                    );
                }
            }
            $preHash = DB::table('transactions')->orderBy('id', 'desc')->value('tx_hash');

            $now = date('Y-m-d H:i:s');
            $from = $data['from_address'] ?? '';
            $to   = $data['to_address']   ?? '';
            // Concatenate values for hashing: timestamp + userId + amount + from + to
            $hashInput = $now . '|' . $userId . '|' . $data['amount'] . '|' . $from . '|' . $to;
            $txHash = hash('sha256', $hashInput);

            $userId = $request->user['id'];
            $creator = DB::table('wa_users')->where('id', $userId)->value('username');

            $payload = [
                'user_id'      => $userId,
                'tx_type'      => $data['tx_type'],
                'currency'     => $data['currency'],
                'tx_hash'      => $txHash,
                'status'       => $data['status'],
                'amount'       => $data['amount'],
                'fee'          => $data['fee'],
                'pre-hash'     => $preHash,
                'from_address' => $from,
                'to_address'   => $to,
                'block_height' => $data['block_height'] ?? null,
                'memo'         => $data['memo'] ?? null,
                'create_time'  => $now,
                'creator'      => $creator,
            ];

            try {
                DB::table('transactions')->insert($payload);
                Redis::rPush('tx_queue', json_encode($payload));
                return new Response(
                    202,
                    ['Content-Type' => 'application/json'],
                    json_encode(['message' => 'Accepted'], JSON_UNESCAPED_UNICODE)
                );
            } catch (\Throwable $e) {
                Log::error('TransactionController@create enqueue error: ' . $e->getMessage());
                return new Response(
                    500,
                    ['Content-Type' => 'application/json'],
                    json_encode(['message' => 'Cannot enqueue transaction'], JSON_UNESCAPED_UNICODE)
                );
            }
        } catch (\Throwable $e) {
            Log::error('TransactionController@create error: ' . $e->getMessage());
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Cannot create transaction'], JSON_UNESCAPED_UNICODE)
            );
        }
        // return new Response(
        //     202,
        //     ['Content-Type' => 'application/json'],
        //     json_encode(['message' => 'Accepted'], JSON_UNESCAPED_UNICODE)
        // );
    }

    /**
     * PUT /api/transactions/{id}
     */
    public function update(Request $request, $id)
    {
        $userId = $request->user['id'] ?? null;
        if (! $userId) {
            Log::error('TransactionController@update unauthorized request');
            return new Response(
                401,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE)
            );
        }

        $data = $request->json();
        try {
            if (! DB::table('transactions')->where('id', $id)->exists()) {
                Log::error("TransactionController@update not found: $id");
                return new Response(
                    404,
                    ['Content-Type' => 'application/json'],
                    json_encode(['message' => 'Transaction not found'], JSON_UNESCAPED_UNICODE)
                );
            }

            $modifier = DB::table('wa_users')->where('id', $userId)->value('username');
            $data['modify_time'] = date('Y-m-d H:i:s');
            $data['modifier']    = $modifier;

            DB::table('transactions')->where('id', $id)->update($data);
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Updated'], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('TransactionController@update error: ' . $e->getMessage());
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Cannot update transaction'], JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /**
     * DELETE /api/transactions/{id}
     */
    public function delete(Request $request, $id)
    {
        try {
            $deleted = DB::table('transactions')->where('id', $id)->delete();
            if (! $deleted) {
                Log::error("TransactionController@delete not found: $id");
                return new Response(
                    404,
                    ['Content-Type' => 'application/json'],
                    json_encode(['message' => 'Transaction not found'], JSON_UNESCAPED_UNICODE)
                );
            }
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Deleted'], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('TransactionController@delete error: ' . $e->getMessage());
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Cannot delete transaction'], JSON_UNESCAPED_UNICODE)
            );
        }
    }
}
