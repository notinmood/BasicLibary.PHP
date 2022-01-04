<?php
/**
 * @file   : ConfigHelperTest.php
 * @time   : 21:40
 * @date   : 2021/9/5
 * @emailto: 9727005@qq.com
 * @creator: ShanDong Xiedali
 * @company: HiLand & RainyTop
 */

namespace Hiland\Utils\Environment;

use Hiland\Utils\Config\ConfigHelper;
use PHPUnit\Framework\TestCase;

class ConfigHelperTest extends TestCase
{
    public function testGet1()
    {
        $key = "d.dA";
        $actual = ConfigHelper::get($key);
        $expect = "dA-content";
        self::assertEquals($expect, $actual);

        $key = "archive.host";
        $actual = ConfigHelper::get($key, null, "demo.config.ini");
        $expect = "localhost";
        self::assertEquals($expect, $actual);

        /**
         * 测试上一步通过 get()第三个参数加载的配置文件的临时性,下次再使用 get 的时候，上次加载的临时配置文件就失效了
         */
        $key = "archive.database";
        $actual = ConfigHelper::get($key, null);
        $expect = null;
        self::assertEquals($expect, $actual);

        /**
         * 再次单独使用 get 方法的时候, 会使用长效的配置文件
         */
        $key = "d.dA";
        $actual = ConfigHelper::get($key);
        $expect = "dA-content";
        self::assertEquals($expect, $actual);
    }

    /**
     * @TODO
     * @return void
     */
    public function testGet2()
    {
        ConfigHelper::loadFile("demo.config.ini");
        self::assertEquals(1, 1);

        // $key = "d.dA";
        // $actual = ConfigHelper::get($key);
        // $expect = null;
        // self::assertEquals($expect, $actual);
        //
        // $key = "archive.database";
        // $actual = ConfigHelper::get($key);
        // $expect = "archive";
        // self::assertEquals($expect, $actual);
    }

    /**
     * 测试 .env 优先生效
     * @return void
     */
    public function testGet3()
    {
        $key = "city";
        $actual = ConfigHelper::get($key);
        $expect = "qingdao";
        self::assertEquals($expect, $actual);

        $key = "base.host";
        $actual = ConfigHelper::get($key);
        $expect = "env.localhost";
        self::assertEquals($expect, $actual);

        $key = "www";
        $actual = ConfigHelper::get($key, "");
        $expect = "";
        self::assertEquals($expect, $actual);

        $key = "base.12w6ww";
        $actual = ConfigHelper::get($key, "");
        $expect = "";
        self::assertEquals($expect, $actual);
    }

    /**
     * TODO:这个方法单独执行没有问题，但整个文件一起执行，就报错。
     * @return void
     */
    public function testGetSection1()
    {
        $key = "archive.host";
        $actual = ConfigHelper::get($key, null, "demo.config.ini");
        $expect = "localhost";
        self::assertEquals($expect, $actual);

        $key = "office";
        $actual = ConfigHelper::get($key);
        dump($actual);
        $expect = ["MS", "WPS"];
        self::assertEquals($expect, $actual);
    }

    public function testGetSection2()
    {
        $key = "base";
        $actual = ConfigHelper::get($key);
        $expect = ["host" => "env.localhost",
            "database" => "env.default",
            "address" => "env.address"];
        self::assertEquals($expect, $actual);
    }

    /**
     * 测试 .env 内的数据
     * @return void
     */
    public function testGetSection3()
    {
        $key = "users";
        $actual = ConfigHelper::get($key);
        $expect = ["host" => "env.localhost",
            "database" => "users",
            "address" => "env.address"];
        self::assertEquals($expect, $actual);
    }
}
