<?php
/**
 * PostVote model
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model;

class PostVote extends Model
{
    function vote($postID, $userID, $up)
    {
        $query = "INSERT INTO post_votes (post_id, user_id, up, created_at)
                  VALUES (:postID, :userID, :up, NOW())";
        
        $result = $this->save($query, array(':postID' => $postID,
                                            ':userID' => $userID,
                                            ':up'     => (int) $up));
             
        return $result;
    }
    
    function getVotes($postID)
    {
        $query = "SELECT SUM(pv.up) AS rating,
                         pv.post_id AS postID,
                         pv.created_at AS createdAt
                  FROM post_votes pv
                  JOIN posts p ON p.id = pv.post_id
                  WHERE 1=1
                  AND pv.post_id = :postID";
                  
        $result = $this->fetchAll($query, array(':postID' => (int) $postID));
        $votes  = array();
        
        if ($result) {
            foreach ($result as $k => $v) {
                $votes[$v['postID']] = $v;
            }
        }
        
        return $votes;
    }
}