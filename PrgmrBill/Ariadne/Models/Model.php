<?php
/**
 * Model - all models inherit from this class
 *
 */
namespace Ariadne\Models;

use Ariadne\Application\Sortable;

abstract class Model
{
    const DIRECTION_DESC = 'DESC';
    const DIRECTION_ASC  = 'ASC';
    
    private $connection;
    
    function __construct($connection)
    {
        $this->connection = $connection;
        $this->sortable   = new Sortable();
    }
    
    protected function getConnection()
    {
        return $this->connection;
    }
    
    private function getSortable()
    {
        return $this->sortable;
    }
    
    protected function getOrderBy($sort, $direction = 'DESC', $defaultColumn = null)
    {
        return $this->getSortable()->getOrderBy($sort, $direction, $defaultColumn);
    }
    
    protected function fetch($query, array $params = array())
    {
        $stmt = $this->getStatement($query, $params);
        
        if ($stmt) {
            $result = $stmt->fetch();
            
            return $result;
        }
        
        return $stmt;
    }
    
    protected function fetchAll($query, array $params = array())
    {
        $stmt = $this->getStatement($query, $params);
        
        if ($stmt) {
            $stmt->setFetchMode(\PDO::FETCH_ASSOC);
            
            $result = $stmt->fetchAll();
            
            return $result;
        }
        
        return $stmt;
    }
    
    protected function save($query, array $params = array()) 
    {
        $stmt   = $this->getStatement($query, $params);
        $result = $stmt;
        
        if ($stmt) {
            // Insert statement; return primary key
            if (strpos(strtolower($query), 'insert') === 0) {
                $result = (int) $this->getConnection()->lastInsertId();
            } else {
                // State changing; return rows affected
                $result = (int) $stmt->rowCount();
            }
        } 
        
        return $result;
    }
    
    private function getStatement($query, array $params = array())
    {
        $stmt = $this->getConnection()->prepare($query);
        
        if ($params) {
            $result = $stmt->execute($params);
        } else {
            $result = $stmt->execute();
        }
        
        return $stmt;
    }
    
    
}




