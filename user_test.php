<?php
ini_set('memory_limit', '1024M');

require "User.php";

$user_count = 500;
$activity_count = 10;

$start_time = microtime(true);
$users = array();
$user_objects = array();

$predis_conn = new Predis\Client();

for ($i = 0; $i < $user_count; $i++) {
    $user = new User($predis_conn);
    $user->store();
    $user_objects[] = $user;
    $users[] = $user->id;
    unset($user);
}


$count = 0;
foreach ($user_objects as $user) {
    $act_1 = microtime(true);
    $user->addConnections($users);
    var_dump("per conn: ".(microtime(true) - $act_1) * 1000);
    echo "<br/>";
    $count++;
    $connected_user = $user;
    usleep(10);
}

$user_finish_time = microtime(true);
$user_time = ($user_finish_time - $start_time) * 1000;
var_dump("$user_count users created and connected in: ".$user_time);

foreach ($user_objects as $user) {
    for ($i = 0; $i < $activity_count; $i++) {
        $act_1 = microtime(true);
        $user->addActivity("some activity at: ".microtime(true));
        var_dump("per act: ".(microtime(true) - $act_1) * 1000);
        echo "<br/>";
        usleep(100);
    }
}

$activity_finish_time = microtime(true);
$activity_time = ($activity_finish_time - $user_finish_time) * 1000;
unset($user_objects);
var_dump("$activity_count activities per user created in: ".$activity_time);

echo "<br/><br/><br/>";
$activities_for_user = $connected_user->getActivities();
$fetch_finish_time = microtime(true);
$fetch_time = ($fetch_finish_time - $activity_finish_time) * 1000;
var_dump("fetched activities for one user in: ".$fetch_time);

var_dump(count($activities_for_user)." activities for: ".$connected_user->name."<br/>");
foreach ($activities_for_user as $activity) {
    $owner = $activity->getOwner();
    if ($owner) {
        $name = $owner->name;
    } else {
        $name = "unknown";
    }

    var_dump($name, $activity->value);
    echo "<br/>";
}
