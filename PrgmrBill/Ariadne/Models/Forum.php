<?php
/**
 * Forum Model
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model,
    Ariadne\Models\Thread;

class Forum extends Model
{
    private $thread;
    
    function __construct($connection)
    {
        $this->thread = new Thread($connection);
        
        parent::__construct($connection);
    }
    
    function add(array $forum)
    {
        $q = "INSERT INTO forums(title, 
                                 created_at, 
                                 created_by)
              VALUES(:title,
                     NOW(),
                     :createdBy)";
        
        return $this->save($q, array(':title'     => $forum['title'],
                                     ':createdBy' => $forum['createdBy']));
    }
    
    function getAll()
    {
        $query = 'SELECT f.id,
                         f.title,
                         f.display_order as displayOrder
                  FROM forums f 
                  ORDER BY f.display_order';
        
        $forums = $this->fetchAll($query);
        
        if ($forums) {
            $threadCounts = $this->thread->getThreadCounts();
            
            foreach ($forums as $key => $f) {
                $forums[$key]['threadCount'] = $threadCounts[$f['id']];
            }
        }
        
        return $forums;
    }
    
    function getForumByID($id)
    {
        $query = 'SELECT f.id,
                         f.title,
                         f.display_order as displayOrder
                  FROM forums f 
                  WHERE 1=1
                  AND f.id = :id';
        
        $forum = $this->fetch($query, array(':id' => $id));
        
        return $forum;
    }
}