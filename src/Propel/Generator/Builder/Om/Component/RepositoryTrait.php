<?php


namespace Propel\Generator\Builder\Om\Component;


trait RepositoryTrait
{

    /**
     * Adds the PHP code to return a instance pool key for the passed-in primary key variable names.
     *
     * @param  array $pkphp An array of PHP var names / method calls representing complete pk.
     *
     * @return string
     */
    public function getFirstLevelCacheKeySnippet($pkphp)
    {
        $pkphp = (array)$pkphp; // make it an array if it is not.
        $script = '';
        if (count($pkphp) > 1) {
            $script .= "serialize(array(";
            $i = 0;
            foreach ($pkphp as $pkvar) {
                $script .= ($i++ ? ', ' : '') . "(string) $pkvar";
            }
            $script .= "))";
        } else {
            $script .= "(string) " . $pkphp[0];
        }

        return $script;
    }
}