<?php

namespace Hiland\Utils\DataModel;

use Hiland\Utils\Data\ThinkHelper;
use ReflectionException;
use think\Config;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use Think\Model;

/**
 * 模型辅助器(需要ThinkPHP或者ThinkORM支持)
 * 封装模型与数据库交互的常用操作
 * ════════════════════════
 * ThinkPHP的Model中的initialize方法,请修改为protected(否则会启用反射,影响性能)
 * @author devel
 */
class ModelMate
{
    var $modelObject;
    var $queryObject;
    /**
     * @var string
     */
    private $tableRealName;

    /**
     * 构造函数
     * @param string|model $model
     *            其可以是一个表示model名称的字符串；
     *            也可以是一个继承至Think\Model的类型
     */
    public function __construct($model)
    {
        if (is_string($model)) {
            try {
                $this->modelObject = new CommonModel($model);
            } catch (ReflectionException $e) {
            }

            $className = "\\think\\facade\\Db";
            $exist = class_exists("$className");
            if ($exist) {
                $this->queryObject = \think\facade\Db::name($model);
            } else {
                $className = "\\think\\Db";
                $exist = class_exists("$className");
                if ($exist) {
                    $this->queryObject = \think\Db::name($model);
                }
            }
        } else {
            $this->modelObject = $model;
        }

        $this->tableRealName = $this->modelObject->getTable();
    }

    /**
     * 获取数据库真实的表名称(包含了表前缀)
     * @return string
     */
    public function getTableRealName()
    {
        return $this->tableRealName;
    }


    /**
     * 按照主键获取信息
     * @param int|string $key
     *            查询信息的主键值
     * @param string     $keyName
     *            查询信息的主键名称
     * @return array 模型实体数据
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function get($key, $keyName = 'id')
    {
        return self::getModel_Get($key, $keyName)->find();
    }

    /**
     * 获取get数据时候需要的model
     * @param        $key
     * @param string $keyName
     * @return Model
     */
    protected function getModel_Get($key, $keyName = 'id')
    {
        $condition[$keyName] = $key;
        return self::getModel_Where($condition);
    }

    /**
     * 获取加入where过滤条件的 model
     * @param array $condition
     * @return Model
     */
    protected function getModel_Where($condition = array())
    {
        return $this->queryObject->where($condition);
    }

    /**
     * 根据条件获取一条记录
     * @param array  $condition 过滤条件
     * @param string $orderBy
     * @return array 符合条件的结果，一维数组
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @example
     *                          $where= array();
     *                          $where['shopid'] = $merchantScanedID;
     *                          $where['openid'] = $openId;
     *                          $result = $buyerShopMate->find($where);
     */
    public function find($condition = array(), $orderBy = '')
    {
        $model = $this->getModel_Where($condition);
        return $model->order($orderBy)->find();
    }

    /**
     * 根据条件获取多条记录
     * @param array  $condition
     * @param string $orderBy          排序信息
     * @param int    $pageIndex        页面序号
     * @param int    $itemCountPerPage 每页显示的信息条目数
     * @param int    $limit            查询信息的条目数
     * @param string $fields           需要在查询结果中显示的字段信息，缺省情况下显示全部字段
     * @return array 符合条件的结果，多维数组
     * @example
     *                                 $where= array();
     *                                 $where['shopid'] = $merchantScanedID;
     *                                 $where['openid'] = $openId;
     *                                 $relation = $buyerShopMate->select($where);
     */
    public function select($condition = array(), $orderBy = "", $pageIndex = 0, $itemCountPerPage = 0, $limit = 0, $fields = '')
    {
        if (empty($orderBy)) {
            $orderBy = "id desc";
        }

        $model = $this->getModel_Select($condition, $orderBy, $pageIndex, $itemCountPerPage, $limit);

        if ($fields) {
            return $model->field($fields)->select();
        } else {
            return $model->select();
        }
    }

    /**
     * 根据条件获取Select需要的model
     * @param array  $condition
     * @param string $orderBy          排序信息
     * @param int    $pageIndex        页面序号
     * @param int    $itemCountPerPage 每页显示的信息条目数
     * @param int    $limit            查询信息的条目数
     * @return Model
     */
    protected function getModel_Select($condition = array(), $orderBy = "id desc", $pageIndex = 0, $itemCountPerPage = 0, $limit = 0)
    {
        $model = $this->getModel_Where($condition);

        if ($pageIndex && $itemCountPerPage) {
            $model = $model->page($pageIndex, $itemCountPerPage);
        }

        if ($limit) {
            $model = $model->limit($limit);
        }

        if ($orderBy) {
            $model = $model->order($orderBy);
        }

        return $model;
    }

    /**
     * 获取满足条件的记录数
     * @param array $condition
     * @return mixed
     */
    public function getCount($condition = array())
    {
        $model = $this->getModel_Where($condition);
        return $model->count();
    }

    /**
     * 删除数据
     * @param array $condition
     * @return mixed 失败返回false；成功返回删除数据的条数
     */
    public function delete($condition = array())
    {
        $model = $this->getModel_Where($condition);
        return $model->delete();
    }

    /**
     * 获取某记录的字段的值
     * @param int|string $key
     * @param string     $fieldName
     * @param string     $keyName
     * @return mixed 字段的值
     */
    public function getValue($key, $fieldName, $keyName = 'id')
    {
        $condition[$keyName] = $key;
        $model = $this->getModel_Where($condition);

        $thinkVersion = ThinkHelper::getPrimaryVersion();

        $model = $model->find();
        return $model[$fieldName];
    }

    /**
     * 设置某记录的字段的值
     * @param int|string $key
     * @param string     $fieldName
     * @param mixed      $fieldValue
     * @param string     $keyName
     * @return bool|int 成功时返回受影响的行数，失败时返回false
     */
    public function setValue($key, $fieldName, $fieldValue, $keyName = 'id')
    {
        $condition[$keyName] = $key;
        $model = $this->getModel_Where($condition);
        return $model->setField($fieldName, $fieldValue);
    }

    /**
     * 查找单个值
     * @param string      $searcher 要查找的内容
     * @param string|null $whereClause
     * @return null|mixed
     */
    public function queryValue($searcher, $whereClause = null)
    {
        $tableName = $this->queryObject->getTable();

        $sql = "SELECT $searcher FROM $tableName";
        if (!empty($whereClause)) {
            $sql .= ' where ' . $whereClause;
        }

        $dbSet = $this->directlyQuery($sql);

        if ($dbSet) {
            return $dbSet[0][$searcher];
        } else {
            return null;
        }
    }

    /**
     * 执行SQL语句，如果语句里面涉及到本模型对应的表名称，建议不要直接写。可以使用“关键字”  __MODELTABLENAME__,或者__MTN__,推荐使用 __TABLE__ ，本函数自动翻译为带前缀的表名称
     * @param $sql
     * @return mixed
     */
    public function directlyQuery($sql)
    {
        $tableName = $this->queryObject->getTable();

        if (strstr($sql, '__MODELTABLENAME__')) {
            $sql = str_replace('__MODELTABLENAME__', $tableName, $sql);
        }

        if (strstr($sql, '__MTN__')) {
            $sql = str_replace('__MTN__', $tableName, $sql);
        }

        return $this->queryObject->getConnection()->query($sql);
    }

    /**
     * 执行原始的sql语句
     * @param      $sql
     * @return false|int
     */
    public function directlyExecute($sql)
    {
        return $this->queryObject->getConnection()->execute($sql);
    }

    /**
     * 交互信息
     * @param array  $data
     *            待跟数据库交互的模型实体数据
     * @param string $keyName
     *            当前模型的数据库表的主键名称
     * @return boolean|number
     */
    public function interact($data = null, $keyName = 'id')
    {
        if (empty($data)) {
            /* 获取数据对象 */
            $data = $this->modelObject->create($_POST);
        }

        if (empty($data)) {
            return false;
        }

        $isAddOperation = true;

        /* 添加或新增基础内容 */
        if (empty($data[$keyName])) { // 新增数据
            $recordID = $this->queryObject->insert($data, false, true);
            $recordID = (int)$recordID;

            if (!$recordID) {
                //$this->model->setError('新增数据出错！');
                return false;
            }
        } else { // 更新数据
            $recordID = $data[$keyName];
            $isAddOperation = false;

            $status = $this->queryObject->update($data);

            if (false === $status) {
                //$this->model->setError('更新数据出错！');
                return false;
            }
        }

        // TODO:需要并研究添加hook机制
        // hook('documentSaveComplete', array('model_id'=>$data['model_id']));

        // 行为记录
        if ($recordID && $isAddOperation) {
            // action_log('add_role', 'role', $recordid, UID);
        }

        // 内容添加或更新完成
        return $recordID;
    }

    /**
     * 按照主键集合批量更新数据
     * @param        $keys    string 主键集合（用逗号分隔的主键字符串，例如“1,5,6”）
     * @param        $data    array 需要更新的数据（可以是目标实体的部分属性构成的array，比如本data内只包含status信息，这样就可以批量更新数据库内的记录状态）
     * @param string $keyName 主键的名称，缺省为“id”
     * @return bool
     */
    public function maintenanceData($keys = "", $data = null, $keyName = 'id')
    {
        if (empty($data)) {
            $data = I("get.");
        }

        if (empty($keys) && array_key_exists($keyName, $data)) {
            $keys = $data["$keyName"];
        }

        if (is_numeric($keys)) {
            $keys = "$keys";
        }

        if (array_key_exists($keyName, $data)) {
            unset($data["$keyName"]);
        }

        $condition = array("$keyName" => array("in", $keys));
        return $this->modelObject->where($condition)->data($data)->save();
    }

    /**
     * @param     $condition
     * @param     $field
     * @param int $step
     * @param int $lazyTime
     * @return bool
     */
    public function setInc($condition, $field, $step = 1, $lazyTime = 0)
    {
        return $this->modelObject->where($condition)->setInc($field, $step, $lazyTime);
    }

    /**
     * @param     $condition
     * @param     $field
     * @param int $step
     * @param int $lazyTime
     * @return bool
     */
    public function setDec($condition, $field, $step = 1, $lazyTime = 0)
    {
        return $this->modelObject->where($condition)->setDec($field, $step, $lazyTime);
    }
}
