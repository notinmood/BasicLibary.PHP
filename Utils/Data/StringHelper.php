<?php

namespace Hiland\Utils\Data;

use Hiland\Utils\Web\EnvironmentHelper;

class StringHelper
{
    /**
     * @param $data
     * @param string $targetEncoding
     * @return false|string|string[]|null
     */
    public static function getEncodingContent($data, $targetEncoding = 'UTF-8')
    {
        $originalEncoding = self::getEncoding($data);

        $result = "";
        if ($originalEncoding) {
            $result = mb_convert_encoding($data, $targetEncoding, $originalEncoding);
        }

        return $result;
    }

    /**
     * 获取内容的编码
     * @param string $data
     * @return bool|string
     */
    public static function getEncoding($data = "")
    {
        return mb_detect_encoding($data);
    }

    /**
     * 获取文本文件的回车换行符
     *
     * @return string
     */
    public static function getNewLineSymbol()
    {
        if (EnvironmentHelper::getOS() == 'Windows') {
            return "\r\n";
        } else {
            return "\n";
        }
    }

    /**
     * 截取全角和半角（汉字和英文）混合的字符串以避免乱码
     *
     * @param string $originalString
     *            要截取的字符串
     * @param int $startPosition
     *            开始位置(第一个字符的位置为0)
     * @param int $length
     *            要截取的长度(超过总长度 按总长度计算)
     * @param string $charset
     * @return string
     * @author 小墨 244349067@qq.com
     */
    public static function subString($originalString, $startPosition, $length = 0, $charset = "utf-8")
    {
        $originalStringLength = strlen($originalString);

        if ($startPosition >= $originalStringLength) {
            return '';
        }

//        $content = '';
//        $sing = 0;
//        $count = 0;

        if ($length > $originalStringLength - $startPosition) {
            $length = $originalStringLength - $startPosition;
        }

        if ($length == 0) {
            $length = $originalStringLength - $startPosition;
        }

        if (function_exists("mb_substr")) {
            $slice = mb_substr($originalString, $startPosition, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($originalString, $startPosition, $length, $charset);
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $originalString, $match);
            $slice = join("", array_slice($match[0], $startPosition, $length));
        }

        return $slice;
    }

    /**
     * @param string $padding 待测试的结尾字符
     * @param string $wholeString 全句
     * @return bool
     */
    public static function isEndWith($wholeString, $padding)
    {
        $paddingLength = strlen($padding);
        $fullLength = strlen($wholeString);
        $subString = substr($wholeString, $fullLength - $paddingLength);
        if ($subString == $padding) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $padding 待测试的开始字符
     * @param string $wholeString 全句
     * @return bool
     */
    public static function isStartWith($wholeString, $padding)
    {
        $before = self::getStringBeforeSeperator($wholeString, $padding);
        if ($before == '') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取字符串分隔符前面的内容
     *
     * @param string $data
     * @param string $seperator
     * @return string
     */
    public static function getStringBeforeSeperator($data, $seperator)
    {
        if (self::isContains($data, $seperator)) {
            $array = explode($seperator, $data);
            return $array[0];
        } else {
            return $data;
        }
    }

    /**
     * 判断一个字符串是否被包含在另外一个字符串内
     *
     * @param string $subString
     *            被查找的子字符串
     * @param string $wholeString
     *            查找的母体字符串
     * @return boolean
     */
    public static function isContains($wholeString, $subString)
    {
        $result = strstr($wholeString, $subString);

        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**将一个字符串按照某个分隔符分隔成数组
     * @param $wholeString string 字符串全串
     * @param $delimiterString string 分隔符
     * @return false|string[]
     */
    public static function explode($wholeString, $delimiterString)
    {
        return explode($delimiterString, $wholeString);
    }

    /**
     * 将一个字符串按照字符个数分组进行格式化
     * @param string $data string
     * @param string $formator string 字符串字符个数分组的格式，同一个分组内字符的个数用{}包围，各个分组之间可以自定义分隔符，例如
     *  '{4}-{2}-{2}'，或者'{4} {2} {2}'(中间用空格表示);
     * @return string
     */
    public static function grouping($data, $formator)
    {
        $content = '';
        $pattern = '/\{\d*\}/';
        $matches = null;
        $result = preg_match_all($pattern, $formator, $matches);
        if ($result) {
            foreach ($matches[0] as $matchedWithQuotation) {
                $matchedWithQuotationStartPosition = strpos($formator, $matchedWithQuotation);
                $matchedWithQuotationLength = strlen($matchedWithQuotation);
                $seperator = substr($formator, 0, $matchedWithQuotationStartPosition);
                $content .= $seperator;
                $seperatorLength = strlen($seperator);
                $formator = substr($formator, $matchedWithQuotationLength + $seperatorLength);

                $matchedNumber = StringHelper::getStringAfterSeperator($matchedWithQuotation, '{');
                $matchedNumber = StringHelper::getStringBeforeSeperator($matchedNumber, '}');
                $matchedNumber = (int)$matchedNumber;
                $dataLength = strlen($data);
                if ($dataLength >= $matchedNumber) {
                    $content .= substr($data, 0, $matchedNumber);
                    $data = substr($data, $matchedNumber);
                } else {
                    $content .= $data;
                    $data = '';
                }
            }
        }
        return $content;
    }


    /** 对带有占位符的字符串信息，进行格式化填充，形成完整的字符串
     * @param $data string 带有占位符的字符串信息（占位符用{?}表示），例如 "i like this {?},do you known {?}"
     * @param $realValueList string[] 待填入的真实信息，用字符串数值表示，例如["qingdao","beijing"]
     * @return string
     */
    public static function format($data, $realValueList)
    {
        $needle = "{?}";
        // 查找?位置
        $p = strpos($data, $needle);
        // 替换字符的数组下标
        $i = 0;
        while ($p !== false) {
            // substr_replace ( mixed $string , mixed $replacement , mixed $start [, mixed $length ] ) : mixed
            $data = substr_replace($data, $realValueList[$i++], $p, 3);
            // 查找下一个?位置  没有时会退出循环
            $p = strpos($data, $needle, ++$p);
        }

        return $data;
    }

    /**
     * 获取字符串分隔符后面的内容
     *
     * @param string $data
     * @param string $seperator
     * @return string
     */
    public static function getStringAfterSeperator($data, $seperator)
    {
        if (self::isContains($data, $seperator)) {
            $array = explode($seperator, $data);
            return $array[1];
        } else {
            return $data;
        }
    }

    /**
     * 将字符串中第一个单词的首字母大写
     * @param $data
     * @return string
     */
    public static function upperStringFirstChar($data)
    {
        return ucfirst($data);
    }

    /**
     * 将字符串中每一个单词的首字母大写
     * @param $data
     * @return string
     */
    public static function upperWordsFirstChar($data)
    {
        return ucwords($data);
    }

    /**
     * 将字符串中每一个字母都转成大写
     * @param $data
     * @return string
     */
    public static function upper($data)
    {
        return mb_strtoupper($data);
    }

    /**
     * 将字符串中每一个字母都转成小写
     * @param $data
     * @return string
     */
    public static function lower($data)
    {
        return mb_strtolower($data);
    }


}