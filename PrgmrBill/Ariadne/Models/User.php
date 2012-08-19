<?php
/**
 * User Model
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model;

class User extends Model
{
    function add(array $info)
    {
        $q = 'INSERT INTO users(name, password, created_at)
              VALUES(:name, :pw, NOW())';
              
        $this->save();
    }
    
    function getAll()
    {
        $q = "SELECT u.name,
                     u.id,
                     u.image
              FROM users u
              WHERE 1=1
              ORDER BY u.name";
              
        return $this->fetchAll($q);
    }
    
    function getUserByName($name) 
    {
        $q = 'SELECT u.id,
                     u.name,
                     u.password,
                     u.created_at AS createdAt
              FROM users u
              WHERE 1=1
              AND u.name = :name';
        
        $user = $this->fetch($q, array(':name' => $name));
        
        return $user;
    }
}