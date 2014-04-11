<?php
namespace Propel\Generator\Model;

use Propel\Generator\Util\BehaviorLocator;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Config\GeneratorConfigInterface;

/**
 * BehaviorableTrait use it on every model that can hold behaviors
 *
 */
trait BehaviorableTrait
{
    /**
     * @var Behavior[]
     */
    protected $behaviors;

    /**
     * @var BehaviorLocator
     */
    private $behaviorLocator;

    /**
     * @return GeneratorConfigInterface
     */
    abstract protected function getGeneratorConfig();

    /**
     * Returns the behavior locator.
     *
     * @return BehaviorLocator
     */
    private function getBehaviorLocator()
    {
        if (null === $this->behaviorLocator) {
            $config = $this->getGeneratorConfig();
            if (null !== $config) {
                $this->behaviorLocator = $config->getBehaviorLocator();
                if (null === $this->behaviorLocator) {
                    $this->behaviorLocator = new BehaviorLocator();
                }
            } else {
                $this->behaviorLocator = new BehaviorLocator();
            }
        }

        return $this->behaviorLocator;
    }

    /**
     * Adds a new Behavior
     *
     * @param $bdata
     * @throws BuildException when the added behavior is not an instance of \Propel\Generator\Model\Behavior
     * @return Behavior       $bdata
     */
    public function addBehavior($bdata)
    {
        if ($bdata instanceof Behavior) {
            $behavior = $bdata;
            $this->registerBehavior($behavior);
            $this->behaviors[$behavior->getName()] = $behavior;

            return $behavior;
        }

        $locator = $this->getBehaviorLocator();
        $class = $locator->getBehavior($bdata['name']);
        $behavior = new $class();
        if (!($behavior instanceof Behavior)) {
            throw new BuildException(sprintf('Behavior [%s: %s] not instance of %s',
                    $bdata['name'], $class, '\Propel\Generator\Model\Behavior'));
        }
        $behavior->loadMapping($bdata);

        return $this->addBehavior($behavior);
    }

    abstract protected function registerBehavior(Behavior $behavior);

    /**
     * Returns the list of behaviors.
     *
     * @return Behavior[]
     */
    public function getBehaviors()
    {
        return $this->behaviors;
    }

    /**
     * check if the given behavior exists
     *
     * @param  string  $name the behavior name
     * @return boolean True if the behavior exists
     */
    public function hasBehavior($name)
    {
        return array_key_exists($name, $this->behaviors);
    }

    /**
     * Get behavior by name
     *
     * @param  string   $name the behavior name
     * @return Behavior a behavior object or null if the behavior doesn't exist
     */
    public function getBehavior($name)
    {
        if ($this->hasBehavior($name)) {
            return $this->behaviors[$name];
        }

        return null;
    }
}
