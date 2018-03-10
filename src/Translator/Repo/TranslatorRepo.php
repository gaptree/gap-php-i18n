<?php
namespace Gap\I18n\Translator\Repo;

use Gap\Db\MySql\Cnn;

class TranslatorRepo implements TranslatorRepoInterface
{
    protected $cnn;
    protected $table = 'gap_trans';

    public function __construct(Cnn $cnn)
    {
        $this->cnn = $cnn;
    }

    public function save(string $localeKey, string $transKey, string $transValue): void
    {
        if ($this->fetchTransValue($localeKey, $transKey)) {
            $this->cnn->update($this->table)
                ->set('value')->beStr($transValue)
                ->where()
                    ->expect('localeKey')->beStr($localeKey)
                    ->andExpect('key')->beStr($transKey)
                ->execute();

            return;
        }

        $this->cnn->insert($this->table)
            ->field('transId', 'localeKey', 'key', 'value')
            ->value()
                ->addStr($this->cnn->zid())
                ->addStr($localeKey)
                ->addStr($transKey)
                ->addStr($transValue)
            ->execute();
    }

    public function delete(string $localeKey, string $transKey): void
    {
        $this->cnn->delete()
            ->from($this->table)
            ->where()
                ->expect('localeKey')->beStr($localeKey)
                ->andExpect('key')->beStr($transKey)
            ->execute();
    }

    public function fetchTransValue(string $localeKey, string $transKey): string
    {
        $transArr = $this->cnn->select('value')
            ->from($this->table)
            ->where()
                ->expect('localeKey')->beStr($localeKey)
                ->andExpect('key')->beStr($transKey)
            ->fetchAssoc();

        if (!$transArr) {
            return '';
        }

        return $transArr['value'];
    }
}
