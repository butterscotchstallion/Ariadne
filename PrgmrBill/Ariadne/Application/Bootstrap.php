<?php
/**
 * Ariadne bootstrap
 *
 */ 
ob_start();
error_reporting(-1);
ini_set('display_errors', 1);
date_default_timezone_set('America/New_York');

require 'Config.php';

require sprintf('%s/autoload.php', VENDOR_ROOT);

$app          = new Silex\Application();
$app['debug'] = true;

// Get DB connection
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options'   => array(
        'driver'        => 'pdo_mysql',
        'host'          => DB_HOST,
        'dbname'        => DB_NAME,
        'user'          => DB_USER,
        'password'      => DB_PASSWORD,
        'driverOptions' => array(
                        1002 => 'SET NAMES utf8'
        )
    )
));

// Register twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => VIEW_ROOT
));


$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());

// Forum
define('FORUM_TITLE_MIN_LENGTH', 10);
define('FORUM_TITLE_MAX_LENGTH', 255);

// Post
define('POST_MIN_LENGTH', 10);
define('POST_MAX_LENGTH', 64000);
define('POST_TITLE_MAX_LENGTH', 255);

require 'Routes.php';