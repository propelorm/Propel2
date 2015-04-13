/**
* Filter the query by <?=$fieldName?> column
*
* Example usage:
* <code>
    * $query->filterBy<?=$phpName?>(1234); // WHERE <?=$fieldName?> = 1234
    * $query->filterBy<?=$phpName?>(array(12, 34)); // WHERE <?=$fieldName?> IN (12, 34)
    * $query->filterBy<?=$phpName?>(array('min' => 12)); // WHERE <?=$fieldName?> > 12
    * </code>
*
* @param     mixed $value The value to use as filter.
*              Use scalar values for equality.
*              Use array values for in_array() equivalent.
*              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
* @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
*
* @return $this|<?=$childClassName?> The current query, for fluid interface
*/
public function filterBy<?=$phpName?>($value = null, $comparison = null)
{
    return $this->use<?=$tablePhpName?>Query()->filterBy<?=$phpName?>($value, $comparison)->endUse();
}

/**
* Adds an ORDER BY clause to the query
* Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
* Infers $column and $order from $columnName and some optional arguments
* Examples:
*   $c->orderBy('Book.CreatedAt')
*    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
*   $c->orderBy('Book.CategoryId', 'desc')
*    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
*
* @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
*
* @return $this|ModelCriteria The current object, for fluid interface
*/
public function orderBy<?=$phpName?>($order = Criteria::ASC)
{
    return $this->use<?=$tablePhpName?>Query()->orderBy<?=$phpName?>($order)->endUse();
}
