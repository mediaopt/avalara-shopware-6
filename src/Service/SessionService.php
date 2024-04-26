<?php

namespace MoptAvalara6\Service;

use ContainerS8x3vqV\FallbackUrlPackageGhostE015acd;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionService extends Session
{
    private bool $isHeadless;

    public function __construct()
    {
        parent::__construct();
    }

    private function isHeadless(AvalaraSDKAdapter $adapter)
    {
        if (!isset($this->isHeadless)) {
            $this->isHeadless = $adapter->getPluginConfig(Form::HEADLESS_MODE);
        }

        return $this->isHeadless;
    }

    public function setValue(string $name, mixed $value, AvalaraSDKAdapter $adapter): void
    {
        if (!$this->isHeadless($adapter)) {
            parent::set($name, $value);
        }
    }

    public function getValue(string $name, AvalaraSDKAdapter $adapter): mixed
    {
        if (!$this->isHeadless($adapter)) {
            return parent::get($name);
        }
        return null;
    }

}
?>