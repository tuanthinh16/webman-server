<?php


namespace app\helper;

class PaginationHelper
{
    public static function Pagination($data, $perPage, $page)
    {
        return [
            'data' => array_slice($data, ($page - 1) * $perPage, $perPage),
            'pagination' => [
                'total' => count($data),
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil(count($data) / $perPage),
            ]
        ];
    }
}
