<?php

namespace Propel\Generator\Builder;

use Symfony\CS\FixerInterface;

class PhpdocFixer
{
    protected $content;
    protected $i;
    protected $blankChars;

    /**
     * @param string $content
     * @return string
     */
    public function fix($content)
    {
        $this->blankChars = array_flip([' ', "\n", "\t"]);
        $this->content = $content;
        $inForbiddenContext = false; //usually between ", ', or <<<EOF
        $startCharIdx = 0;
        $forbiddenContextHereDocKeyword = null;

        for ($this->i = 0, $l = strlen($content); $this->i < $l; $this->i++) {

            if (!$inForbiddenContext) {
                //check if we start a forbidden context
                if ($this->matchEat("<<<")) {
                    $forbiddenContextHereDocKeyword = $this->eatTill("\n");
                    $forbiddenContextHereDocKeyword = trim($forbiddenContextHereDocKeyword, "'");
                    $inForbiddenContext = 2;
                    continue;
                }
                if ($this->match('"') || $this->match("'")) {
                    $inForbiddenContext = 1;
                    continue;
                }
            }

            if ($inForbiddenContext) {
                //check if we can reverse the forbidden context
                if (1 === $inForbiddenContext && ($this->match('"') || $this->match("'"))) {
                    $allBackSlashes = $this->getBackTillNot('\\');
                    if (0 === strlen($allBackSlashes) % 2) {
                        //no escaping backslash found, escape the hell
                        $inForbiddenContext = false;
                    }
                }

                if (2 === $inForbiddenContext && $this->match("\n" . $forbiddenContextHereDocKeyword . ';')) {
                    $inForbiddenContext = false;
                }

                continue;
            }

            if ($this->match("\n")) {
                $startCharIdx = $this->i + 1;
            }

            if ($this->match('/**')) {
                //we are now in docBlock
                //jump back to newline
                $docBlock = $this->eatTill('*/');
                $indentation = $this->detectIndentationOfNextLine();
                $newDocBlock = $this->fixDocBlock($docBlock, $indentation);
                if ($newDocBlock != $docBlock) {
                    $endCharIdx = $this->i;

                    //replace docBlock
                    $this->content =
                          substr($this->content, 0, $startCharIdx)
                        . $newDocBlock
                        . substr($this->content, $endCharIdx);

                    //set $this->i to new position
                    $this->i = $startCharIdx + strlen($newDocBlock);
                }
            }
        }

        return $this->content;
    }

    /**
     * @param string $docBlock
     * @param int $indentation
     * @return string
     */
    protected function fixDocBlock($docBlock, $indentation)
    {
        $newDocBlock = trim($docBlock);
        $spaces = str_repeat(' ', $indentation);

        //fix first line
        $newDocBlock  = $spaces . $newDocBlock;

        //fix empty lines
        $newDocBlock = preg_replace('/\n([\s\t]*)\n/', "\n", $newDocBlock);

        //fix lines with no starting *
        $newDocBlock = preg_replace('/^\s*([^\*\s\/])/m', $spaces . ' * ', $newDocBlock);

        //fix all other
        $newDocBlock = preg_replace('/^(\s*)\*/m', $spaces . ' *', $newDocBlock);

        return $newDocBlock;
    }

    protected function detectIndentationOfNextLine()
    {
        $indentation = 0;
        for ($i = $this->i, $l = strlen($this->content); $i < $l; $i++) {
            $char = $this->content[$i];
            if ($char == "\n") {
                $indentation = 0;
                continue;
            }

            if (!isset($this->blankChars[$char])) {
                //no whitespace anymore, return the indentation
                return $indentation;
            }

            //still only whitespace, increase the counter
            $indentation++;
        }

        return 0;
    }

    protected function match($chars)
    {
        $charCount = strlen($chars);
        return $chars === substr($this->content, $this->i, $charCount);
    }

    protected function matchEat($chars)
    {
        if ($match = $this->match($chars)) {
            $this->i += strlen($chars);
        }

        return $match;
    }

    protected function eatTill($chars)
    {
        $eaten = '';
        $charCount = strlen($chars);
        for ($l = strlen($this->content); $this->i < $l; $this->i++) {
            $buffer = substr($this->content, $this->i - $charCount, $charCount);
            if ($buffer === $chars) {
                //we found $chars, so leave
                return $eaten;
            }
            $eaten .= $this->content[$this->i];
        }

        return null;
    }

    protected function getBackTillNot($chars)
    {
        $eaten = '';
        $charCount = strlen($chars);
        for ($i = $this->i; $i >= 0; $i--) {
            $buffer = substr($this->content, $i - $charCount, $charCount);
            if ($buffer !== $chars) {
                //we found another chars than $chars, so leave
                return $eaten;
            }
            $eaten = $this->content[$i - $charCount] . $eaten;
        }

        return null;
    }

}