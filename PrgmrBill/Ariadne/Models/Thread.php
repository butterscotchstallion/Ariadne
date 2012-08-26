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
    
    function add(array $thread)
    {
        $q = "INSERT INTO threads(forum_id, 
                                  title, 
                                  created_at, 
                                  created_by)
              VALUES(:forumID,
                     :title,
                     NOW(),
                     :createdBy)";
        
        return $this->save($q, array(':forumID'   => $thread['forumID'],
                                     ':title'     => $thread['title'],
                                     ':createdBy' => $thread['createdBy']));
    }
    
    function getAll($forumID, $sort = null)
    {
        $sortable = array('createdAt',
                          'createdByUser',
                          'title',
                          'postCount');
        
        // Invalid sort
        if (!in_array($sort, $sortable)) {
            $sort = 'createdAt';
        }
        
        if ($sort != 'postCount') {
            $order = sprintf("ORDER BY %s DESC", $sort);
        } else {
            $order = '';
        }
        
        $query = sprintf('SELECT t.id,
                                 t.title,
                                 t.created_at AS createdAt,
                                 u.name AS createdByUser,
                                 u.id AS createdBy,
                                 t.forum_id AS forumID
                          FROM threads t
                          JOIN users u ON u.id = t.created_by
                          WHERE 1=1
                          AND t.forum_id = :forumID
                          %s', $order);
        
        $result = $this->fetchAll($query, array(':forumID' => $forumID));
        
        if ($result) {
            // Lookup array of all post counts
            $postCounts    = $this->post->getPostCounts();
            $postCountSort = array();
            
            foreach ($result as $key => $t) {
                // If this value is set, there is at least one post
                // If not, there are no posts in that thread
                $postCount = isset($postCounts[$t['id']]) ? $postCounts[$t['id']] : 0;
                $result[$key]['postCount'] = $postCount;
                
                $postCountSort[$key] = $postCount;
            }
            
            if ($sort == 'postCount') {
                array_multisort($postCountSort, SORT_DESC, $result, SORT_NUMERIC);
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