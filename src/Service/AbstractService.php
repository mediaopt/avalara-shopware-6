<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use MoptAvalara6\Adapter\AdapterInterface;

/**
 * @author derksen mediaopt GmbH
 * @package MoptAvalara6\Service
 */
abstract class AbstractService
{
    /**
     *
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * get adapter
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}
