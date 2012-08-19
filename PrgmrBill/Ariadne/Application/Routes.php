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
    Ariadne\Models\Post,
    Ariadne\Models\User;
    
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
})->assert('id', "\d+");

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
})->assert('forumID',  "\d+")
  ->assert('threadID', "\d+");

// New post
$app->post('/f/{forumID}/t/{threadID}/reply', function(Silex\Application $app, Request $req, $forumID, $threadID) {
    
    $post               = $req->get('reply');
    
    $user              = $app['session']->get('user');
    $post['createdBy'] = $user['id'];
    
    $p      = new Post($app['db']);
    $postID = $p->add($post);
    
    return $app->redirect(sprintf('/f/%d/t/%d#post%d', $forumID, $threadID, $postID));
    
})->assert('forumID', "\d+")
  ->assert('threadID', "\d+");

// User profile
$app->get('/u/{id}', function(Silex\Application $app, Request $req, $id = 0) {
    
    $u    = new User($app['db']);
    $user = $u->getUserByID($id);
    
    if (!$user) {
        return $app->abort(404, 'User not found'); 
    }
    
    $t        = new Thread($app['db']);
    $threads = $t->getThreadTitlesByAuthor($id);
    
    return $app['twig']->render('User/Profile.twig', array(
        'user'    => $user,
        'threads' => $threads
    ));
})->assert('id', "\d+");

// Sign in
$app->get('/u/sign-in', function(Silex\Application $app, Request $req) {
    
    return $app['twig']->render('User/SignIn.twig', array(
        
    ));
});

// Sign out
$app->post('/u/sign-out', function(Silex\Application $app, Request $req) {
    
    if ($app['session']->has('user')) {
        $app['session']->remove('user');
    }
    
    return $app->redirect('/u/sign-in');
});

// Authenticate
$app->post('/u/sign-in', function(Silex\Application $app, Request $req) {
    $user     = $req->get('user');
    $name     = isset($user['name'])     ? $user['name']     : '';
    $password = isset($user['password']) ? $user['password'] : '';
    
    if ($name && $password) {        
        // Find user
        $userModel = new User($app['db']);
        $user      = $userModel->getUserByName($name);
        
        if ($user) {
            // Check password
            require sprintf('%s/phpass/PasswordHash.php', VENDOR_ROOT);
            $hasher  = new \PasswordHash(8, false);
            
            //var_dump($hasher->HashPassword($password));
            //die;
            
            $pwMatch = $hasher->CheckPassword($password, $user['password']);
            
            //var_dump($pwMatch);
            //die;
            
            if ($pwMatch) {
                $app['session']->set('user', $user);
                return $app->redirect(sprintf('/u/%d', $user['id']));
            } 
        }         
    } 
    
    return $app->redirect('/u/sign-in');
});

$app['twig']->addGlobal('signedIn', $app['session']->get('user'));