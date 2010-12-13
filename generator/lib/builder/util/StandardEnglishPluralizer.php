<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/Pluralizer.php';

/**
 * A standard English pluralizer class.
 *
 * @author     paul.hanssen
 * @version    $Revision$
 * @package    propel.generator.builder.util
 */
class StandardEnglishPluralizer implements Pluralizer
{
	protected $_plural = array(
		'ch' => 'ches',
		'sh' => 'shes',
		'ss' => 'sses',
		
		'ay' => 'ays',
		'ey' => 'eys',
		'iy' => 'iys',
		'oy' => 'oys',
		'uy' => 'uys',
		'y'  => 'ies',
		
		'ao' => 'aos',
		'eo' => 'eos',
		'io' => 'ios',
		'oo' => 'oos',
		'uo' => 'uos',
		'o'	 => 'oes',

		'cis' => 'ces',
		'sis' => 'ses',
		'xis' => 'xes',
		
		'zoon' => 'zoa',

		'itis' => 'itis',
		'ois'  => 'ois',
		'pox'  => 'pox',
		'ox'   => 'oxes',
		
		'foot'  => 'feet',
		'goose' => 'geese',
		'tooth' => 'teeth',
		
		'alf'  => 'alves',
		'elf'  => 'elves',
		'olf'  => 'olves',
		'arf'  => 'arves',
		'nife' => 'nives',
		'life' => 'lives'
	);

	protected $_irregular = array(
		'leaf'   => 'leaves',
		'move'   => 'moves',
		'foot'   => 'feet',
		'goose'  => 'geese',
		'sex'    => 'sexes',
		'ox'     => 'oxen',
		'child'  => 'children',
		'man'    => 'men',
		'tooth'  => 'teeth',
		'person' => 'people',
		'wife'   => 'wives'
	);

	private $_uncountable = array(
		'sheep',
		'fish',
		'deer',
		'series',
		'species',
		'money',
		'rice',
		'information',
		'equipment'
	);	
	
	/**
	 * Generate a plural name based on the passed in root.
	 * @param	  string $root The root that needs to be pluralized (e.g. Author)
	 * @return	 string The plural form of $root (e.g. Authors).
	 */
	public function getPluralForm($root)
	{
		// save some time in the case that singular and plural are the same
		if (in_array(strtolower($root), $this->_uncountable)) {
			return $root;
		}

		// check for irregular singular words
		foreach ($this->_irregular as $pattern => $result) {
			$searchPattern = '/' . $pattern . '$/i';
			if (preg_match($searchPattern, $root)) {
				$replacement = preg_replace($searchPattern, $result, $root);
				// look at the first char and see if it's upper case
				// I know it won't handle more than one upper case char here (but I'm OK with that)
				if (preg_match('/^[A-Z]/', $root)) {
					$replacement = ucfirst($replacement);
				}
				return $replacement;				
			}
		}

		// check for irregular singular suffixes
		foreach ($this->_plural as $pattern => $result) {
			$searchPattern = '/' . $pattern . '$/i';
			if (preg_match($searchPattern, $root)) {
				return preg_replace($searchPattern, $result, $root);
			}
		}
		
		// fallback to naive pluralization
		return $root . 's';
	}
}