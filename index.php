<?php
/** Basic REST endpoint for our app */
require "init.php";

$app = new \Slim\Slim();

$app->get('/user/:id', function ($id) {
    $user = User::find($id);
    echo User::dehydrate($user);
});

$app->get('/users', function () {
    echo json_encode(User::findAll());
});

$app->post('/user', function() use ($app) {
    $user = new User();
    $user->name = $app->request()->post('name');
    $user->store();

    echo $user->dehydrate();
});

$app->get('/user/:id/connections', function ($id) {
    $user = User::find($id);
    $connections = $user->getConnections();
    $connected_users = array();
    foreach ($connections as $a_user) {
        $connected_users[] = User::find($a_user);
    }
    echo json_encode($connected_users);
});

$app->get('/user/:id/reverse_connections', function ($id) {
    $user = User::find($id);
    $reverseConnections = $user->getreverseConnections();
    $connected_users = array();
    foreach ($reverseConnections as $a_user) {
        $connected_users[] = User::find($a_user);
    }
    echo json_encode($connected_users);
});

$app->post('/user/:id/connect/:connected_id', function ($id, $connected_id) {
    $user = User::find($id);
    $user->addConnection($connected_id);

    echo true;
});

$app->post('/user/:id/disconnect/:connected_id', function ($id, $connected_id) {
    $user = User::find($id);
    $user->removeConnection($connected_id);

    echo true;
});

$app->get('/user/:id/activity(/:offset(/:limit))', function ($id, $offset = 0, $limit = 500) {
    $user = User::find($id);
    echo json_encode($user->getActivities($offset, $limit));
});

$app->post('/user/:id/activity', function($id) use ($app) {
    $user = User::find($id);
    $activity = $user->addActivity($app->request()->post('activity_value'));

    echo $activity->dehydrate();
});

$app->run();
