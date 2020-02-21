<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Config;

use yswery\DNS\Exception\ConfigFileNotFoundException;

class FileConfig
{
    /**
     * @var string
     */
    protected $configFile;

    /**
     * @var object
     */
    protected $config;

    public function __construct(string $configFile)
    {
        $this->configFile = $configFile;
    }

    /**
     * @throws ConfigFileNotFoundException
     */
    public function load()
    {
        // make sure the file exists before loading the config
        if (file_exists($this->configFile)) {
            $data = file_get_contents($this->configFile);
            $this->config = json_decode($data);
        } else {
            throw new ConfigFileNotFoundException('Config file not found.');
        }
    }

    public function save()
    {
        file_put_contents($this->configFile, json_encode($this->config));
    }

    public function get($key, $default = null)
    {
        if (isset($this->config->{$key})) {
            return $this->config->{$key};
        }

        return $default;
    }

    public function set(array $data)
    {
        $configObject = new RecursiveArrayObject($this->config);
        $configArray = $configObject->getArrayCopy();

        //$origional = json_decode(json_encode($this->config), true);
        $new = array_merge($configArray, $data);

        $this->config = json_decode(json_encode($new), false);
    }

    public function has($key)
    {
        return isset($this->config->{$key});
    }
}
