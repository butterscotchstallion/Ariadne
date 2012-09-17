<?php
/**
 * Post Model
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model;

class Post extends Model
{
    private $tag;
    private $vote;
    
    function __construct($connection)
    {
        $this->tag  = new Tag($connection);
        $this->vote = new PostVote($connection);
        
        parent::__construct($connection);
    }
    
    function getAll($forumID, $threadID)
    {
        $query = 'SELECT p.id,
                         p.body,
                         p.bump,
                         DATE_FORMAT(p.created_at, "%b %d %Y %h:%s %p") AS createdAt,
                         p.created_by AS createdBy,
                         p.forum_id AS forumID,
                         p.thread_id AS threadID,
                         f.title AS forumTitle,
                         u.name AS createdByUser,
                         u.image AS createdByUserImage,
                         t.title AS threadTitle
                  FROM posts p
                  JOIN forums f ON f.id = p.forum_id
                  JOIN users u ON u.id = p.created_by
                  JOIN threads t ON t.id = p.thread_id
                  WHERE 1=1
                  AND p.forum_id  = :forumID
                  AND p.thread_id = :threadID';
        
        $posts = $this->fetchAll($query, array(':forumID'  => $forumID,
                                               ':threadID' => $threadID));
                                               
        if ($posts) {
            $tags  = $this->tag->getPostTags($threadID);
            $votes = $this->vote->getVotes($threadID);
            
            //print_r($votes);
            
            foreach ($posts as $key => $p) {
                $posts[$key]['tags']   = isset($tags[$p['id']]) ? $tags[$p['id']] : array(); 
                $posts[$key]['rating'] = isset($votes[$p['id']]['rating']) ? $votes[$p['id']]['rating'] : 0; 
            }
        }
        
        return $posts;
    }
    
    function getLatestPostsFromThreads($forumID)
    {
        $query = "SELECT p.created_by AS userID,
                         u.name,
                         p.id,
                         t.id AS threadID
                  FROM posts p
                  JOIN users u ON u.id = p.created_by
                  JOIN threads t ON t.id = p.thread_id
                  WHERE 1=1
                  AND p.forum_id = :forumID
                  ORDER BY p.created_at DESC";
        
        $result = $this->fetchAll($query, array(':forumID' => $forumID));
        $posts  = array();
        
        if ($result) {
            /**
             * Create lookup array using 
             * threadID as the key
             *
             */
            foreach ($result as $key => $p) {
                $posts[$p['threadID']] = $p;
            }
        }
        
        return $posts;
    }
    
    function getOriginalPostUser($forumID, $threadID)
    {
        $query = "SELECT u.name
                  FROM users u
                  JOIN threads t ON t.created_by = u.id
                  JOIN posts   p ON p.thread_id  = t.id
                  WHERE 1=1
                  AND p.is_first_post = 1
                  AND t.forum_id      = :forumID
                  AND t.id            = :threadID";
        
        $user = $this->fetch($query, array(':forumID'  => $forumID,
                                           ':threadID' => $threadID));
                                           
        return $user ? $user['name'] : '';
    }
    
    /**
     * Gets the number of posts in a thread. We don't count
     * the original post or posts which have the "bump" checkbox
     * unchecked.
     *
     */
    function getPostCounts()
    {
        $query = "SELECT COUNT(*)    AS postCount,
                         p.thread_id AS threadID
                  FROM posts p
                  JOIN threads t ON t.id = p.thread_id
                  WHERE 1=1
                  AND p.is_first_post = 0
                  AND p.bump          = 1
                  GROUP BY p.thread_id";
        
        $result = $this->fetchAll($query);
        $counts = array();
        
        if ($result) {
            foreach ($result as $key => $p) {
                $counts[$p['threadID']] = $p['postCount'];
            }
        }
        
        return $counts;
    }
    
    function add(array $post)
    {
        $q = "INSERT INTO posts(forum_id, 
                                thread_id, 
                                created_at, 
                                created_by,
                                body,
                                is_first_post,
                                bump)
              VALUES(:forumID,
                     :threadID,
                     NOW(),
                     :createdBy,
                     :body,
                     :isFirstPost,
                     :bump)";
        
        $isFirstPost = isset($post['isFirstPost']) ? $post['isFirstPost'] : 0;
        $bump        = isset($post['bump']) ? (int) $post['bump'] : 0;
        
        return $this->save($q, array(':forumID'     => $post['forumID'],
                                     ':threadID'    => $post['threadID'],
                                     ':createdBy'   => $post['createdBy'],
                                     ':body'        => $post['body'],
                                     ':isFirstPost' => $isFirstPost,
                                     ':bump'        => $bump));
    }
}