<?php

namespace Shas\TransEditBundle\Services;

use Shas\TransEditBundle\Entity\EntityManager\TranslationContentManager;
use Shas\TransEditBundle\Entity\TranslationContent;

/**
 * Class TranslationContentProcessing
 * @package Shas\TransEditBundle\Services
 */
class TranslationContentProcessing
{
    /** @var  TranslationContentManager $translationContentManager */
    private $translationContentManager;

    /** @var  TranslationContent $translationContent */
    private $translationContent;

    /** @var integer */
    private $minDeep = 2;

    /** @var integer */
    private $maxDeep = 3;

    /**
     * @param TranslationContentManager $translationContentManager
     */
    public function __construct(TranslationContentManager $translationContentManager)
    {
        $this->translationContentManager = $translationContentManager;
        $this->translationContent = $translationContentManager->getTranslationContentEntity();
    }

    /**
     * @return integer
     */
    public function getTranslationContentStatus()
    {
        if (empty($this->translationContent)) {
            return 1;
        }

        return 0;
    }

    /**
     * @return array
     */
    public function getStatistic()
    {
        return [
            'languagesCount'     => count($this->translationContent->getLanguagesData()),
            'languagesInString'  => implode(', ', array_values($this->translationContent->getLanguagesData())),
            'keysCountByLocales' => $this->getKeysCountByLocales()
        ];
    }

    /**
     * @return array|null
     */
    public function getAllKeysPlain()
    {
        if (count($this->translationContent->getLocaleKeys()) == 0) {
            return null;
        }

        return [
            'languages'    => $this->translationContent->getLanguagesData(),
            'translations' => $this->translationContent->getTranslationData()
        ];
    }

    /**
     * @return array|null
     */
    public function getGroupsKeys()
    {
        $mergedKeys = $this->mergeKeysFromAllLocales();

        return $this->collectGroupKeys($mergedKeys);
    }

    /**
     * @return array
     */
    public function getLocaleKeys()
    {
        return $this->translationContent->getLocaleKeys();
    }

    /**
     * @return array|null
     */
    public function getAllTransKeys()
    {
        return $this->mergeKeysFromAllLocales();
    }

    /**
     * @param array $keyData
     * @return bool
     */
    public function saveKeyData($keyData)
    {
        if ($this->translationContent->addNewKeyData($keyData)) {
            return $this->translationContentManager->saveTranslationContentEntity($this->translationContent);
        }

        return false;
    }

    /**
     * @param $key
     * @return array
     */
    public function findKeyData($key)
    {
        if (empty($key)) {
            return [];
        }

        $localeKeys = $this->translationContent->getLocaleKeys();
        $translationData = $this->translationContent->getTranslationData();

        $keyData = [];
        foreach ($localeKeys as $locale) {
            if (!isset($translationData[$locale][$key])) {
                continue;
            }

            $keyData[$locale] = $translationData[$locale][$key];
        }

        return $keyData;
    }

    /**
     * @param string $needle
     * @return array
     */
    public function searchKeys($needle)
    {
        if (empty($needle)) {
            return [];
        }

        $foundKeys = [];

        $localeKeys = $this->translationContent->getLocaleKeys();
        $translationData = $this->translationContent->getTranslationData();

        foreach ($localeKeys as $locale) {
            foreach ($translationData[$locale] as $key => $value) {
                if (array_key_exists($key, $foundKeys)) {
                    continue;
                }

                if (stripos($key, $needle) !== false || stripos($value, $needle) !== false) {
                    $foundKeys[$key] = $this->getAllTransForKey($key);
                }
            }
        }

        return $foundKeys;
    }

    /**
     * @return array
     */
    public function getDefaultLocales()
    {
        return [
            'ua' => 'Українська',
            'ru' => 'Русский',
            'en' => 'English'
        ];
    }

    /**
     * @return array
     */
    public function getExistsLocales()
    {
        $localeKeys = $this->translationContent->getLocaleKeys();
        $languagePrefix = $this->translationContent->getLanguagePrefix();
        $languagesData = $this->translationContent->getLanguagesData();

        $locales = [];

        foreach ($localeKeys as $locale) {
            if (array_key_exists($languagePrefix . $locale, $languagesData)) {
                $locales[$locale] = $languagesData[$languagePrefix . $locale];
            }
        }

        return $locales;
    }

    /**
     * @param array $localesData
     * @return bool
     */
    public function createNewTranslationContentEntity($localesData)
    {
        $defaults = [];
        $locales = [];
        $defaultLocales = $this->getDefaultLocales();
        $languagePrefix = $this->translationContent->getLanguagePrefix();

        foreach ($localesData as $locale) {
            if (array_key_exists($locale, $defaultLocales)) {
                $defaults[$languagePrefix . $locale] = $defaultLocales[$locale];
                $locales[$locale] = [];
            }
        }

        if (count($defaults) == 0) {
            return false;
        }

        $data = ['defaults' => $defaults, 'locales' => $locales];

        $translationContent = new TranslationContent($data);

        return $this->translationContentManager->saveTranslationContentEntity($translationContent);
    }

    /**
     * @param string $localeKey
     * @return bool
     */
    public function isLocaleExists($localeKey)
    {
        $localeKeys = $this->translationContent->getLocaleKeys();

        return in_array($localeKey, $localeKeys);
    }

    /**
     * @param string $localeKey
     * @param string $localeName
     * @return bool
     */
    public function addNewLocale($localeKey, $localeName)
    {
        $translationContent = $this->translationContent;

        if (!$translationContent->addLocaleKey($localeKey, $localeName)) {
            return false;
        }

        $allTransKeys = $this->mergeKeysFromAllLocales();

        if ($translationContent->setEmptyTransDataToLocale($localeKey, $allTransKeys)) {
            return $this->translationContentManager->saveTranslationContentEntity($translationContent);
        }

        return false;
    }

    /**
     * @param string $localeKey
     * @return bool
     */
    public function removeLocale($localeKey)
    {
        $translationContent = $this->translationContent;

        if (!$translationContent->removeLocaleKey($localeKey)) {
            return false;
        }

        return $this->translationContentManager->saveTranslationContentEntity($translationContent);
    }

    private function collectGroupKeys($mergedKeys)
    {
        $prevKey = '';
        $groups = [];
        $current = null;
        $currentGroupMinKey = '';

        foreach ($mergedKeys as $transKey) {
            $commonPartsOfKeys = $this->getCommonPartsOfKeys($prevKey, $transKey, $currentGroupMinKey);

            if ($prevKey == '' || $commonPartsOfKeys === '') {
                $group = [
                    'common' => '',
                    'error'  => false,
                    'items'  => []
                ];
                $len = array_push($groups, $group);
                $current = $len - 1;
                $currentGroupMinKey = '';
            } else {
                $currentGroupMinKey = $commonPartsOfKeys;
                $groups[$current]['common'] = $commonPartsOfKeys;
            }

            $transForKey = $this->getAllTransForKeyWithErrorMark($transKey);
            $groups[$current]['items'][] = [
                'key'   => $transKey,
                'trans' => $transForKey['transForKey'],
                'error' => $transForKey['error']
            ];
            $prevKey = $transKey;
            if ($transForKey['error']) {
                $groups[$current]['error'] = true;
            }
        }

        return $groups;
    }

    private function getCommonPartsOfKeys($prevKey, $transKey, $commonKey)
    {
        if (strlen($prevKey) === 0) {
            $transKeyParts = explode('.', $transKey);

            return implode('.', array_splice($transKeyParts, $this->maxDeep));
        }

        if (strpos($transKey, '.') === false) {
            return '';
        }

        $prevKeyParts = explode('.', $prevKey);
        $transKeyParts = explode('.', $transKey);
        if (count($prevKeyParts) < $this->minDeep || count($transKeyParts) < $this->minDeep) {
            return '';
        }
        $minDeep = strlen($commonKey) > 0 ? count(explode('.', $commonKey)) : $this->minDeep;

        for ($i = $this->maxDeep; $i >= $minDeep; $i--) {
            array_splice($prevKeyParts, $i);
            array_splice($transKeyParts, $i);

            $diff = array_diff($prevKeyParts, $transKeyParts);
            if (count($diff) == 0) {
                return implode('.', $transKeyParts);
            }
        }

        return '';
    }

    private function getAllTransForKeyWithErrorMark($key)
    {
        $transForKey = [];
        $error = false;

        $localeKeys = $this->translationContent->getLocaleKeys();
        $translationData = $this->translationContent->getTranslationData();

        foreach ($localeKeys as $locale) {
            if (array_key_exists($key, $translationData[$locale])) {
                $transForKey[$locale] = $translationData[$locale][$key];
            } else {
                $transForKey[$locale] = null;
                $error = true;
            }
        }

        return [
            'transForKey' => $transForKey,
            'error'       => $error
        ];
    }

    private function getAllTransForKey($key)
    {
        $transForKey = [];

        $localeKeys = $this->translationContent->getLocaleKeys();
        $translationData = $this->translationContent->getTranslationData();

        foreach ($localeKeys as $locale) {
            if (array_key_exists($key, $translationData[$locale])) {
                $transForKey[$locale] = $translationData[$locale][$key];
            } else {
                $transForKey[$locale] = null;
            }
        }

        return $transForKey;
    }

    private function mergeKeysFromAllLocales()
    {
        if (count($this->translationContent->getLocaleKeys()) == 0) {
            return [];
        }

        $localeKeys = $this->translationContent->getLocaleKeys();
        $translationData = $this->translationContent->getTranslationData();

        $transKeys = array_keys($translationData[$localeKeys[0]]);
        for ($i = 1; $i < count($localeKeys); $i++) {
            $diff = array_diff(array_keys($translationData[$localeKeys[$i]]), $transKeys);
            $transKeys = array_merge($transKeys, $diff);
        }
        sort($transKeys);

        return $transKeys;
    }

    private function getKeysCountByLocales()
    {
        $translationData = $this->translationContent->getTranslationData();

        $countByLocales = [];
        foreach ($this->translationContent->getLocaleKeys() as $localeKey) {
            if (isset($translationData[$localeKey])) {
                $countByLocales[$localeKey] = count($translationData[$localeKey]);
            } else {
                $countByLocales[$localeKey] = 0;
            }
        }

        return $countByLocales;
    }
}
