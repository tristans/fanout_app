<?php

require "User.php";
$user_id = 5700;

$user = User::find($user_id);
$time_start = microtime(true);
$activities_for_user = $user->getActivities();

$total_time = (microtime(true) - $time_start) * 1000;

var_dump("got activities in: ".$total_time);

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
