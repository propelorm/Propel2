<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Model\Table;

/**
 * Generates the empty PHP5 stub object class for user object model (OM).
 *
 * This class produces the empty stub class that can be customized with application
 * business logic, custom behavior, etc.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class ExtensionObjectBuilder extends AbstractObjectBuilder
{
    private $twig;
    public function __construct(Table $table)
    {
        parent::__construct($table);

        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/templates/');
        $this->twig = new \Twig_Environment($loader);
    }

    /**
     * Returns the name of the current class being built.
     * @return string
     */
    public function getUnprefixedClassName()
    {
        return $this->getTable()->getPhpName();
    }

    /**
     * Adds class phpdoc comment and opening of class.
     * @param string &$script The script will be modified in this method.
     */
    protected function addClassOpen(&$script)
    {
        $script .= $this->twig->render('ExtensionObject/_classOpen.php.twig', ['builder' => $this]);
    }

    /**
     * Specifies the methods that are added as part of the stub object class.
     *
     * By default there are no methods for the empty stub classes; override this method
     * if you want to change that behavior.
     *
     * @see ObjectBuilder::addClassBody()
     */
    protected function addClassBody(&$script)
    {
    }

    /**
     * Closes class.
     * @param string &$script The script will be modified in this method.
     */
    protected function addClassClose(&$script)
    {
        $script .= $this->twig->render('ExtensionObject/_classClose.php.twig', ['builder' => $this]);
        $this->applyBehaviorModifier('extensionObjectFilter', $script, "");
    }
}
