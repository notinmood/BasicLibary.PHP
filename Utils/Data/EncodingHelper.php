<?php
/**
 * Created by PhpStorm.
 * User: xiedali
 * Date: 2018/6/25
 * Time: 9:14
 */

namespace Hiland\Utils\Data;

/**
 * javascript有编码函数 escape()和对应的解码函数 unescape()，
 * 而 php中只有个 urlencode 和 urldecode，这个编码和解码函数对 encodeURI和 encodeURIComponent有效，
 * 但是对escape的是无效的。javascript中的 escape()函数和 unescape()函数用户字符串编码，
 * 类似于 PHP 中的 urlencode()函数，下面是 php 实现的 escape 函数代码：
 * Class EncodingHelper
 * @package Hiland\Utils\Data
 */
class EncodingHelper
{
    /**
     * js的 escape 功能之 php 实现
     * @param string $stringData the string want to be escaped
     * @param string $in_encoding
     * @param string $out_encoding
     * @return string
     */
    public static function escape($stringData, $in_encoding = 'UTF-8', $out_encoding = 'UCS-2')
    {
        $return = '';
        if (function_exists('mb_get_info')) {
            for ($x = 0; $x < mb_strlen($stringData, $in_encoding); $x++) {
                $str = mb_substr($stringData, $x, 1, $in_encoding);
                if (strlen($str) > 1) { // 多字节字符
                    $return .= '%u' . strtoupper(bin2hex(mb_convert_encoding($str, $out_encoding, $in_encoding)));
                } else {
                    $return .= '%' . strtoupper(bin2hex($str));
                }
            }
        }
        return $return;
    }

    /**
     * js的 unescape 功能之 php 实现
     * @param string $stringData
     * @return string
     */
    public static function unescape($stringData)
    {
        $ret = '';
        $len = strlen($stringData);
        for ($i = 0; $i < $len; $i++) {
            if ($stringData[$i] == '%' && $stringData[$i + 1] == 'u') {
                $val = hexdec(substr($stringData, $i + 2, 4));
                if ($val < 0x7f)
                    $ret .= chr($val);
                else
                    if ($val < 0x800)
                        $ret .= chr(0xc0 | ($val >> 6)) .
                            chr(0x80 | ($val & 0x3f));
                    else
                        $ret .= chr(0xe0 | ($val >> 12)) .
                            chr(0x80 | (($val >> 6) & 0x3f)) .
                            chr(0x80 | ($val & 0x3f));
                $i += 5;
            } else
                if ($stringData[$i] == '%') {
                    $ret .= urldecode(substr($stringData, $i, 3));
                    $i += 2;
                } else
                    $ret .= $stringData[$i];
        }
        return $ret;
    }
}