<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Propel;

/**
 * Class for iterating over a statement and returning one Propel object at a time
 *
 * @author Francois Zaninotto
 */
class OnDemandIterator implements IteratorInterface
{
    /**
     * @var \Propel\Runtime\Formatter\ObjectFormatter
     */
    protected $formatter;

    /**
     * @var \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    protected $dataFetcher;

    /**
     * @var array|bool|null
     */
    protected $currentRow;

    /**
     * @var int
     */
    protected $currentKey;

    /**
     * @var bool
     */
    protected $isValid;

    /**
     * @var bool
     */
    protected $enableInstancePoolingOnFinish;

    /**
     * @param \Propel\Runtime\Formatter\ObjectFormatter $formatter
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface $dataFetcher
     */
    public function __construct(AbstractFormatter $formatter, DataFetcherInterface $dataFetcher)
    {
        $this->currentKey = -1;
        $this->formatter = $formatter;
        $this->dataFetcher = $dataFetcher;
        $this->enableInstancePoolingOnFinish = Propel::disableInstancePooling();
    }

    /**
     * @return void
     */
    public function closeCursor(): void
    {
        $this->dataFetcher->close();
        if ($this->enableInstancePoolingOnFinish) {
            Propel::enableInstancePooling();
        }
    }

    /**
     * Returns the number of rows in the resultset
     * Warning: this number is inaccurate for most databases. Do not rely on it for a portable application.
     *
     * @return int Number of results
     */
    public function count(): int
    {
        return $this->dataFetcher->count();
    }

    // Iterator Interface

    /**
     * Gets the current Model object in the collection
     * This is where the hydration takes place.
     *
     * @psalm-suppress ReservedWord
     *
     * @see ObjectFormatter::getAllObjectsFromRow()
     *
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface
     */
    #[\ReturnTypeWillChange]
    public function current(): ActiveRecordInterface
    {
        if (!is_array($this->currentRow)) {
            $this->currentRow = [];
        }

        return $this->formatter->getAllObjectsFromRow($this->currentRow);
    }

    /**
     * Gets the current key in the iterator
     *
     * @psalm-suppress ReservedWord
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key(): int
    {
        return $this->currentKey;
    }

    /**
     * Advances the cursor in the statement
     * Closes the cursor if the end of the statement is reached
     *
     * @return void
     */
    public function next(): void
    {
        $this->currentRow = $this->dataFetcher->fetch();
        $this->currentKey++;
        $this->isValid = (bool)$this->currentRow;
        if (!$this->isValid) {
            $this->closeCursor();
        }
    }

    /**
     * Initializes the iterator by advancing to the first position
     * This method can only be called once (this is a NoRewindIterator)
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return void
     */
    public function rewind(): void
    {
        // check that the hydration can begin
        if ($this->formatter === null) {
            throw new PropelException('The On Demand collection requires a formatter. Add it by calling setFormatter()');
        }
        if ($this->dataFetcher === null) {
            throw new PropelException('The On Demand collection requires a dataFetcher. Add it by calling setDataFetcher()');
        }
        if ($this->isValid !== null) {
            throw new PropelException('The On Demand collection can only be iterated once');
        }

        // initialize the current row and key
        $this->next();
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->isValid;
    }
}
