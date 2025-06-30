<?php


namespace app\repositories\order;


interface OrderInterface
{
    /**
     * List orders with pagination.
     *
     * @param int $userId
     * @param int $perPage
     * 
     */
    public function listOrders(int $userId, int $perPage = 15);
    /**
     * Find an order by its ID.
     *
     * @param int $orderId
     * @param int $userId
     */
    public function getByUserID(int $userId, array $params = [], int $perPage = 15);
    public function getLastHash();
    public function findByID(int $orderId, int $userId);
    /**
     * Create a new order.
     *
     * @param array $payload
     */
    public function create(array $payload);

    /**
     * Update an existing order.
     *
     * @param int $orderId
     * @param array $payload
     * @param int $userId
     */
    public function update(int $orderId, array $payload, int $userId);
}
