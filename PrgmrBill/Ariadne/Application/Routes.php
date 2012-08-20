<?php
/**
 * Ariadne routes
 *
 */
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\Validator\Constraints as Assert,
    Ariadne\Models\Forum,
    Ariadne\Models\Thread,
    Ariadne\Models\Post,
    Ariadne\Models\User;
    
    
$mustBeSignedIn = function (Request $request) use ($app) {
    if (!$app['session']->has('user')) {
        return $app->redirect('/u/sign-in');
    }
};

// Forum list
$app->get('/', function(Silex\Application $app, Request $req) {

    $f      = new Forum($app['db']);
    $forums = $f->getAll();
    
    return $app['twig']->render('Main/Index.twig', array(
        'forums'      => $forums,
        'forumTitle'  => '',
        'forumID'     => '',
        'threadID'    => 0,
        'threadTitle' => '',
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
        'forumTitle'  => $forum['title'],
        'forumID'     => $forum['id'],
        'threadID'    => 0,
        'threadTitle' => '',
        'threads'     => $threads
    ));
})->assert('id', "\d+");

// New thread
$app->post('/f/{forumID}/t/new', function(Silex\Application $app, Request $req, $forumID) {
    
    $thread            = $req->get('thread');
    
    $user              = $app['session']->get('user');
    $post['createdBy'] = $user['id'];
    
    // Validate
    $constraint = new Assert\Collection(array(
        'title' => array(new Assert\NotBlank(), 
                        new Assert\MinLength(10),
                        new Assert\MaxLength(255)),
        'forumID'  => new Assert\Regex("#\d+#"),
        'createdBy' => new Assert\Regex("#\d+#"),
    ));
    
    $errors = $app['validator']->validateValue($thread, $constraint);
    
    if (count($errors) > 0) {
        $app['session']->set('errors', $errors);
        return $app->redirect(sprintf('/f/%d/t/new', $forumID, $threadID));
    } else {
        $app['session']->set('errors', false);
    }
    
    // Proceed
    $p      = new Post($app['db']);
    $postID = $p->add($post);
    
    return $app->redirect(sprintf('/f/%d/t/%d#post%d', $forumID, $threadID, $postID));
    
})->assert('forumID', "\d+")
  ->assert('threadID', "\d+")
  ->before($mustBeSignedIn);
  
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
    
    //echo '<pre>';
    //print_r($post);
    //die;
    
    // Validate
    $constraint = new Assert\Collection(array(
        'body' => array(new Assert\NotBlank(), 
                        new Assert\MinLength(10),
                        new Assert\MaxLength(64000)),
        'forumID'  => new Assert\Regex("#\d+#"),
        'threadID' => new Assert\Regex("#\d+#"),
        'createdBy' => new Assert\Regex("#\d+#"),
    ));
    
    $errors = $app['validator']->validateValue($post, $constraint);
    
    if (count($errors) > 0) {
        $app['session']->set('errors', $errors);
        return $app->redirect(sprintf('/f/%d/t/%d#reply', $forumID, $threadID));
    } else {
        $app['session']->set('errors', false);
    }
    
    // Proceed
    $p      = new Post($app['db']);
    $postID = $p->add($post);
    
    return $app->redirect(sprintf('/f/%d/t/%d#post%d', $forumID, $threadID, $postID));
    
})->assert('forumID', "\d+")
  ->assert('threadID', "\d+")
  ->before($mustBeSignedIn);

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
        'threads' => $threads,
        'threadID' => 0,
        'threadTitle' => '',
        'forumID' => 0,
        'forumTitle' => ''
    ));
    
})->assert('id', "\d+");

// Sign in
$app->get('/u/sign-in', function(Silex\Application $app, Request $req) {
    
    return $app['twig']->render('User/SignIn.twig', array(
        
    ));
});

// Sign out
$app->get('/u/sign-out', function(Silex\Application $app, Request $req) {
    
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
$app['twig']->addGlobal('user', $app['session']->get('user'));
$app['twig']->addGlobal('errors', $app['session']->get('errors'));