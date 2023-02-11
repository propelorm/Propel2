<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Pluralizer;

/**
 * Standard replacement English pluralizer class. Based on the links below
 *
 * @link http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/
 * @link http://blogs.msdn.com/dmitryr/archive/2007/01/11/simple-english-noun-pluralizer-in-c.aspx
 * @link http://api.cakephp.org/view_source/inflector/
 *
 * @author paul.hanssen
 */
class StandardEnglishPluralizer implements PluralizerInterface
{
    /**
     * @var array<string, string>
     */
    protected $plural = [
        '(matr|vert|ind)(ix|ex)' => '\1ices',
        '(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)us' => '\1i',
        '(buffal|tomat)o' => '\1oes',

        'x' => 'xes',
        'ch' => 'ches',
        'sh' => 'shes',
        'ss' => 'sses',

        'ay' => 'ays',
        'ey' => 'eys',
        'iy' => 'iys',
        'oy' => 'oys',
        'uy' => 'uys',
        'y' => 'ies',

        'ao' => 'aos',
        'eo' => 'eos',
        'io' => 'ios',
        'oo' => 'oos',
        'uo' => 'uos',
        'o' => 'os',

        'us' => 'uses',

        'cis' => 'ces',
        'sis' => 'ses',
        'xis' => 'xes',

        'zoon' => 'zoa',

        'itis' => 'itis',
        'ois' => 'ois',
        'pox' => 'pox',
        'ox' => 'oxes',

        'foot' => 'feet',
        'goose' => 'geese',
        'tooth' => 'teeth',
        'quiz' => 'quizzes',
        'alias' => 'aliases',

        'alf' => 'alves',
        'elf' => 'elves',
        'olf' => 'olves',
        'arf' => 'arves',
        'nife' => 'nives',
        'life' => 'lives',
    ];

    /**
     * @var array<string, string>
     */
    protected $irregular = [
        'leaf' => 'leaves',
        'loaf' => 'loaves',
        'move' => 'moves',
        'foot' => 'feet',
        'goose' => 'geese',
        'genus' => 'genera',
        'sex' => 'sexes',
        '^ox' => 'oxen',
        'child' => 'children',
        'man' => 'men',
        'tooth' => 'teeth',
        'person' => 'people',
        'wife' => 'wives',
        'mythos' => 'mythoi',
        'testis' => 'testes',
        'numen' => 'numina',
        'quiz' => 'quizzes',
        'alias' => 'aliases',
    ];

    /**
     * @var array<string>
     */
    protected $uncountable = [
        'sheep',
        'fish',
        'deer',
        'series',
        'species',
        'money',
        'rice',
        'information',
        'equipment',
        'news',
        'people',
    ];

    /**
     * Generate a plural name based on the passed in root.
     *
     * @param string $root The root that needs to be pluralized (e.g. Author)
     *
     * @return string The plural form of $root (e.g. Authors).
     */
    public function getPluralForm(string $root): string
    {
        // save some time in the case that singular and plural are the same
        if (in_array(strtolower($root), $this->uncountable, true)) {
            return $root;
        }

        // check for irregular singular words
        foreach ($this->irregular as $pattern => $result) {
            $searchPattern = '/' . $pattern . '$/i';
            if (preg_match($searchPattern, $root)) {
                $replacement = preg_replace($searchPattern, $result, $root);
                // look at the first char and see if it's upper case
                // I know it won't handle more than one upper case char here (but I'm OK with that)
                if (ctype_upper($root[0])) {
                    $replacement = ucfirst($replacement);
                }

                return $replacement;
            }
        }

        // check for irregular singular suffixes
        foreach ($this->plural as $pattern => $result) {
            $searchPattern = '/' . $pattern . '$/i';
            if (preg_match($searchPattern, $root)) {
                return preg_replace($searchPattern, $result, $root);
            }
        }

        // fallback to naive pluralization
        return $root . 's';
    }
}
