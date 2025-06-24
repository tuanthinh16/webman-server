<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace support;

use Webman\Http\Request as BaseRequest;
use Webman\Route\Route as WebmanRoute;

class Request extends BaseRequest
{
    /**
     * Lấy và decode JSON từ body.
     *
     * @param bool $assoc true để trả về array, false trả về object
     * @return mixed
     * @throws \InvalidArgumentException nếu JSON không hợp lệ
     */
    public function json(bool $assoc = true)
    {
        $body = (string)$this->rawBody();
        if ('' === trim($body)) {
            return $assoc ? [] : null;
        }
        $data = json_decode($body, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload: ' . json_last_error_msg());
        }
        return $data;
    }

    /**
     * Lấy giá trị param đã định nghĩa trong route URI.
     * Ví dụ trong route '/order/{request_id}' bạn dùng $request->route('request_id').
     *
     * @param string     $name    Tên tham số
     * @param mixed|null $default Giá trị mặc định nếu không có
     * @return mixed
     */
    public function route(string $name, $default = null)
    {
        // Webman lưu params lên thuộc tính $this->attributes['_route_params']
        $params = $this->attributes['_route_params'] ?? [];
        return $params[$name] ?? $default;
    }
}
