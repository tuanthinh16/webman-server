<?php

namespace support;

use Webman\Http\Response as BaseResponse;

class Response extends BaseResponse
{
    /**
     * Create a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return static
     */
    public static array $HEADERS_JSON = [
        'Content-Type' => 'application/json; charset=utf-8',
    ];

    // Plain text response
    public static array $HEADERS_TEXT = [
        'Content-Type' => 'text/plain; charset=utf-8',
    ];

    // HTML response
    public static array $HEADERS_HTML = [
        'Content-Type' => 'text/html; charset=utf-8',
    ];

    // XML response
    public static array $HEADERS_XML = [
        'Content-Type' => 'application/xml; charset=utf-8',
    ];

    // File download response (binary stream)
    public static array $HEADERS_DOWNLOAD = [
        'Content-Type' => 'application/octet-stream',
        // Content-Disposition có thể điều chỉnh filename khi cần
        'Content-Disposition' => 'attachment; filename="download.bin"',
    ];
}
