<?php
namespace Gap\I18n\Translator;

use Redis;
use Gap\I18n\Translator\Repo\TranslatorRepoInterface;

class Translator
{
    protected $cache;
    protected $repo;

    protected $table = 'gap_trans';
    protected $localeKey = 'zh-cn';
    //protected $defaultLocaleKey = 'zh-cn';

    public function __construct(TranslatorRepoInterface $repo, Redis $cache)
    {
        $this->repo = $repo;
        $this->cache = $cache;
    }

    public function setLocaleKey(string $localeKey): void
    {
        $this->localeKey = $localeKey;
    }

    public function localeGet(string $localeKey, string $key, string ...$vars): string
    {
        $this->setLocaleKey($localeKey);
        return $this->get($key, ...$vars);
    }

    public function localeSet(string $localeKey, string $key, string $value): void
    {
        $this->setLocaleKey($localeKey);
        $this->set($key, $value);
    }

    public function get(string $key, string ...$vars): string
    {
        if (!$vars) {
            return $this->getTransValue($this->localeKey, $key);
        }

        //$key = $key . '-%$' . implode('$s-%$', array_keys($vars)) . '$s';
        $count = count($vars);
        for ($i = 1; $i <= $count; $i++) {
            $key .= '-%' . $i . '$s';
        }
        return sprintf($this->getTransValue($this->localeKey, $key), ...$vars);
        /* todo delete later
        $index = 1;
        $args = [];
        $args[0] = '';

        foreach ($vars as $val) {
            $key .= "-%$index" . '$s';
            $args[$index] = $val;
            $index++;
        }
        $args[0] = $this->lget($key, $this->localeKey);

        return sprintf(...$args);
        */
    }

    public function set(string $key, string $value): void
    {
        $this->setTrans($this->localeKey, $key, $value);
    }

    public function delete(string $localeKey, string $transKey): void
    {
        $this->cache->hDel($localeKey, strtolower($transKey));
        $this->repo->delete($localeKey, $transKey);
    }

    protected function findFromDb(string $localeKey, string $key): string
    {
        return $this->repo->fetchTransValue($localeKey, $key);
    }

    protected function saveToDb(string $localeKey, string $key, string $value): void
    {
        $this->repo->save($localeKey, $key, $value);
    }

    protected function getTransValue($localeKey, $key)
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
        $this->setTrans($localeKey, $key, $value);
        return $value;
    }

    protected function setTrans($localeKey, $key, $value)
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
}
