<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\AlternativeCodingStandards;

use Propel\Generator\Model\Behavior;

/**
 * Changes the coding standard of Propel generated Model classes
 *  - Opening brackets always use newline, e.g.
 *     if ($foo) {
 *       ...
 *     } else {
 *       ...
 *     }
 *    Becomes:
 *     if ($foo)
 *     {
 *       ...
 *     }
 *     else
 *     {
 *       ...
 *     }
 *  - closing comments are removed, e.g.
 *     } // save()
 *    Becomes:
 *     }
 *   - tabs are replaced by 2 whitespaces
 *   - comments are stripped (optional)
 *
 * @author François Zaninotto
 */
class AlternativeCodingStandardsBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'brackets_newline'        => 'true',
        'remove_closing_comments' => 'true',
        'use_whitespace'          => 'true',
        'tab_size'                => 2,
        'strip_comments'          => 'false'
    );

    public function objectFilter(&$script)
    {
        return $this->filter($script);
    }

    public function extensionObjectFilter(&$script)
    {
        return $this->filter($script);
    }

    public function queryFilter(&$script)
    {
        return $this->filter($script);
    }

    public function extensionQueryFilter(&$script)
    {
        return $this->filter($script);
    }

    public function peerFilter(&$script)
    {
        return $this->filter($script);
    }

    public function extensionPeerFilter(&$script)
    {
        return $this->filter($script);
    }

    public function tableMapFilter(&$script)
    {
        return $this->filter($script);
    }

    /**
     * Transform the coding standards of a PHP sourcecode string
     *
     * @param string $script A script string to be filtered, passed as reference
     */
    protected function filter(&$script)
    {
        $filter = array();
        if ('true' === $this->getParameter('brackets_newline')) {
            $filter['#^(\t*)\}\h(else|elseif|catch)(.*)\h\{$#m'] = "$1}
$1$2$3
$1{";
            $filter['#^(\t*)(\w.*)\h\{$#m'] = "$1$2
$1{";
        }
        if ('true' === $this->getParameter('remove_closing_comments')) {
            $filter['#^(\t*)} //.*$#m'] = "$1}";
        }
        if ('true' === $this->getParameter('use_whitespace')) {
            $filter['#\t#'] = str_repeat(' ', $this->getParameter('tab_size'));
        }

        $script = preg_replace(array_keys($filter), array_values($filter), $script);

        if ('true' === $this->getParameter('strip_comments')) {
            $script = self::stripComments($script);
        }
    }

    /**
     * Remove inline and codeblock comments from a PHP code string
     * @param  string $code The input code
     * @return string The input code, without comments
     */
    static public function stripComments($code)
    {
        $output  = '';
        $commentTokens = array(T_COMMENT, T_DOC_COMMENT);
        foreach (token_get_all($code) as $token) {
            if (is_array($token)) {
                if (in_array($token[0], $commentTokens)) {
                    continue;
                }
                $token = $token[1];
            }

            $output .= $token;
        }

        return $output;
    }
}