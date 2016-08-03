<?php

namespace Shas\TransEditBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FileManager
 * @package Shas\TransEditBundle\Services
 */
class FileManager
{
    /** @var ContainerInterface $container */
    private $container;

    /** @var string $currentFileName */
    private $currentFileName = 'translations.json';

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $translationValuesFile = $container->getParameter('translation_values_file');
        $this->currentFileName = $translationValuesFile['file_name'];
    }

    /**
     * @return string
     */
    public function getCurrentFullFileName()
    {
        return $this->currentFileName;
    }

    /**
     * @return string
     */
    public function getCurrentFileContent()
    {
        $content = @file_get_contents($this->getCurrentFullFileName());

        return $content;
    }

    /**
     * @param string $data
     * @return bool
     */
    public function saveCurrentFileContent($data)
    {
        $result = file_put_contents($this->getCurrentFullFileName(), $data);

        return $result === false ? false : true;
    }
}
