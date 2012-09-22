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
    const VOTE_DOWN = -1;
    
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
    
    function getVotes($threadID = null, $postID = null)
    {
        $params     = array(':threadID' => $threadID);
        
        $postFilter = '';
        if ($postID) {
            $postFilter        = "AND pv.post_id = :postID";
            $params[':postID'] = $postID;
        }
        
        $threadFilter = '';
        if ($threadID) {
            $threadFilter        = "AND pv.thread_id = :threadID";
            $params[':threadID'] = $threadID;
        }
        
        $query = sprintf("SELECT SUM(pv.up) AS rating,
                                 pv.post_id AS postID,
                                 pv.created_at AS createdAt,
                                 pv.thread_id AS threadID
                          FROM post_votes pv
                          JOIN posts p ON p.id = pv.post_id
                          WHERE 1=1
                          %s
                          %s
                          GROUP BY postID", 
                          $threadFilter,
                          $postFilter);
        
        $result = $this->fetchAll($query, $params);
        $votes  = array();
        
        if ($result) {
            foreach ($result as $k => $v) {
                $votes[$v['postID']] = $v;
            }
        }
        
        return $votes;
    }
    
    function getThreadRatings()
    {
        $votes   = $this->getVotes();
        $ratings = array();
        
        if ($votes) {
            foreach ($votes as $key => $v) {
                if (!isset($ratings[$v['threadID']])) {
                    $ratings[$v['threadID']] = 0;
                }
                
                $ratings[$v['threadID']] += $v['rating'];
            }
        }
        
        return $ratings;
    }
    
    function getVotersByThread($threadID)
    {
        $query = "SELECT pv.user_id AS userID,
                         pv.post_id AS postID
                  FROM post_votes pv
                  WHERE 1=1
                  AND pv.thread_id = :threadID
                  GROUP BY postID";
                  
        $voters = array();
        $result = $this->fetchAll($query, array(':threadID' => $threadID));
        
        if ($result) {
            foreach ($result as $key => $v) {
                if (!isset($voters[$v['postID']])) {
                    $voters[$v['postID']] = array();
                }
                
                $voters[$v['postID']][] = $v['userID'];
            }
        }
        
        return $voters;
    }
}