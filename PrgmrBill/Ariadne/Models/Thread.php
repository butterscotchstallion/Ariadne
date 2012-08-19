<?php
/**
 * Thread Model
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model;

class Thread extends Model
{
    private $post;
    
    function __construct($connection)
    {
        $this->post = new Post($connection);
        
        parent::__construct($connection);
    }
    
    function getAll($forumID)
    {
        $query = 'SELECT t.id,
                         t.title,
                         t.forum_id AS forumID
                  FROM threads t
                  WHERE 1=1
                  AND t.forum_id = :forumID';
        
        $result = $this->fetchAll($query, array(':forumID' => $forumID));
        
        if ($result) {
            // Lookup array of all post counts
            $postCounts = $this->post->getPostCounts();
            
            foreach ($result as $key => $t) {
                // If this value is set, there is at least one post
                // If not, there are no posts in that thread
                $result[$key]['postCount'] = isset($postCounts[$t['id']]) ? $postCounts[$t['id']] : 0;
            }
        }
        
        return $result;
    }
    
    function getThreadTitlesByAuthor($userID)
    {
        $q = 'SELECT t.id,
                     t.title,
                     t.created_at AS createdAt,
                     t.forum_id AS forumID
              FROM threads t
              WHERE 1=1
              AND t.created_by = :userID
              ORDER BY t.created_at';
              
        return $this->fetchAll($q, array(':userID' => $userID));
    }
    
    /**
     * Used to determine how many threads exist in a forum
     *
     */
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
        
        if ($result) {
            foreach ($result as $key => $f) {
                $counts[$f['id']] = $f['threadCount'];
            }
        }
        
        return $counts;
    }
    
    function getCreatedThreadCount($userID)
    {
        $q = "SELECT COUNT(*) AS threadCount
              FROM threads t
              WHERE 1=1
              AND t.created_by = :userID";
              
        $result = $this->fetch($q, array(':userID' => (int) $userID));
        
        return $result ? $result['threadCount'] : 0;
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