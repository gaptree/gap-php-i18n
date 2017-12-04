<?php
namespace Gap\I18n\Translator;

use Redis;
use Gap\Database\Connection\Mysql as MysqlConnection;

class Translator
{
    protected $cnn;
    protected $cache;

    protected $table = 'trans';
    protected $defaultLocaleKey = 'zh-cn';

    public function __construct(MysqlConnection $cnn, Redis $cache)
    {
        $this->cnn = $cnn;
        $this->cache = $cache;
    }

    public function setDefaultLocaleKey($localeKey)
    {
        $this->defaultLocaleKey = $localeKey;
    }

    public function get($key, $vars = [], $localeKey = '')
    {
        if (!$localeKey) {
            $localeKey = $this->defaultLocaleKey;
        }

        if (!$vars) {
            return $this->lget($key, $localeKey);
        }

        if (is_string($vars)) {
            $vars = [$vars];
        }

        $index = 1;
        $args = [];
        $args[0] = '';

        foreach ($vars as $val) {
            $key .= "-%$index" . '$s';
            $args[$index] = $val;
            $index++;
        }
        $args[0] = $this->lget($key, $localeKey);

        return sprintf(...$args);
    }

    public function set($localeKey, $key, $value)
    {
        if (!$key) {
            // todo
            throw new \Exception("cannot translate empty str");
        }

        if (!$value) {
            // todo
            throw new \Exception("value could not be empty");
        }

        if (!$localeKey) {
            // todo
            throw new \Exception("localeKey cannot be empty");
        }

        $this->cache->hSet($localeKey, strtolower($key), $value);
        $this->saveToDb($localeKey, $key, $value);
    }

    public function delete($localeKey, $transKey)
    {
        $this->cache->hDel($localeKey, strtolower($transKey));
        $this->cnn->delete()
            ->from($this->table)
            ->where('key', '=', $transKey)
            ->andWhere('localeKey', '=', $localeKey)
            ->execute();
    }

    protected function findFromDb($localeKey, $key)
    {
        $obj = $this->cnn->select('value')
            ->from($this->table)
            ->where('localeKey', '=', $localeKey)
            ->andWhere('key', '=', $key)
            ->fetchObj();

        if (!$obj) {
            return null;
        }

        return $obj->value;
    }

    protected function saveToDb($localeKey, $key, $value)
    {
        if ($this->findFromDb($localeKey, $key)) {
            $this->cnn->update($this->table)
                ->where('localeKey', '=', $localeKey)
                ->andWhere('key', '=', $key)
                ->set('key', $key)
                ->set('value', $value)
                ->execute();

            return;
        }

        $this->cnn->insert($this->table)
            ->value('transId', $this->cnn->zid())
            ->value('localeKey', $localeKey)
            ->value('key', $key)
            ->value('value', $value)
            ->execute();
    }

    protected function lget($key, $localeKey)
    {
        if (!$key) {
            // todo
            throw new \Exception("cannot translate empty str");
        }

        if (!$localeKey) {
            // todo
            throw new \Exception("localeKey cannot be empty");
        }

        if ($value = $this->cache->hGet($localeKey, strtolower($key))) {
            return $value;
        }

        if ($value = $this->findFromDb($localeKey, $key)) {
            $this->cache->hSet($localeKey, strtolower($key), $value);
            return $value;
        }

        $value = ':' . $key;
        $this->set($localeKey, $key, $value);
        return $value;
    }
}
