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
        $cnn = $this->cnn;
        if ($this->fetchTransValue($localeKey, $transKey)) {
            $cnn->update($cnn->table($this->table))
                ->set('`value`', $cnn->str($transValue))
                ->where(
                    $cnn->cond()
                        ->expect('localeKey')->equal($cnn->str($localeKey))
                        ->andExpect('`key`')->equal($cnn->str($transKey))
                )
                ->execute();
            return;
        }
        $cnn->insert($this->table)
            ->field('transId', 'localeKey', '`key`', '`value`')
            ->value(
                $cnn->value()
                    ->add($cnn->str($cnn->zid()))
                    ->add($cnn->str($localeKey))
                    ->add($cnn->str($transKey))
                    ->add($cnn->str($transValue))
            )
            ->execute();
    }

    public function delete(string $localeKey, string $transKey): void
    {
        $cnn = $this->cnn;
        $this->cnn->delete()
            ->from($cnn->table($this->table))
            ->where(
                $cnn->cond()
                    ->expect('`key`')->equal($cnn->str($transKey))
                    ->andExpect('localeKey')->equal($cnn->str($localeKey))
            )
            ->execute();
    }

    public function fetchTransValue(string $localeKey, string $transKey): string
    {
        $cnn = $this->cnn;
        $transArr = $cnn->select('`value`')
            ->from($cnn->table($this->table))
            ->where(
                $cnn->cond()
                    ->expect('localeKey')->equal($cnn->str($localeKey))
                    ->andExpect('`key`')->equal($cnn->str($transKey))
            )
            ->limit(1)
            ->fetchAssoc();
        if (!$transArr) {
            return '';
        }
        return $transArr['`value`'];
    }
}
