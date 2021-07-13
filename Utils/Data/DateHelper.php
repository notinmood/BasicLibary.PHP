<?php

namespace Hiland\Utils\Data;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

/** 2038年问题的核心是 timestamp最大支持2^31 - 1(即2,147,483,641)，超过这个值都会出现问题。
 * Class DateHelper
 * @package Hiland\Utils\Data
 */
class DateHelper
{
    /**获取2038年1月1日0时0分0秒的 时间戳
     * @return int
     */
    private static function get20380101Timestamp()
    {
        return 2145888000;
    }

    /**20380101的时间表示格式
     * @return DateTime
     * @throws Exception
     */
    private static function get20380101DateTime()
    {
        return new DateTime("2038-1-1 0:0:0", self::getDateTimeZone());
    }

    /**统一设置时区为PRC
     * @return DateTimeZone
     */
    private static function getDateTimeZone()
    {
        return new DateTimeZone("PRC");
    }


    /** 比较两个日期的大小
     * @param $dateMain
     * @param $dateSecondary
     * @return int 如果$dateMain大于$dateSecondary返回1；小于返回-1；等于返回0.
     */
    public static function compare($dateMain, $dateSecondary)
    {
        $dateMain = self::parseDateTimeSafely($dateMain);
        $dateSecondary = self::parseDateTimeSafely($dateSecondary);

        if ($dateMain == false || $dateSecondary == false) {
            return 0;
        } else {
            if ($dateMain == $dateSecondary) {
                return 0;
            }

            if ($dateMain > $dateSecondary) {
                return 1;
            } else {
                return -1;
            }
        }
    }

    /**从字符串解析出日期时间
     * @param $dateString
     * @return bool|\DateTime，成功时返回正确的日期时间格式；失败时返回false；
     */
    public static function parseDateTimeSafely($dateString)
    {
        $result = false;
        $type = ObjectHelper::getType($dateString);
        switch ($type) {
            case ObjectTypes::STRING:
                try {
                    $result = new DateTime($dateString, self::getDateTimeZone());
                } catch (Exception $e) {
                    $result = false;
                }
                break;
            case ObjectTypes::DATETIME:
                $result = $dateString;
                break;
            default:
                $result = false;
        }

        return $result;
    }

    /** 将timestamp转换成日期字符串(format函数的别名)
     * @param null $timestamp
     * @param string $format
     * @return string
     * @throws Exception
     */
    public static function getDateTimeString($timestamp = null, $format = "Y-m-d H:i:s")
    {
        return self::format($timestamp, $format);
    }

    /**将timestamp转换成日期
     * @param null $timestamp
     * @return DateTime
     * @throws Exception
     */
    public static function getDateTime($timestamp = null)
    {
        $timestamp = $timestamp === null ? time() : floatval($timestamp);

        if ($timestamp <= self::get20380101Timestamp()) {
            $targetArray = getdate($timestamp);

            $targetString = "{$targetArray['year']}-{$targetArray['mon']}-{$targetArray['mday']} {$targetArray['hours']}:{$targetArray['minutes']}:{$targetArray['seconds']}";
            return new DateTime($targetString, self::getDateTimeZone());
        } else {
            $ts2038 = self::get20380101Timestamp();
            $diffArray = self::getDiff($ts2038, $timestamp);

            $targetDateTime = self::addInterval($ts2038, 'd', $diffArray->d, 'dt');
            $targetDateTime = self::addInterval($targetDateTime, 'h', $diffArray->h, 'dt');
            $targetDateTime = self::addInterval($targetDateTime, 'm', $diffArray->i, 'dt');
            return self::addInterval($targetDateTime, 's', $diffArray->s, 'dt');
        }
    }

    /**获取两个日期之间的差值（秒或者毫秒）
     * @param $dateMain
     * @param $dateSecondary
     * @param string $intervalType “s”表示秒；“ms”表示毫秒
     * @return float|int
     * @throws Exception
     */
    public static function diffInterval($dateMain, $dateSecondary, $intervalType = "s")
    {
        $ms4Main = self::getTotalMilliSeconds($dateMain);
        $ms4Secondary = self::getTotalMilliSeconds($dateSecondary);

        $ms4Diff = $ms4Main - $ms4Secondary;

        $result = null;
        switch ($intervalType) {
            case "ms":
                $result = $ms4Diff;
                break;
            default :
                $result = $ms4Diff / 1000;
        }

        return $result;
    }


    /**
     * @param $startValue DateTime|int|float 开始时间(即可以是DateTime类型也可以是timestamp类型)
     * @param $endValue DateTime|int|float 结束时间(即可以是DateTime类型也可以是timestamp类型)
     * @return DateInterval
     * @throws Exception
     */
    public static function getDiff($startValue, $endValue)
    {
        if (ObjectHelper::getType($startValue) == ObjectTypes::DATETIME) {
            $startValue = self::getTimestamp($startValue);
        }

        if (ObjectHelper::getType($endValue) == ObjectTypes::DATETIME) {
            $endValue = self::getTimestamp($endValue);
        }

        $timeDiff = $endValue - $startValue;
        $days = intval($timeDiff / 86400);
        $remain = $timeDiff % 86400;
        $hours = intval($remain / 3600);
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        $secs = $remain % 60;

        $p = "P{$days}DT{$hours}H{$mins}M{$secs}S";
        return new DateInterval($p);
    }

    /**
     * 获取从1970年1月1日以来总共的毫秒数
     *
     * @param null $dateValue
     * @return float
     * @throws Exception
     */
    public static function getTotalMilliSeconds($dateValue = null)
    {
        return self::getTimestamp($dateValue) * 1000;
    }

    /**
     * 获取一个指定时间点的timestamp(即从1970年1月1日以来总共的秒数)
     * @param string $dateValue 指定的时间点 ，可以是“201603161312”格式，也可以是“2016-03-16 13:12:25”
     * @return int
     * @throws Exception
     */
    public static function getTimestamp($dateValue = null)
    {
        if (ObjectHelper::isEmpty($dateValue)) {
            $dateValue = new DateTime();
        }

        if (ObjectHelper::getType($dateValue) == ObjectTypes::STRING) {
            $dateValue = new DateTime($dateValue);
        }
        $dateValue->setTimezone(self::getDateTimeZone());

        //以下代码修复php中2038年问题（32位php的int无法表示2038年01月19日星期二凌晨03:14:07之后的时间秒数。
        //超过 2^31 – 1，2^31 – 1 就是0x7FFFFFFF）
        $year20380101 = new DateTime("2038-1-1 0:0:0", self::getDateTimeZone());
        if ($dateValue <= $year20380101) {
            $result = $dateValue->getTimestamp();
        } else {
            $dateDiff = $dateValue->diff($year20380101);
            $days = floatval($dateDiff->days);
            $totalSeconds = floatval($days * 86400)
                + $dateDiff->h * 3600 + $dateDiff->i * 60 + $dateDiff->s;

            $year20380101Seconds = $year20380101->getTimestamp();

            $result = $year20380101Seconds + $totalSeconds;
        }

        return $result;
    }

    /**
     * 获取当前时间的毫秒数信息
     *
     * @return float
     */
    public static function getCurrentMilliSecond()
    {
        return microtime(true) * 1000;
    }

    /**
     * 对日期进行时间间隔处理
     *
     * @param int|DateTime $originalValue int类型的时间戳或者是DateTime时间
     * @param string $intervalType
     *            时间间隔类型，具体如下：
     *            y:年
     *            M:月
     *            d:日
     *
     *            q:季度
     *            w:星期
     *
     *            h:小时
     *            m或者i:分钟
     *            s:秒钟
     *
     * @param int $intervalValue 时间间隔值
     * @param string $returnType 返回值类型--dt:返回DateTime时间类型；ts(默认):返回timestamp类型。
     * @return int|DateTime int类型的时间戳或者是DateTime时间
     * @throws Exception
     */
    public static function addInterval($originalValue = null, $intervalType = "d", $intervalValue = 1, $returnType = "ts")
    {
        if (ObjectHelper::getType($originalValue) == ObjectTypes::DATETIME) {
            $source = $originalValue;
        } else {
            if ($originalValue) {
                $originalValue = "@" . $originalValue;
            }

            $source = new DateTime($originalValue);
            $source->setTimezone(self::getDateTimeZone());
        }


        $y = $M = $d = $h = $i = $s = 0;
        $invert = 0;
        if ($intervalValue < 0) {
            $invert = 1;
        }
        $intervalValue = abs($intervalValue);

        switch ($intervalType) {
            case "y":
            case "Y":
                $y = $intervalValue;
                break;
            case "q":
            case "Q":
                $M = $intervalValue * 3;
                break;
            case "M":
                $M = $intervalValue;
                break;
            case "d":
            case "D":
                $d = $intervalValue;
                break;
            case "w":
            case "W":
                $d = $intervalValue * 7;
                break;
            case "h":
            case "H":
                $h = $intervalValue;
                break;
            case "m":
            case "i":
            case "I":
                $i = $intervalValue;
                break;
            case "s":
            case "S":
                $s = $intervalValue;
                break;
        }

        $formatString = "P{$y}Y{$M}M{$d}DT{$h}H{$i}M{$s}S";

        $interval = new DateInterval($formatString);
        $interval->invert = $invert;

        $target = $source->add($interval);

        if ($returnType == "timestamp" || $returnType == "ts") {
            return self::getTimestamp($target);
        } else {
            return $target;
        }
    }

    /**
     * 对数字表示的timestamp进行格式化友好显示
     * @param int $time timestamp格式的时间
     * @param string $formatString 格式化字符串
     * @return string
     * @throws Exception
     */
    public static function format($time = null, $formatString = 'Y-m-d H:i:s')
    {
        $time = $time === null ? time() : floatval($time);

        $targetDateTime = self::getDateTime($time);
        return $targetDateTime->format($formatString);
    }

    /**
     * 获取某个制定的日期是星期几
     * @param $timestamp 指定的日期（默认为当前日期）
     * @param string $format 返回星期几的格式
     * （默认（或者number,N,n）为数组0-7；
     * chinese,C,c:汉字 一，。。。日；
     * chinesefull,CF,cf:汉字全称 星期一。。。星期天）
     * @return string
     */
    public static function getWeekName($format = 'number', $timestamp = null)
    {
        if ($format == 'number' || $format == 'N' || $format == 'n') {
            $format = 'n';
        }

        if ($format == 'chinese' || $format == 'C' || $format == 'c') {
            $format = 'c';
        }

        if ($format == 'chinesefull' || $format == 'CF' || $format == 'cf') {
            $format = 'cf';
        }

        $week = date("w", $timestamp);

        $result = '';
        switch ($week) {
            case 1:
                $result = "一";
                break;
            case 2:
                $result = "二";
                break;
            case 3:
                $result = "三";
                break;
            case 4:
                $result = "四";
                break;
            case 5:
                $result = "五";
                break;
            case 6:
                $result = "六";
                break;
            case 0:
                $result = "日";
                break;
        }

        switch ($format) {
            case "n":
                return $week;
                break;
            case 'c':
                return $result;
                break;
            case 'cf':
                return '星期' . $result;
                break;
        }
    }

    /**
     * 获取日期中月份的中文叫法
     * @param null|int $month
     * @param string $postfixes 后缀信息
     * @return string
     */
    public static function getMonthChineseName($month = null, $postfixes = '')
    {
        if ($month == null) {
            $month = date("n");
        }

        switch ($month) {
            case 1:
                $result = '一';
                break;
            case 2:
                $result = '二';
                break;
            case 3:
                $result = '三';
                break;
            case 4:
                $result = '四';
                break;
            case 5:
                $result = '五';
                break;
            case 6:
                $result = '六';
                break;
            case 7:
                $result = '七';
                break;
            case 8:
                $result = '八';
                break;
            case 9:
                $result = '九';
                break;
            case 10:
                $result = '十';
                break;
            case 11:
                $result = '十一';
                break;
            default:
                $result = '十二';
                break;
        }

        return $result . $postfixes;
    }
}
