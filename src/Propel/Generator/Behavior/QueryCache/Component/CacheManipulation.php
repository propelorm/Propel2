<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\QueryCache\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;

class CacheManipulation extends BuildComponent
{
    public function process()
    {
        $this->addCacheContains();
        $this->addCacheFetch();
        $this->addCacheStore();
    }

    protected function addCacheContains()
    {
        $method = $this->addMethod('cacheContains')
            ->addSimpleParameter('key');

        switch ($this->getBehavior()->getParameter('backend')) {
            case 'apc':
                $method->setBody("return apc_fetch(\$key);");
                break;
            case 'array':
                $method->setBody("return isset(self::\$cacheBackend[\$key]);");
                break;
            case 'custom':
            default:
                $method->setBody("throw new PropelException('You must override the cacheContains(), cacheStore(), and cacheFetch() methods to enable query cache');");
                break;
        }
    }

    protected function addCacheStore()
    {
        $method = $this->addMethod('cacheStore')
            ->addSimpleParameter('key')
            ->addSimpleParameter('value')
            ->addSimpleParameter('lifetime', 'int', $this->getBehavior()->getParameter('lifetime'));

        switch ($this->getBehavior()->getParameter('backend')) {
            case 'apc':
                $method->setBody("apc_store(\$key, \$value, \$lifetime);");
                break;
            case 'array':
                $method->setBody("self::\$cacheBackend[\$key] = \$value;");
                break;
            case 'custom':
            default:
                $method->setBody("throw new PropelException('You must override the cacheContains(), cacheStore(), and cacheFetch() methods to enable query cache');");
                break;
        }
    }

    protected function addCacheFetch()
    {
        $method = $this->addMethod('cacheFetch')
            ->addSimpleParameter('key');

        switch ($this->getBehavior()->getParameter('backend')) {
            case 'apc':
                $method->setBody("return apc_fetch(\$key);");
                break;
            case 'array':
                $method->setBody("return isset(self::\$cacheBackend[\$key]) ? self::\$cacheBackend[\$key] : null;");
                break;
            case 'custom':
            default:
                $method->setBody("throw new PropelException('You must override the cacheContains(), cacheStore(), and cacheFetch() methods to enable query cache');");
                break;
        }
    }
}
