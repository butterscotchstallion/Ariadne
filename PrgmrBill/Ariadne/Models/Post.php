<?php
/**
 * Post Model
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model;

class Post extends Model
{
    function getAll($forumID, $threadID)
    {
        $query = 'SELECT p.id,
                         p.body,
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
        
        return $this->fetchAll($query, array(':forumID'  => $forumID,
                                             ':threadID' => $threadID));
    }
    
    function getPostCounts()
    {
        $query = "SELECT COUNT(*) as postCount,
                         p.thread_id AS threadID
                  FROM posts p
                  JOIN threads t ON t.id = p.thread_id
                  WHERE 1=1
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
                                body)
              VALUES(:forumID,
                     :threadID,
                     NOW(),
                     :createdBy,
                     :body)";
        
        return $this->save($q, array(':forumID'   => $post['forumID'],
                                     ':threadID'  => $post['threadID'],
                                     ':createdBy' => $post['createdBy'],
                                     ':body'      => $post['body']));
    }
}