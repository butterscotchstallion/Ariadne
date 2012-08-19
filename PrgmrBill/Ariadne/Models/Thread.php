<?php
/**
 * Thread Model
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model;

class Thread extends Model
{
    function getAll($forumID)
    {
        $query = 'SELECT t.id,
                         t.title,
                         t.forum_id AS forumID
                  FROM threads t
                  WHERE 1=1
                  AND t.forum_id = :forumID';
        
        return $this->fetchAll($query, array(':forumID' => $forumID));
    }
    
    function getThreadCounts()
    {
        $query = "SELECT COUNT(*) as threadCount,
                         f.id
                  FROM threads t
                  LEFT JOIN forums f ON f.id = t.forum_id
                  WHERE 1=1
                  GROUP BY f.id";
                  
        $result = $this->fetchAll($query);
        $counts = array();
        
        //print_r($result);
        
        if ($result) {
            foreach ($result as $key => $f) {
                //print_r($f);
                $counts[$f['id']] = $f['threadCount'];
            }
        }
        
        return $counts;
    }
    
    function getThreadByID($id)
    {
        $query = 'SELECT t.id,
                         t.title
                  FROM threads t
                  WHERE 1=1
                  AND t.id = :id';
        
        $thread = $this->fetch($query, array(':id' => $id));
        
        return $thread;
    }
}