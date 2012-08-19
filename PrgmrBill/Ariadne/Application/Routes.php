<?php
/**
 * Ariadne routes
 *
 */
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Ariadne\Models\Forum,
    Ariadne\Models\Thread,
    Ariadne\Models\Post;
    
// Forum list
$app->get('/', function(Silex\Application $app, Request $req) {

    $f      = new Forum($app['db']);
    $forums = $f->getAll();
    
    return $app['twig']->render('Main/Index.twig', array(
        'forums' => $forums
    ));
});

// Thread list
$app->get('/f/{id}', function(Silex\Application $app, Request $req, $id = 0) {
 
    $f       = new Forum($app['db']);
    $forum   = $f->getForumByID($id);
     
    if (!$forum) {
        $app->abort(404, "Forum does not exist.");
    }
    
    $t       = new Thread($app['db']);
    $threads = $t->getAll($id);
    
    return $app['twig']->render('Main/Threads.twig', array(
        'forumTitle' => $forum['title'],
        'forumID'    => $forum['id'],
        'threads'    => $threads
    ));
});

// Post list
$app->get('/f/{forumID}/t/{threadID}', function(Silex\Application $app, Request $req, $forumID = 0, $threadID = 0) {
    
    $f       = new Forum($app['db']);
    $forum   = $f->getForumByID($forumID);
    
    if (!$forumID) {
        $app->abort(404, "Forum does not exist.");
    }
    
    $t       = new Thread($app['db']);
    $thread  = $t->getThreadByID($threadID);
    
    if (!$thread) {
        $app->abort(404, "Thread does not exist.");
    }
    
    $p       = new Post($app['db']);
    $posts   = $p->getAll($forumID, $threadID);
    
    return $app['twig']->render('Main/Posts.twig', array(
        'forumTitle'  => $forum['title'],
        'threadTitle' => $thread['title'],
        'postCount'   => count($posts),
        'threadID'    => $threadID,
        'forumID'     => $forumID,
        'posts'       => $posts
    ));
});