企业级的php类库
--

## 配置注意事项

0. 因为功能经常更新和增强，使用的时候请注意版本信息。

1. 里面发送短信使用的aliyun的短信接口，其中composer.json里面引入了"alibabacloud/sdk": "^1.8"
   ，这个库还会引入其他的库，被引入的库guzzle如果是7.X版本，需要手动修改为6.3。因为7.X版本是php7的语法。
   （先删除掉guzzlehttp目录，然后把composer.json,composer.lock中涉及的guzzle从 6.3|7.0,改为6.3；最后在composer update）

   或者暂时先把这个功能去掉

```shell
"require": {
  "alibabacloud/sdk": "^1.8"
},
```

2. 进行单元测试时候，请按照文件 test/_README.md的内容进行简单配置。
3. 使用数据库访问的时候，请按照文件 Utils/Config/_README.md 的内容进行配置。
4. 涉及时间问题的时候，需要在 php.ini 内设置 date.timezone 为 Asia/Shanghai

## 开发注意事项

### 关于"判断"动作的命名

1. 判断某件事情的时候用单词 determine [dɪ'tɜːrmɪn]
2. 是非判断的时候，用is****

### 关于枚举的定义

1. 枚举在某个类型(class)内用 const 定义
2. 普通枚举都统一定义在类型 Utils/DataValue/SystemEnum.php 内,用格式"Xxx_YYY"定义，其中Xxx是这个变量的应用领域，YYY是具体的变量意义(比如
   RandCategory_NUMBER,其中RandCategory表示当前在定义一个随机数种类，NUMBER表示定义的是数字类型的随机数种类)；
3. 特别的枚举可以单独开一个文件定义,建议跟 SystemEnum.php 一样位于 Utils/DataValue/ 目录下

### 关于类库文件的命名规则
1. 如果仅提供静态复制方法的代码逻辑，那么通常建议类型命名为 ***Helper
2. 如果提供实例方法的代码逻辑,那么建议类型命名为 ***Mate
3. 通常 ***Mate类型不直接用 new() 构建:
   1. 用 Container.get(名称) 实现单例调用(得到的同一个实例可以在项目内复用)。比如(MateContainer.get).
   2. 包装在 ***Client 里面，调用静态方法。(比如 ConfigClient、DatabaseClient).