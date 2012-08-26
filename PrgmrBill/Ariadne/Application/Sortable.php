<?php
/**
 * Sortable - builds an order by string based on available
 * sortable columns
 *
 */
namespace Ariadne\Application;

class Sortable
{
    private $columns = array();
    
    function __construct(array $columns)
    {
        $this->columns = $columns;
    }
    
    function getOrderBy($column, $direction = 'DESC', $defaultColumn = null)
    {
        $dir = strtoupper($direction);
        
        if ($dir == 'DESC') {
            $dir = '';
        } else {
            $dir = 'DESC';
        }
        
        if (in_array($column, $this->columns) || $defaultColumn) {
            // If default specified, use that when an invalid sort
            // is specified
            if ($defaultColumn) {
                $column = $defaultColumn;
            }
            
            $orderBy = sprintf('ORDER BY %s %s', $column, $dir);
        } else {
            $orderBy = '';
        }
        
        return $orderBy;
    }
}




