<?php

namespace Shas\TransEditBundle\Entity;

class TranslationContent
{
    /** @var string $languagePrefix */
    private $languagePrefix = 'language-select.language.';

    /** @var array $allData */
    private $allData;

    /** @var array $languageData */
    private $languagesData = [];

    /** @var array $translationData */
    private $translationData = [];

    /** @var array $localeKeys */
    private $localeKeys = [];

    /**
     * @param array $allData
     */
    public function __construct($allData)
    {
        $this->allData = $allData;

        if (isset($this->allData['defaults'])) {
            $this->languagesData = $this->allData['defaults'];
        }

        if (isset($this->allData['locales'])) {
            $this->translationData = $this->allData['locales'];
            $this->localeKeys = array_keys($this->translationData);
        }
    }

    /**
     * @return array
     */
    public function getAllData()
    {
        return $this->allData;
    }

    /**
     * @return array
     */
    public function getLanguagesData()
    {
        return $this->languagesData;
    }

    /**
     * @return array
     */
    public function getTranslationData()
    {
        return $this->translationData;
    }

    /**
     * @return array
     */
    public function getLocaleKeys()
    {
        return $this->localeKeys;
    }

    /**
     * @return string
     */
    public function getLanguagePrefix()
    {
        return $this->languagePrefix;
    }

    /**
     * @param array $keyData
     * @return bool
     */
    public function addNewKeyData($keyData)
    {
        if (empty($keyData) || !is_array($keyData) || !isset($keyData['key'])) {
            return false;
        }

        $key = $keyData['key'];
        $localeKeys = $this->getLocaleKeys();

        foreach ($localeKeys as $locale) {
            if (!isset($keyData[$locale])) {
                continue;
            }
            $this->allData['locales'][$locale][$key] = $keyData[$locale];
            ksort($this->allData['locales'][$locale]);
        }

        return true;
    }

    /**
     * @param string $localeKey
     * @param string $localeName
     * @return bool
     */
    public function addLocaleKey($localeKey, $localeName)
    {
        if (in_array($localeKey, $this->localeKeys)) {
            return false;
        }

        $this->localeKeys[] = $localeKey;
        $this->languagesData[$this->languagePrefix . $localeKey] = $localeName;
        $this->translationData[$localeKey] = [];

        $this->makeNewAllData();

        return true;
    }

    /**
     * @param string $localeKey
     * @return bool
     */
    public function removeLocaleKey($localeKey)
    {
        if (count($this->localeKeys) <= 1 || !in_array($localeKey, $this->localeKeys)) {
            return false;
        }

        unset($this->translationData[$localeKey]);

        if(array_key_exists($this->languagePrefix . $localeKey, $this->languagesData)){
            unset($this->languagesData[$this->languagePrefix . $localeKey]);
        }

        $this->makeNewAllData();

        return true;
    }

    /**
     * @param string $localeKey
     * @param array $transKeys
     * @return bool
     */
    public function setEmptyTransDataToLocale($localeKey, $transKeys)
    {
        if (!in_array($localeKey, $this->localeKeys)) {
            return false;
        }

        foreach ($transKeys as $transKey) {
            $this->translationData[$localeKey][$transKey] = '';
        }

        $this->makeNewAllData();

        return true;
    }

    private function makeNewAllData()
    {
        $this->allData['defaults'] = $this->languagesData;
        $this->allData['locales'] = $this->translationData;
    }
}
