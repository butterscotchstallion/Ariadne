<?php
/**
 * Model - all models inherit from this class
 *
 */
namespace Ariadne\Models;

abstract class Model
{
    private $connection;
    
    function __construct($connection)
    {
        $this->connection = $connection;
    }
    
    protected function getConnection()
    {
        return $this->connection;
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
                $result = $this->getConnection()->lastInsertId();
            } else {
                // State changing; return rows affected
                $result = $stmt->rowCount();
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




