<?php
/**
 * Permission model - retrieves information about user permissions
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model;

class Permission extends Model
{
    const ADD_FORUM  = 'Add Post';
    const ADD_THREAD = 'Add Forum';
    const ADD_POST   = 'Add Post';
    const VOTE_POST  = 'Vote Post';
    
    function getPermissionsByUserID($userID)
    {
        $query = 'SELECT p.name
                  FROM permissions p
                  JOIN user_permissions up ON up.permission_id = p.id
                  WHERE 1=1
                  AND up.user_id = :userID';
                  
        $result = $this->fetchAll($query, array(':userID' => $userID));
        $perms  = array();
        
        // Flatten 
        if ($result) {
            foreach ($result as $k => $p) {
                $perms[] = $p['name'];
            }
        }
        
        return $perms;
    }
}
