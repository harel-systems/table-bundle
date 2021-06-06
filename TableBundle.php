<?php

/*
 * This file is part of the TableBundle package.
 *
 * (c) Harel Systems
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harel\TableBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Harel\TableBundle\DependencyInjection\HarelTableExtension;

/**
 * @author Maxime Corteel <maxime.corteel@harelsystems.com>
 */
class TableBundle extends Bundle
{
    /**
     * FIXME This class should be loaded automatically, but it doesn't work
     * @see https://symfony.com/doc/4.4/bundles/extension.html
     */
    public function getContainerExtension()
    {
        return new HarelTableExtension();
    }
}
