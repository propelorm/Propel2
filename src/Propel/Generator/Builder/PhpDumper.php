<?php

namespace Propel\Generator\Builder;

use Mandango\Mondator\Definition\Definition as BaseDefinition;

/**
 * The Mondator Dumper +.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
class PhpDumper
{
    /**
     * @var ClassDefinition
     */
    protected $definition;

    /**
     * Constructor.
     *
     * @param \Mandango\Mondator\Definition\Definition $definition The definition.
     *
     * @api
     */
    public function __construct(BaseDefinition $definition)
    {
        $this->setDefinition($definition);
    }

    /**
     * Set the definition.
     *
     * @param Mandango\Mondator\Definition\Definition $definition The definition.
     *
     * @api
     */
    public function setDefinition(BaseDefinition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Returns the definition
     *
     * @return Mandango\Mondator\Definition\Definition The definition.
     *
     * @api
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Dump the definition.
     *
     * @return string The PHP code of the definition.
     *
     * @api
     */
    public function dump()
    {
        $php =
            $this->startFile().
            $this->addNamespace().
            $this->addUses().
            $this->startClass().
            $this->addProperties().
            $this->addMethods().
            $this->endClass()
            ;

        $fixer = [
            'Symfony\CS\Fixer\ControlSpacesFixer',
            'Symfony\CS\Fixer\CurlyBracketsNewlineFixer',
            'Symfony\CS\Fixer\ElseIfFixer',
//            'Symfony\CS\Fixer\EncodingFixer',
            'Symfony\CS\Fixer\EndOfFileLineFeedFixer',
            'Symfony\CS\Fixer\ExtraEmptyLinesFixer',
            'Symfony\CS\Fixer\FunctionDeclarationSpacingFixer',
//            'Symfony\CS\Fixer\IncludeFixer',
            'Symfony\CS\Fixer\IndentationFixer',
            'Symfony\CS\Fixer\LineFeedFixer',
            'Symfony\CS\Fixer\LowercaseKeywordsFixer',
            'Symfony\CS\Fixer\LowercaseNativeConstantsFixer',
            'Symfony\CS\Fixer\NewWithBracesFixer',
            'Symfony\CS\Fixer\ObjectOperatorFixer',
//            'Symfony\CS\Fixer\PhpClosingTagFixer',
            'Symfony\CS\Fixer\PhpdocParamsAlignmentFixer',
            'Symfony\CS\Fixer\ReturnStatementsFixer',
//            'Symfony\CS\Fixer\ShortTagFixer',
            'Symfony\CS\Fixer\SpacesNearCastFixer',
            'Symfony\CS\Fixer\StandardizeNotEqualFixer',
            'Symfony\CS\Fixer\TernarySpacesFixer',
            'Symfony\CS\Fixer\TrailingSpacesFixer',
            'Symfony\CS\Fixer\UnusedUseStatementsFixer',
            'Symfony\CS\Fixer\VisibilityFixer',
        ];
        $dummyFile = new \SplFileInfo('/tmp/bla.php');

        foreach ($fixer as $fix) {
            $fix = new $fix;
            $php = $fix->fix($dummyFile, $php);
        }

        $propelDocFixer = new \Propel\Generator\Builder\PhpdocFixer();
        $php = $propelDocFixer->fix($php);

        return $php;
    }

    /**
     * Export an array.
     *
     * Based on Symfony\Component\DependencyInjection\Dumper\PhpDumper::exportParameters
     * http://github.com/symfony/symfony
     *
     * @param array $array  The array.
     * @param int   $indent The indent.
     *
     * @return string The array exported.
     */
    static public function exportArray(array $array, $indent)
    {
        $code = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = self::exportArray($value, $indent + 4);
            } else {
                $value = null === $value ? 'null' : var_export($value, true);
            }

            $code[] = sprintf('%s%s => %s,', str_repeat(' ', $indent), var_export($key, true), $value);
        }

        return sprintf("array(\n%s\n%s)", implode("\n", $code), str_repeat(' ', $indent - 4));
    }

    private function startFile()
    {
        return <<<EOF
<?php

EOF;
    }

    protected function addUses()
    {
        $script = '';

        $classHasNamespace = !!$this->definition->getNamespace();

        foreach ($this->definition->getUseStatements() as $useStatement) {

            $useNamespace = explode('\\', $useStatement->getFqcn());
            if (count($useNamespace) === 1 && !$classHasNamespace && !$useStatement->getAlias()) {
                //we shouldn't `use` a class without namespace in our non-namespaced class
                continue;
            }

            $script .= "use {$useStatement->getFqcn()}";
            if ($alias = $useStatement->getAlias()) {
                $script .= " as $alias";
            }
            $script .= ";\n";
        }

        return $script;
    }

    private function addNamespace()
    {
        if (!$namespace = $this->definition->getNamespace()) {
            return '';
        }

        return <<<EOF

namespace $namespace;

EOF;
    }

    private function startClass()
    {
        $code = "\n";

        // doc comment
        if ($docComment = $this->definition->getDocComment()) {
            $code .= $docComment."\n";
        }

        /*
         * declaration
         */
        $declaration = '';

        // abstract
        if ($this->definition->isAbstract()) {
            $declaration .= 'abstract ';
        }

        if ($this->definition->isTrait()) {
            $declaration .= 'trait ';
        } else {
            $declaration .= 'class ';
        }

        // class
        $declaration .= $this->definition->getClassName();

        // parent class
        if ($parentClass = $this->definition->getParentClass()) {
            $declaration .= ' extends '.$parentClass;
        }

        // interfaces
        if ($interfaces = $this->definition->getInterfaces()) {
            $declaration .= ' implements '.implode(', ', $interfaces);
        }

        $code .= <<<EOF
$declaration
{
EOF;

        return $code;
    }

    private function addProperties()
    {
        $code = '';

        $properties = $this->definition->getProperties();
        foreach ($properties as $property) {
            $code .= "\n";

            if ($docComment = $property->getDocComment()) {
                $code .= $docComment."\n";
            }
            $isStatic = $property->isStatic() ? 'static ' : '';

            $value = $property->getValue();
            if (null === $value) {
                $code .= <<<EOF
    $isStatic{$property->getVisibility()} \${$property->getName()};
EOF;
            } else {
                $value = is_array($property->getValue()) ? self::exportArray($property->getValue(), 8) : var_export($property->getValue(), true);

                $code .= <<<EOF
    $isStatic{$property->getVisibility()} \${$property->getName()} = $value;
EOF;
            }
        }
        if ($properties) {
            $code .= "\n";
        }

        return $code;
    }

    private function addMethods()
    {
        $code = '';

        foreach ($this->definition->getMethods() as $method) {
            $code .= "\n";

            // doc comment
            if ($docComment = $method->getDocComment()) {
                $code .= $docComment."\n";
            }

            // isFinal
            $isFinal = $method->isFinal() ? 'final ' : '';

            // isStatic
            $isStatic = $method->isStatic() ? 'static ' : '';

            // abstract
            if ($method->isAbstract()) {
                $code .= <<<EOF
    abstract $isStatic{$method->getVisibility()} function {$method->getName()}({$method->getArguments()});
EOF;
            } else {
                $methodCode = trim($method->getCode());
                if ($methodCode) {
                    $methodCode = '    '.$methodCode."\n    ";
                }
                $code .= <<<EOF
    $isFinal$isStatic{$method->getVisibility()} function {$method->getName()}({$method->getArguments()})
    {
    $methodCode}
EOF;
            }

            $code .= "\n";
        }

        return $code;
    }

    private function endClass()
    {
        $code = '';

        if (!$this->definition->getProperties() && !$this->definition->getMethods()) {
            $code .= "\n";
        }

        $code .= <<<EOF
}
EOF;

        return $code;
    }
}
