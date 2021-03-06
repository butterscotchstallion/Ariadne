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
        $this->vote = new PostVote($connection);
        
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
    
    function getAll($forumID, 
                    $sort      = null, 
                    $direction = self::DIRECTION_DESC)
    {
        $defaultSort = 'postCount';
        $sortable    = array('createdAt',
                             'createdByUser',
                             'title',
                             'postCount');
        
        // Invalid sort
        if (!in_array($sort, $sortable)) {
            $sort = $defaultSort;
        }
        
        // Invalid direction
        if (!in_array($direction, array(self::DIRECTION_DESC, self::DIRECTION_ASC))) {
            $direction = self::DIRECTION_DESC;
        }
        
        $order = sprintf("ORDER BY %s %s", $sort, $direction);
        
        $query = sprintf('SELECT t.id,
                                 t.title,
                                 t.created_at AS createdAt,
                                 u.name AS createdByUser,
                                 u.id AS createdBy,
                                 t.forum_id AS forumID,
                                 (SELECT COUNT(*) AS postCount 
                                  FROM posts p 
                                  WHERE p.thread_id = t.id) as postCount
                          FROM threads t
                          JOIN users u ON u.id = t.created_by
                          WHERE 1=1
                          AND t.forum_id = :forumID
                          GROUP BY t.id
                          %s', 
                          $order);
        
        $result = $this->fetchAll($query, array(':forumID' => $forumID));
        
        if ($result) {
            // Latest posts
            $latestPosts   = $this->post->getLatestPostsFromThreads($forumID);
            $threadRatings = $this->vote->getThreadRatings();
            
            foreach ($result as $key => $t) {
                // Last post
                $result[$key]['lastPost'] = isset($latestPosts[$t['id']]) ? $latestPosts[$t['id']] : array();
                
                // Thread rating (sum of all votes on posts in this thread)
                $result[$key]['rating']   = isset($threadRatings[$t['id']]) ? $threadRatings[$t['id']] : 0;
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