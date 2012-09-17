<?php
/**
 * PostVote model
 *
 */
namespace Ariadne\Models;

use Ariadne\Models\Model;

class PostVote extends Model
{   
    const VOTE_UP   = 1;
    const VOTE_DOWN = 0;
    
    function vote(array $vote)
    {
        $postID   = isset($vote['postID'])   ? (int) $vote['postID']   : 0;
        $threadID = isset($vote['threadID']) ? (int) $vote['threadID'] : 0;
        $userID   = isset($vote['userID'])   ? (int) $vote['userID']   : 0;
        $up       = isset($vote['up'])       ? (int) $vote['up']       : 0;
        
        $query = "INSERT INTO post_votes (post_id, 
                                          user_id, 
                                          thread_id,
                                          up, 
                                          created_at)
                  VALUES (:postID, :userID, :threadID, :up, NOW())";
        
        $result = $this->save($query, array(':postID'   => $postID,
                                            ':userID'   => $userID,
                                            ':threadID' => $threadID,
                                            ':up'       => $up));
             
        return $result;
    }
    
    function getVotes($threadID, $postID = null)
    {
        $postFilter = '';
        $params     = array(':threadID' => $threadID);
        
        if ($postID) {
            $postFilter = "AND pv.post_id = :postID";
            $params[':postID'] = $postID;
        }
        
        $query = sprintf("SELECT SUM(pv.up) AS rating,
                                 pv.post_id AS postID,
                                 pv.created_at AS createdAt
                          FROM post_votes pv
                          JOIN posts p ON p.id = pv.post_id
                          WHERE 1=1
                          AND pv.thread_id = :threadID
                          %s
                          GROUP BY postID", $postFilter);
        
        $result = $this->fetchAll($query, $params);
        $votes  = array();
        
        if ($result) {
            foreach ($result as $k => $v) {
                $votes[$v['postID']] = $v;
            }
        }
        
        return $votes;
    }
}