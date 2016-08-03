<?php

namespace Shas\TransEditBundle\Entity\EntityManager;

use Shas\TransEditBundle\Entity\TranslationContent;
use Shas\TransEditBundle\Services\FileManager;

class TranslationContentManager
{
    /** @var FileManager $fileManager */
    private $fileManager;

    /**
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @return TranslationContent|null
     */
    public function getTranslationContentEntity()
    {
        $fileContent = $this->fileManager->getCurrentFileContent();
        if ($fileContent === false) {
            return null;
        }

        $fileData = json_decode($fileContent, true);
        if (empty($fileData)) {
            return null;
        }

        $translationContent = new TranslationContent($fileData);

        return $translationContent;
    }

    /**
     * @param TranslationContent $translationContent
     * @return bool
     */
    public function saveTranslationContentEntity(TranslationContent $translationContent)
    {
        $newData = json_encode(
            $translationContent->getAllData(),
            JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        return $this->fileManager->saveCurrentFileContent($newData);
    }

    /**
     * @param string $localeCode
     * @param string $localeName
     * @return bool
     */
    public function addLocale($localeCode, $localeName)
    {
        if (empty($localeCode) || empty($localeName)) {
            return false;
        }

        $translationContent = $this->getTranslationContentEntity();
        if (empty($translationContent)) {
            return false;
        }

        if (!$translationContent->addLocaleKey($localeCode, $localeName)) {
            return false;
        }

        return $this->saveTranslationContentEntity($translationContent);
    }
}
