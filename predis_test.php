<?php
set_time_limit(0);

require "autoload.php";
Predis\Autoloader::register();

/*
try {
    $redis = new Predis\Client();

    echo "connection success!\n";
} catch (Exception $e) {
    echo "connection FAIL\n";
    echo $e->getmessage();
}

$redis->del('test_set');

$elements_to_add = 100000;

$start_time = microtime(true);
try {
    for($i = 0; $i < $elements_to_add; $i++) {
        $redis->zadd('test_set',rand(0,$i),'iteration_'.$i);
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

$intermediate_time = microtime(true);
$time_to_add = ($intermediate_time - $start_time) * 1000;
var_dump("finished adding ".$elements_to_add." elements in ".$time_to_add);
echo "\n\n\n";

try {
    $results = $redis->zrange('test_set',0,500);
} catch (Exception $e) {
    var_dump($e->getmessage());
}
$final_time = microtime(true);
$fetch_time = ($final_time - $intermediate_time) * 1000;
var_dump("fetched elements in ".$fetch_time);
echo "\n\n\n";

*/

CONST NOT_YET_STORED = "not_yet_stored";

class User {
    var $redis;
    var $id = NOT_YET_STORED;
    var $name;
    var $connections;

    function __construct() {
        $this->redis = new Predis\Client();
        $this->connections = array();
    }

    static function find($id) {
        $user_key = self::getRedisUserKey($id);
        $connection = new Predis\Client();
        $result = $connection->get($user_key);

        $user = self::hydrate($result);
        return $user;
    }

    static function dehydrate($user) {
        $data = array();
        $data['id'] = $user->id;
        $data['name'] = $user->name;
        $data['connections'] = $user->connections;

        return json_encode($data);
    }

    static function hydrate($data) {
        $user_data = json_decode($data);

        $user = new User();
        $user->id = $user_data['id'];
        $user->name = $user_data['name'];
        $user->connections = $user_data['connections'];

        return $user;
    }

    function getRedisUserKey($id) {
        return 'user_'.$id;
    }

    function redisKey() {
        return user::getRedisKey($this->id);
    }

    function store() {
        if ($this->id  == NOT_YET_STORED) {
            $this->id = $this->redis->incr('user_id');
        }

        if (!isset($this->name)) {
            $this->name = "Name number ".$this->id;
        }

        $data = self::dehydrate($this);

        $this->redis->set($this->getRedisUserKey(), $data);

        return $this->id;
    }
}


$user = new User();
$user->name = "tristan";

$user->store();

$user_id = $user->id;

unset($user);

$second_user = User::find($user_id);

var_dump($second_user->name);
