<?php
namespace Owl\DataMapper;

abstract class Mapper
{
    /**
     * Data class名.
     *
     * @var string
     */
    protected $class;

    /**
     * 配置，存储服务、存储集合、属性定义等等.
     *
     * @var array
     */
    protected $options = [];

    /**
     * 根据主键值返回查询到的单条记录.
     *
     * @param array $id 主键值
     * @param Owl\Service [$service] 存储服务连接
     * @param string [$collection] 存储集合名
     *
     * @return array 数据结果
     */
    abstract protected function doFind(array $id, \Owl\Service $service = null, $collection = null);

    /**
     * 插入数据到存储服务
     *
     * @param Data $data Data实例
     * @param Owl\Service [$service] 存储服务连接
     * @param string [$collection] 存储集合名
     *
     * @return array 新的主键值
     */
    abstract protected function doInsert(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null);

    /**
     * 更新数据到存储服务
     *
     * @param Data $data Data实例
     * @param Owl\Service [$service] 存储服务连接
     * @param string [$collection] 存储集合名
     *
     * @return bool
     */
    abstract protected function doUpdate(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null);

    /**
     * 从存储服务删除数据.
     *
     * @param Data $data Data实例
     * @param Owl\Service [$service] 存储服务连接
     * @param string [$collection] 存储集合名
     *
     * @return bool
     */
    abstract protected function doDelete(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null);

    /**
     * @param string $class
     */
    public function __construct($class)
    {
        $this->class = $class;
        $this->options = array_merge($this->normalizeOptions($class::getOptions()), $this->options);
    }

    protected function __beforeSave(\Owl\DataMapper\Data $data)
    {
    }

    protected function __afterSave(\Owl\DataMapper\Data $data)
    {
    }

    protected function __beforeInsert(\Owl\DataMapper\Data $data)
    {
    }

    protected function __afterInsert(\Owl\DataMapper\Data $data)
    {
    }

    protected function __beforeUpdate(\Owl\DataMapper\Data $data)
    {
    }

    protected function __afterUpdate(\Owl\DataMapper\Data $data)
    {
    }

    protected function __beforeDelete(\Owl\DataMapper\Data $data)
    {
    }

    protected function __afterDelete(\Owl\DataMapper\Data $data)
    {
    }

    final private function __before($event, \Owl\DataMapper\Data $data)
    {
        $event = ucfirst($event);
        call_user_func([$data, '__before' . $event]);
        call_user_func([$this, '__before' . $event], $data);
    }

    final private function __after($event, \Owl\DataMapper\Data $data)
    {
        $event = ucfirst($event);
        call_user_func([$data, '__after' . $event]);
        call_user_func([$this, '__after' . $event], $data);
    }

    /**
     * 指定的配置是否存在.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasOption($key)
    {
        return isset($this->options[$key]);
    }

    /**
     * 获取指定的配置内容.
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws \RuntimeException 指定的配置不存在
     */
    public function getOption($key)
    {
        if (!isset($this->options[$key])) {
            throw new \RuntimeException('Mapper: undefined option "' . $key . '"');
        }

        return $this->options[$key];
    }

    /**
     * 获取所有的配置内容.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * 获得存储服务连接实例.
     *
     * @return \Owl\Service
     *
     * @throws \RuntimeException Data class没有配置存储服务
     */
    public function getService()
    {
        $service = $this->getOption('service');

        return \Owl\Service\Container::getInstance()->get($service);
    }

    /**
     * 获得存储集合的名字
     * 对于数据库来说，就是表名.
     *
     * @return string
     *
     * @throws \RuntimeException 存储集合名未配置
     */
    public function getCollection()
    {
        return $this->getOption('collection');
    }

    /**
     * 获得主键定义.
     *
     * @return
     * [
     *     (string),    // 主键字段名
     *     ...
     * ]
     */
    public function getPrimaryKey()
    {
        return $this->getOption('primary_key');
    }

    /**
     * 获得指定属性的定义.
     *
     * @param string $key 属性名
     *
     * @return array|false
     */
    public function getAttribute($key)
    {
        return isset($this->options['attributes'][$key])
        ? $this->options['attributes'][$key]
        : false;
    }

    /**
     * 获得所有的属性定义
     * 默认忽略被标记为“废弃”的属性.
     *
     * @param bool $without_deprecated 不包含废弃属性
     *
     * @return [
     *           (string) => (array),  // 属性名 => 属性定义
     *           ...
     *           ]
     */
    public function getAttributes($without_deprecated = true)
    {
        $attributes = $this->getOption('attributes');

        if ($without_deprecated) {
            foreach ($attributes as $key => $attribute) {
                if ($attribute['deprecated']) {
                    unset($attributes[$key]);
                }
            }
        }

        return $attributes;
    }

    /**
     * 是否定义了指定的属性
     * 如果定义了属性，但被标记为“废弃”，也返回未定义.
     *
     * @param string $key 属性名
     *
     * @return bool
     */
    public function hasAttribute($key)
    {
        $attribute = $this->getAttribute($key);

        return $attribute ? !$attribute['deprecated'] : false;
    }

    /**
     * Mapper是否只读.
     *
     * @return bool
     */
    public function isReadonly()
    {
        return $this->getOption('readonly');
    }

    /**
     * 把存储服务内获取的数据，打包成Data实例.
     *
     * @param array $record
     * @param Data [$data]
     *
     * @return Data
     */
    public function pack(array $record, Data $data = null)
    {
        $types = Type::getInstance();
        $values = [];

        $attributes = $this->getAttributes();

        foreach ($record as $key => $value) {
            if (!isset($attributes[$key])) {
                continue;
            }

            $attribute = $attributes[$key];
            $values[$key] = $types->get($attribute['type'])->restore($value, $attribute);
        }

        if ($data) {
            $data->__pack($values, false);
        } else {
            $class = $this->class;
            $data = new $class(null, ['fresh' => false]);
            $data->__pack($values, true);
        }

        return $data;
    }

    /**
     * 把Data实例内的数据，转换为适用于存储的格式.
     *
     * @param Data $data
     * @param array [$options]
     *
     * @return array
     */
    public function unpack(Data $data, array $options = null)
    {
        $defaults = ['dirty' => false];
        $options = $options ? array_merge($defaults, $options) : $defaults;

        $attributes = $this->getAttributes();

        $record = [];
        foreach ($data->pick(array_keys($attributes)) as $key => $value) {
            if ($options['dirty'] && !$data->isDirty($key)) {
                continue;
            }

            if ($value !== null) {
                $attribute = $attributes[$key];
                $value = Type::factory($attribute['type'])->store($value, $attribute);
            }

            $record[$key] = $value;
        }

        return $record;
    }

    /**
     * 根据指定的主键值生成Data实例.
     *
     * @param string|int|array $id 主键值
     * @param Data [$data]
     *
     * @return Data|false
     */
    public function find($id, Data $data = null)
    {
        $id = $this->normalizeID($id);
        $registry = Registry::getInstance();

        if (!$data) {
            if ($data = $registry->get($this->class, $id)) {
                return $data;
            }
        }

        if (!$record = $this->doFind($id)) {
            return false;
        }

        $data = $this->pack($record, $data ?: null);
        $registry->set($data);

        return $data;
    }

    /**
     * 从存储服务内重新获取数据并刷新Data实例.
     *
     * @param Data $data
     *
     * @return Data
     */
    public function refresh(Data $data)
    {
        if ($data->isFresh()) {
            return $data;
        }

        return $this->find($data->id(true), $data);
    }

    /**
     * 保存Data.
     *
     * @param Data $data
     *
     * @return bool
     */
    public function save(Data $data)
    {
        if ($this->isReadonly()) {
            throw new \RuntimeException($this->class . ' is readonly');
        }

        $is_fresh = $data->isFresh();
        if (!$is_fresh && !$data->isDirty()) {
            return true;
        }

        $this->__before('save', $data);

        $result = $is_fresh ? $this->insert($data) : $this->update($data);
        if (!$result) {
            throw new \RuntimeException($this->class . ' save failed');
        }

        $this->__after('save', $data);

        return true;
    }

    /**
     * 删除Data.
     *
     * @param Data $data
     *
     * @return bool
     */
    public function destroy(Data $data)
    {
        if ($this->isReadonly()) {
            throw new \RuntimeException($this->class . ' is readonly');
        }

        if ($data->isFresh()) {
            return true;
        }

        $this->__before('delete', $data);

        if (!$this->doDelete($data)) {
            throw new \Exception($this->class . ' destroy failed');
        }

        $this->__after('delete', $data);

        Registry::getInstance()->remove($this->class, $data->id(true));

        return true;
    }

    /**
     * 把ID值格式化为数组形式.
     *
     * @param mixed $id
     *
     * @return array
     */
    public function normalizeID($id)
    {
        $primary_keys = $this->getPrimaryKey();

        if (!is_array($id)) {
            $key = $primary_keys[0];
            $id = [$key => $id];
        }

        $result = [];
        foreach ($primary_keys as $key) {
            if (!isset($id[$key])) {
                throw new Exception\UnexpectedPropertyValueException('Illegal id value');
            }

            $result[$key] = $id[$key];
        }

        return $result;
    }

    /**
     * 把新的Data数据插入到存储集合中.
     *
     * @param Data $data
     *
     * @return bool
     */
    protected function insert(Data $data)
    {
        $this->__before('insert', $data);
        $data->validate();

        if (!is_array($id = $this->doInsert($data))) {
            return false;
        }

        $this->pack($id, $data);
        $this->__after('insert', $data);

        return true;
    }

    /**
     * 更新Data数据到存储集合内.
     *
     * @param Data $data
     *
     * @return bool
     */
    protected function update(Data $data)
    {
        $this->__before('update', $data);
        $data->validate();

        if (!$this->doUpdate($data)) {
            return false;
        }

        $this->pack([], $data);
        $this->__after('update', $data);

        return true;
    }

    /**
     * 格式化从Data class获得的配置信息.
     *
     * @param array $options
     *
     * @return array
     */
    protected function normalizeOptions(array $options)
    {
        $options = array_merge([
            'service' => null,
            'collection' => null,
            'attributes' => [],
            'readonly' => false,
            'strict' => false,
        ], $options);

        $primary_key = [];
        foreach ($options['attributes'] as $key => $attribute) {
            $attribute = Type::normalizeAttribute($attribute);

            if ($attribute['strict'] === null) {
                $attribute['strict'] = $options['strict'];
            }

            if ($attribute['primary_key'] && !$attribute['deprecated']) {
                $primary_key[] = $key;
            }

            $options['attributes'][$key] = $attribute;
        }

        if (!$primary_key) {
            throw new \RuntimeException('Mapper: undefined primary key');
        }

        $options['primary_key'] = $primary_key;

        return $options;
    }

    /**
     * Mapper实例缓存数组.
     *
     * @var array
     */
    private static $instance = [];

    /**
     * 获得指定Data class的Mapper实例.
     *
     * @param string $class
     *
     * @return Mapper
     */
    final public static function factory($class)
    {
        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = new static($class);
        }

        return self::$instance[$class];
    }
}
