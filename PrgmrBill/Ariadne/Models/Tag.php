<?php
/**
 * Tag Model
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model;

class Tag extends Model
{
    function getPostTags($threadID)
    {
        $query = 'SELECT t.id,
                         t.name,
                         p.id AS postID
                  FROM tags t
                  LEFT JOIN post_tags pt ON pt.tag_id = t.id
                  JOIN posts p ON p.id = pt.post_id
                  JOIN threads th ON th.id = p.thread_id
                  WHERE 1=1
                  AND th.id = :threadID';
        
        $result = $this->fetchAll($query, array(':threadID' => $threadID));
        $tags   = array();
        
        if ($result) {
            foreach ($result as $k => $t) {
                if (!isset($tags[$t['postID']])) {
                    $tags[$t['postID']] = array();
                }
                
                $tags[$t['postID']][] = $t;
            }
        }
        
        return $tags;
    }
    
    function getPostsByTagID($tagID)
    {
        $query = "SELECT f.id AS forumID,
                         f.title AS forumTitle,
                         th.id AS threadID,
                         th.title AS threadTitle,
                         p.id AS postID,
                         t.name AS tagName
                  FROM tags t
                  JOIN post_tags pt ON pt.tag_id = t.id
                  JOIN posts p ON p.id = pt.post_id
                  JOIN forums f ON f.id = p.forum_id
                  JOIN threads th ON th.id = p.thread_id
                  WHERE 1=1
                  AND t.id = :tagID
                  GROUP BY t.id";
        
        $result = $this->fetchAll($query, array(':tagID' => $tagID));
        
        return $result;
    }
}