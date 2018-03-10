<?php
namespace Gap\I18n\Translator\Repo;

interface TranslatorRepoInterface
{
    public function save(string $localeKey, string $transKey, string $transValue): void;
    public function delete(string $localeKey, string $transKey): void;
    public function fetchTransValue(string $localeKey, string $transKey): string;
}
