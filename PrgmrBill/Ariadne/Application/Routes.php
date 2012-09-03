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
    Ariadne\Models\User,
    Ariadne\Models\Permission,
    Ariadne\Models\Tag;
    
    
$mustBeSignedIn = function (Request $request) use ($app) {
    if (!$app['session']->has('user')) {
        return $app->redirect('/u/sign-in');
    }
};

$flash = function(Silex\Application $app, $msg) {
    $app['session']->setFlash('notice', $msg);
};

$hasPermission = function($permission, array $permissions) {
    return in_array($permission, $permissions);
};

$canAddForums = function(array $permissions) use ($hasPermission) {
    return $hasPermission(Permission::ADD_FORUM, $permissions);
}; 

$canAddThreads = function(array $permissions) use ($hasPermission) {
    return $hasPermission(Permission::ADD_THREAD, $permissions);
}; 

$canAddPosts = function(array $permissions) use ($hasPermission) {
    return $hasPermission(Permission::ADD_POST, $permissions);
}; 

$checkPermissions = function($permissionTest, $redirect) use($app, $flash) {
    if (!$permissionTest) {
        return $app->redirect($redirect);
    }
};

$user = $app['session']->get('user') ? $app['session']->get('user') : array('permissions' => array());

//print_r($user);

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

// New forum (GET)
$app->get('/f/new', function(Silex\Application $app, Request $req) {
    
    return $app['twig']->render('Main/NewForum.twig', array(
        
    ));
});

// New forum (POST)
$app->post('/f/new', function(Silex\Application $app, Request $req) 
                     use($flash) {
    
    $user               = $app['session']->get('user');
    $forum              = $req->get('forum');
    $forum['createdBy'] = $user['id'];
    
    // Validate
    $constraint = new Assert\Collection(array(
        'title' => array(new Assert\NotBlank(), 
                        new Assert\MinLength(FORUM_TITLE_MIN_LENGTH),
                        new Assert\MaxLength(FORUM_TITLE_MAX_LENGTH)),
        'createdBy' => new Assert\Regex("#\d+#"),
    ));
    
    $errors = $app['validator']->validateValue($forum, $constraint);
    
    if (count($errors) > 0) {
        $app['session']->set('errors', $errors);
        return $app->redirect(sprintf('/f/new?errors=1', $forumID));
    } else {
        $app['session']->set('errors', false);
    }
    
    // Proceed!
    $f       = new Forum($app['db']);
    $forumID = $f->add($forum);
    
    // Problem creating forum
    if (!$forumID) {
        $app['session']->set('errors', array('Error creating forum'));
        return $app->redirect('/f/new');
    } else {        
        return $app->redirect(sprintf('/f/%d', $forumID));
    }
    
})->before($mustBeSignedIn)
  ->before($checkPermissions($canAddForums($user['permissions']), '/f/new'));
  
// Thread list
$app->get('/f/{id}', function(Silex\Application $app, Request $req, $id = 0) {
 
    $f       = new Forum($app['db']);
    $forum   = $f->getForumByID($id);
     
    if (!$forum) {
        $app->abort(404, "Forum does not exist.");
    }
    
    $t       = new Thread($app['db']);
    $threads = $t->getAll($id, $req->get('sort'));
    
    return $app['twig']->render('Main/Threads.twig', array(
        'forumTitle'  => $forum['title'],
        'forumID'     => $forum['id'],
        'threadID'    => 0,
        'threadTitle' => '',
        'threads'     => $threads
    ));
})->assert('id', "\d+");

// New Thread (GET)
$app->get('/f/{forumID}/t/new', function(Silex\Application $app, 
                                                    Request $req, 
                                                    $forumID = 0) {
    $f       = new Forum($app['db']);
    $forum   = $f->getForumByID($forumID);
     
    if (!$forum) {
        $app->abort(404, "Forum does not exist.");
    }
    
    return $app['twig']->render('Main/NewThread.twig', array(
        'forumTitle'  => $forum['title'],
        'forumID'     => $forum['id']
    ));
    
})->assert('forumID', "\d+");

// New thread (POST)
$app->post('/f/{forumID}/t/new', function(Silex\Application $app, Request $req, $forumID) 
                                 use($flash) {
    
    $thread              = $req->get('thread');
    $user                = $app['session']->get('user');
    $thread['createdBy'] = $user['id'];
    
    // Validate
    $constraint = new Assert\Collection(array(
        'title' => array(new Assert\NotBlank(), 
                        new Assert\MinLength(POST_MIN_LENGTH),
                        new Assert\MaxLength(POST_TITLE_MAX_LENGTH)),
        'body'     => array(new Assert\NotBlank(), 
                        new Assert\MinLength(POST_MIN_LENGTH),
                        new Assert\MaxLength(POST_MAX_LENGTH)),
        'forumID'  => new Assert\Regex("#\d+#"),
        'createdBy' => new Assert\Regex("#\d+#"),
    ));
    
    $errors = $app['validator']->validateValue($thread, $constraint);
    
    if (count($errors) > 0) {
        $app['session']->set('errors', $errors);
        return $app->redirect(sprintf('/f/%d/t/new?errors=1', $forumID));
    } else {
        $app['session']->set('errors', false);
    }
    
    // Proceed!
    // 1. Add thread
    // 2. Add post to resulting thread
    $t        = new Thread($app['db']);
    $threadID = $t->add($thread);
    
    // Problem creating thread
    if (!$threadID) {
        $app['session']->set('errors', array('Error creating thread'));
        return $app->redirect(sprintf('/f/%d', $forumID));
    } else {
        // Thread created; create post and add it to that thread
        $p      = new Post($app['db']);
        $postID = $p->add(array('forumID'     => $forumID,
                                'threadID'    => $threadID,
                                'createdBy'   => $thread['createdBy'],
                                'body'        => $thread['body'],
                                'isFirstPost' => 1));
        
        if (!$postID) {
            $app['session']->set('errors', array('Error creating thread'));
        } else {
            $flash($app, 'Nice post!');
        }   
        
        return $app->redirect(sprintf('/f/%d/t/%d', $forumID, $threadID));
    }
    
})->assert('forumID', "\d+")
  ->before($mustBeSignedIn)
  ->before($checkPermissions($canAddThreads($user['permissions']), '/'));
  
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
    
    $user    = $p->getOriginalPostUser($forumID, $threadID);
    
    return $app['twig']->render('Main/Posts.twig', array(
        'forumTitle'     => $forum['title'],
        'threadTitle'    => $thread['title'],
        'postCount'      => count($posts),
        'threadID'       => $threadID,
        'forumID'        => $forumID,
        'posts'          => $posts,
        'originalPoster' => $user
    ));
    
})->assert('forumID',  "\d+")
  ->assert('threadID', "\d+");

// New post (POST)
$app->post('/f/{forumID}/t/{threadID}/reply', function(Silex\Application $app, Request $req, $forumID, $threadID) {
    
    $post               = $req->get('reply');
    
    $user              = $app['session']->get('user');
    $post['createdBy'] = $user['id'];
    
    // Validate
    $constraint = new Assert\Collection(array(
        'body' => array(new Assert\NotBlank(), 
                        new Assert\MinLength(POST_MIN_LENGTH),
                        new Assert\MaxLength(POST_MAX_LENGTH)),
        'forumID'  => new Assert\Regex("#\d+#"),
        'threadID' => new Assert\Regex("#\d+#"),
        'bump'     => new Assert\Regex("#[0,1]#"),
        'createdBy' => new Assert\Regex("#\d+#"),
    ));
    
    $errors = $app['validator']->validateValue($post, $constraint);
    
    if (count($errors) > 0) {
        $app['session']->set('errors', $errors);
        return $app->redirect(sprintf('/f/%d/t/%d#reply', $forumID, $threadID));
    } else {
        $app['session']->set('errors', false);
    }
    
    // Quoting another post
    $replaceLink  = sprintf("<a href='/f/%d/t/%d/#post%d'>\\0</a>", $forumID, $threadID, $postID);
    $post['body'] = preg_replace("#^>>(\d+)#", $replaceLink, $post['body']);
    
    //echo '<pre>';
    //print_r($post);
    //die;
    
    // Proceed
    $p      = new Post($app['db']);
    $postID = $p->add($post);
    
    return $app->redirect(sprintf('/f/%d/t/%d#post%d', $forumID, $threadID, $postID));
    
})->assert('forumID', "\d+")
  ->assert('threadID', "\d+")
  ->before($mustBeSignedIn)
  ->before($checkPermissions($canAddPosts($user['permissions']), '/'));

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
        'user'        => $user,
        'threads'     => $threads,
        'threadID'    => 0,
        'threadTitle' => '',
        'forumID'     => 0,
        'forumTitle'  => ''
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
            
            $pwMatch = $hasher->CheckPassword($password, $user['password']);
            
            if ($pwMatch) {
                // No reason to store this
                unset($user['password']);
                
                $app['session']->set('user', $user);
                return $app->redirect(sprintf('/u/%d', $user['id']));
            } 
        }         
    } 
    
    return $app->redirect('/u/sign-in');
});

// Posts with this tag
$app->get('/t/{tagID}', function(Silex\Application $app, 
                                                   Request $req, 
                                                   $tagID = 0) {
    
    $t     = new Tag($app['db']);
    $posts = $t->getPostsByTagID($tagID); 
    
    return $app['twig']->render('Main/TagList.twig', array(
        'posts'     => $posts,
        'postCount' => count($posts)
    ));
    
})->assert('tagID', "\d+");

// Permissions
$app['twig']->addGlobal('signedIn', $app['session']->get('user'));
$app['twig']->addGlobal('canAddForums', $canAddForums($user['permissions']));
$app['twig']->addGlobal('canAddThreads', $canAddThreads($user['permissions']));
$app['twig']->addGlobal('canAddPosts', $canAddPosts($user['permissions']));

$app['twig']->addGlobal('user', $app['session']->get('user'));
$app['twig']->addGlobal('errors', $app['session']->get('errors'));
$app['twig']->addGlobal('message', $app['session']->get('message'));
