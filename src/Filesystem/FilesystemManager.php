<?php

namespace yswery\DNS\Filesystem;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use yswery\DNS\Resolver\JsonFileSystemResolver;

class FilesystemManager
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $zonePath;

    public function __construct($basePath = null, $zonePath = null)
    {
        if ($basePath)
        {
            $this->setBasePath($basePath);
        }

        if ($zonePath)
        {
            $this->setZonePath($zonePath);
        }


        $this->registerFilesystem();
    }

    protected function registerFilesystem()
    {
        $this->filesystem = new Filesystem();

        // make sure our directories exist
        if (!$this->filesystem->exists($this->zonePath()))
        {
            try {
                $this->filesystem->mkdir($this->zonePath(), 0700);
            } catch (IOExceptionInterface $e) {
                // todo: implement logging functions
            }

        }

    }

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
        return $this;
    }

    public function basePath()
    {
        return $this->basePath;
    }

    public function setZonePath($zonePath)
    {
        $this->zonePath = rtrim($zonePath, '\/');
        return $this;
    }

    public function zonePath()
    {
        return $this->zonePath ?: $this->basePath.DIRECTORY_SEPARATOR.'zones';
    }

    /**
     * @param string $zone
     * @return JsonFileSystemResolver
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function getZone(string $zone)
    {
        $zoneFile = $this->basePath().DIRECTORY_SEPARATOR.$zone.'.json';
        return new JsonFileSystemResolver($zoneFile);
    }
}