<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Parser;

/**
 * CSV parser. Converts data between associative array and CSV formats.
 * CSV parsing code borrowed from php-csv-utils by Luke Visinoni
 * http://code.google.com/p/php-csv-utils/
 *
 * @author Francois Zaninotto
 */
class CsvParser extends AbstractParser
{
    /**
     * @var int
     */
    public const QUOTE_NONE = 0;

    /**
     * @var int
     */
    public const QUOTE_ALL = 1;

    /**
     * @var int
     */
    public const QUOTE_NONNUMERIC = 2;

    /**
     * @var int
     */
    public const QUOTE_MINIMAL = 3;

    /**
     * @phpstan-var non-empty-string
     *
     * @var string
     */
    public string $delimiter = ',';

    /**
     * @phpstan-var non-empty-string
     *
     * @var string
     */
    public string $lineTerminator = "\r\n";

    /**
     * @var string
     */
    public string $quotechar = '"';

    /**
     * @var string
     */
    public string $escapechar = '\\';

    /**
     * @var int
     */
    public $quoting = self::QUOTE_MINIMAL;

    /**
     * Converts data from an associative array to CSV.
     *
     * @param array $array Source data to convert
     * @param string|null $rootKey Will not be used for converting because csv is flat
     * @param bool $isList Whether the input data contains more than one row
     * @param bool $includeHeading Whether the output should contain a heading line
     *
     * @return string Converted data, as a CSV string
     */
    public function fromArray(array $array, ?string $rootKey = null, bool $isList = false, bool $includeHeading = true): string
    {
        $rows = [];
        if ($isList) {
            if ($includeHeading) {
                /** @var array $indexedRow */
                $indexedRow = reset($array);
                $rows[] = implode($this->delimiter, $this->formatRow(array_keys($indexedRow)));
            }
            foreach ($array as $row) {
                $rows[] = implode($this->delimiter, $this->formatRow($row));
            }
        } else {
            if ($includeHeading) {
                $rows[] = implode($this->delimiter, $this->formatRow(array_keys($array)));
            }
            $rows[] = implode($this->delimiter, $this->formatRow($array));
        }

        return implode($this->lineTerminator, $rows) . $this->lineTerminator;
    }

    /**
     * @param array $array
     * @param string|null $rootKey
     *
     * @return string
     */
    public function listFromArray(array $array, ?string $rootKey = null): string
    {
        return $this->fromArray($array, $rootKey, true);
    }

    /**
     * Accepts a row of data and returns it formatted
     *
     * @param array $row An array of data to be formatted for output to the file
     *
     * @return array The formatted array
     */
    protected function formatRow(array $row): array
    {
        foreach ($row as &$column) {
            if (!is_scalar($column)) {
                $column = $this->serialize($column);
            }
            switch ($this->quoting) {
                case self::QUOTE_NONE:
                    // do nothing... no quoting is happening here
                    break;
                case self::QUOTE_ALL:
                    $column = $this->quote($this->escape((string)$column));

                    break;
                case self::QUOTE_NONNUMERIC:
                    if (preg_match('/[^0-9]/', (string)$column)) {
                        $column = $this->quote($this->escape((string)$column));
                    }

                    break;
                case self::QUOTE_MINIMAL:
                default:
                    if ($this->containsSpecialChars((string)$column)) {
                        $column = $this->quote($this->escape((string)$column));
                    }

                    break;
            }
        }

        return $row;
    }

    /**
     * Escapes a column (escapes quotechar with escapechar)
     *
     * @param string $input A single value to be escaped for output
     *
     * @return string Escaped input value
     */
    protected function escape(string $input): string
    {
        return str_replace(
            $this->quotechar,
            $this->escapechar . $this->quotechar,
            $input,
        );
    }

    /**
     * Quotes a column with quotechar
     *
     * @param string $input A single value to be quoted for output
     *
     * @return string Quoted input value
     */
    protected function quote(string $input): string
    {
        return $this->quotechar . $input . $this->quotechar;
    }

    /**
     * Returns true if input contains quotechar, delimiter or any of the characters in lineTerminator
     *
     * @param string $input A single value to be checked for special characters
     *
     * @return bool True if contains any special characters
     */
    protected function containsSpecialChars(string $input): bool
    {
        $specialChars = str_split($this->lineTerminator);
        $specialChars[] = $this->quotechar;
        $specialChars[] = $this->delimiter;
        foreach ($specialChars as $char) {
            if (strpos($input, $char) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Serializes a value to place it into a CSV output
     *
     * @param mixed $input
     *
     * @return string
     */
    protected function serialize($input): string
    {
        return serialize($input);
    }

    /**
     * Alias for CsvParser::fromArray()
     *
     * @param array $array Source data to convert
     * @param bool $isList Whether the input data contains more than one row
     * @param bool $includeHeading Whether the output should contain a heading line
     *
     * @return string Converted data, as a CSV string
     */
    public function toCSV(array $array, bool $isList = false, bool $includeHeading = true): string
    {
        return $this->fromArray($array, null, $isList, $includeHeading);
    }

    /**
     * Converts data from CSV to an associative array.
     *
     * @param string $data Source data to convert, as a CSV string
     * @param string|null $rootKey Will not be used for converting because csv is flat
     * @param bool $isList Whether the input data contains more than one row
     * @param bool $includeHeading Whether the input contains a heading line
     *
     * @return array Converted data
     */
    public function toArray(string $data, ?string $rootKey = null, bool $isList = false, bool $includeHeading = true): array
    {
        $rows = explode($this->lineTerminator, $data);
        if ($includeHeading) {
            $heading = array_shift($rows);
            $keys = explode($this->delimiter, $heading);
        } else {
            $keys = range(0, count($this->getColumns($rows[0])) - 1);
        }
        if ($isList) {
            $array = [];
            foreach ($rows as $row) {
                $values = $this->cleanupRow($this->getColumns($row));
                if ($values !== []) {
                    $array[] = array_combine($keys, $values);
                }
            }
        } else {
            $values = $this->cleanupRow($this->getColumns((string)array_shift($rows)));
            if ($keys === [''] && $values === []) {
                $array = [];
            } else {
                if (count($keys) > count($values)) {
                    // empty values at the end of the row are not match bu the getColumns() regexp
                    $values = array_pad($values, count($keys), null);
                }
                $array = array_combine($keys, $values);
            }
        }

        return $array;
    }

    /**
     * @param string $data
     * @param string|null $rootKey
     *
     * @return array
     */
    public function listToArray(string $data, ?string $rootKey = null): array
    {
        return $this->toArray($data, $rootKey, true);
    }

    /**
     * @param string $row
     *
     * @return array
     */
    protected function getColumns(string $row): array
    {
        $delim = preg_quote($this->delimiter, '/');
        preg_match_all('/(".+?"|[^' . $delim . ']+)(' . $delim . '|$)/', $row, $matches);

        return $matches[1];
    }

    /**
     * Accepts a formatted row of data and returns it raw
     *
     * @param array $row An array of data from a CSV output
     *
     * @return array The cleaned up array
     */
    protected function cleanupRow(array $row): array
    {
        foreach ($row as $key => $column) {
            if ($this->isQuoted($column)) {
                $column = $this->unescape($this->unquote($column));
            }
            if ($this->isSerialized($column)) {
                $column = $this->unserialize($column);
            }
            if ($column === 'N;') {
                $column = null;
            }
            $row[$key] = $column;
        }

        return $row;
    }

    /**
     * @param string $input
     *
     * @return int|false
     */
    protected function isQuoted(string $input)
    {
        $quote = preg_quote($this->quotechar, '/');

        return preg_match('/^' . $quote . '.*' . $quote . '$/', $input);
    }

    /**
     * @param string $input
     *
     * @return string
     */
    protected function unescape(string $input): string
    {
        return str_replace(
            $this->escapechar . $this->quotechar,
            $this->quotechar,
            $input,
        );
    }

    /**
     * @param string $input
     *
     * @return string
     */
    protected function unquote(string $input): string
    {
        return trim($input, $this->quotechar);
    }

    /**
     * Checks whether a value from CSV output is serialized
     *
     * @param string $input
     *
     * @return int|false
     */
    protected function isSerialized(string $input)
    {
        return preg_match('/^\w\:\d+\:\{/', $input);
    }

    /**
     * Unserializes a value from CSV output
     *
     * @param string $input
     *
     * @return mixed
     */
    protected function unserialize(string $input)
    {
        return unserialize($input);
    }

    /**
     * Alias for CsvParser::toArray()
     *
     * @param string $data Source data to convert, as a CSV string
     * @param bool $isList Whether the input data contains more than one row
     * @param bool $includeHeading Whether the input contains a heading line
     *
     * @return array Converted data
     */
    public function fromCSV(string $data, bool $isList = false, bool $includeHeading = true): array
    {
        return $this->toArray($data, null, $isList, $includeHeading);
    }
}
