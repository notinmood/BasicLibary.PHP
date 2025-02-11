<?php

namespace Hiland\Web;

/**
 *
 */
class HttpRequestHeader
{
    public static function getUserAgent()
    {
        return self::get('USER-AGENT');
    }

    public static function get($key)
    {
        $key     = strtoupper($key);
        $headers = self::getAll();
        foreach ($headers as $k => $v) {
            if ($k == $key) {
                return $v;
            }
        }

        return false;
    }

    public static function getAll(): array
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            $keyPrefix = strtoupper(substr($key, 0, 5));
            if ('HTTP_' == $keyPrefix) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
        }

        if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            $headers['AUTHORIZATION'] = $_SERVER['PHP_AUTH_DIGEST'];
        } elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $headers['AUTHORIZATION'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH'];
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
        }

        return $headers;
    }

    /*
     * public static function set($key,$value){
     * $key= strtoupper($key);
     *
     * }
     */

    /**
     * 页面正常状态 200 ok
     */
    public static function setPage202(): void
    {
        self::set('HTTP/1.1 200 OK');
    }

    /**
     * 设置http头信息
     * @param mixed $value
     */
    public static function set($value): void
    {
        header($value);
    }

    /**
     * 设置一个404头: “404 文件找不到错误”
     */
    public static function setPage404(): void
    {
        self::set('HTTP/1.1 404 Not Found');
    }

    /**
     * 设置地址被永久的重定向 301重定向
     */
    public static function setPage301(): void
    {
        self::set('HTTP/1.1 301 Moved Permanently');
    }

    /**
     * 告诉浏览器文档内容没有发生改变
     */
    public static function setPage304(): void
    {
        self::set('HTTP/1.1 304 Not Modified');
    }

    /**
     * 转到一个新地址
     * @param string $url 目标地址
     */
    public static function redirectUrl(string $url): void
    {
        self::set("Location: $url");
    }

    /**
     * 延迟转向
     * @param string $url     目标地址
     * @param int $seconds 延迟时间
     */
    public static function redirectUrlDelay(string $url, int $seconds = 10): void
    {
        self::set("Refresh: $seconds; url=$url");
    }

    /**
     * 对当前文档禁用缓存
     */
    public static function setNoCache(): void
    {
        self::set("Cache-Control: no-cache, no-store, max-age=0, must-revalidate");
        self::set("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// Date in the past
        self::set("Pragma: no-cache");
    }
}
