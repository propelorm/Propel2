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

            // the new behavior is already registered
            if ($this->hasBehavior($behavior->getId()) && $behavior->allowMultiple()) {
                // the user probably just forgot to specify the "id" attribute
                if ($behavior->getId() === $behavior->getName()) {
                    throw new BuildException(sprintf('Behavior "%s" is already registered. Specify a different ID attribute to register the same behavior several times.', $behavior->getName()));
                } else { // or he copy-pasted it and forgot to update it.
                    throw new BuildException(sprintf('A behavior with ID "%s" is already registered.', $behavior->getId()));
                }
            }

            $this->registerBehavior($behavior);
            $this->behaviors[$behavior->getId()] = $behavior;

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
     * @param  string  $id the behavior id
     * @return boolean True if the behavior exists
     */
    public function hasBehavior($id)
    {
        return isset($this->behaviors[$id]);
    }

    /**
     * Get behavior by id
     *
     * @param  string   $id the behavior id
     * @return Behavior a behavior object or null if the behavior doesn't exist
     */
    public function getBehavior($id)
    {
        if ($this->hasBehavior($id)) {
            return $this->behaviors[$id];
        }

        return null;
    }
}
