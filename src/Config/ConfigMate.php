<?php
/**
 * @file   : ConfigMate.php
 * @time   : 16:40
 * @date   : 2021/8/11
 * @mail   : 9727005@qq.com
 * @creator: ShanDong Xiedali
 * @company: HiLand & RainyTop
 */

namespace Hiland\Config;

use Hiland\Data\ArrayHelper;
use Hiland\Data\ObjectHelper;
use Hiland\Data\StringHelper;
use Hiland\Environment\EnvHelper;
use Hiland\IO\FileHelper;
use Hiland\IO\PathHelper;


/**
 * 配置文件交互的核心类(不直接向外暴露；外部请使用ConfigHelper访问配置信息)
 */
class ConfigMate
{
    private static ?ConfigMate $_instance            = null;
    private static array       $__configContentArray = [];

    private function __construct()
    {
        // do nothing;
    }

    /**
     * @return ConfigMate|null
     */
    public static function Instance(): ?ConfigMate
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 获取具体的配置项
     * @param string $key
     * @param mixed  $default
     * @return array|mixed|null
     */
    public function get(string $key, $default = null)
    {
        /**
         * 使用自己的配置系统，不再使用 ThinkPHP 的配置系统了
         */
        if (ObjectHelper::isEmpty(self::$__configContentArray)) {
            self::loadFile();
        }

        foreach (self::$__configContentArray as $currentConfigContent) {
            $result = ArrayHelper::getNode($currentConfigContent, $key);
            if (!is_null($result)) {
                return $result;
            }
        }
        return $default;
    }

    private function loadFileDetail($fileFullName)
    {
        $thisFileLoaded = ArrayHelper::isContainsKey(self::$__configContentArray, $fileFullName);
        if (!$thisFileLoaded) {
            $_parser = self::getParser($fileFullName);

            $fileContent = null;
            if (file_exists($fileFullName)) {
                $fileContent = $_parser->loadFileToArray($fileFullName);
            }

            self::$__configContentArray[$fileFullName] = $fileContent;
        }
    }

    /**
     * 载入配置文件
     * (因为有可能本方法位于链式操作，因此需要返回 this)
     * @param string $fileName
     * @return $this
     */
    public function loadFile(string $fileName = ""): ConfigMate
    {
        $rootPath         = EnvHelper::getPhysicalRootPath();
        $defaultFileNames = ["config.php", "config.ini", "config.json"];

        $fileFullName = PathHelper::combine($rootPath, $fileName);
        if (!$fileName || !file_exists($fileFullName)) {
            foreach ($defaultFileNames as $defaultFileName) {
                $configFileFullName = PathHelper::combine($rootPath, $defaultFileName);
                if (file_exists($configFileFullName)) {
                    $fileFullName = $configFileFullName;
                    break;
                }
            }
        }

        self::loadFileDetail($fileFullName);
        return $this;
    }

    private static function getParser($fileName)
    {
        $extensionName = FileHelper::getExtensionName($fileName);
        $extensionName = StringHelper::upperStringFirstChar($extensionName);
        /** @noinspection all */
        $targetParserType   = "ConfigParser{$extensionName}";
        $targetParserClass  = "Hiland\\Config\\$targetParserType";
        $targetFileBaseName = "$targetParserType.php";
        $targetFileFullName = PathHelper::combine(__DIR__, $targetFileBaseName);
        if (file_exists($targetFileFullName)) {
            return new $targetParserClass();
        } else {
            return new ConfigParserArray();
        }
    }
}
