<?php
require_once "init.php";
require_once "Activity.php";

class User {
    var $redis;
    var $id = NOT_YET_STORED;
    var $name;

    function __construct($predis_conn) {
        $this->redis = $predis_conn;
        $this->name = null;
        $this->id = NOT_YET_STORED;
    }

    static function find($id, $connection = null, $dehydrated = false) {
        $user_key = self::getRedisUserKey($id);
        
        if (!isset($connection)) {
            $connection = new Predis\Client();
        }
        try {
            $result = $connection->get($user_key);

            if ($dehydrated) {
                return $result;
            }

            $user = self::hydrate($result, $connection);
            return $user;
        } catch (Exception $e) {
            var_dump($e->getmessage()." on ".$e->getLine());
        }
        
        return NOT_YET_STORED;
    }

    static function findAll($dehydrated = false) {
        $connection = new Predis\Client();

        $user_keys = $connection->smembers('users');
        
        $users = array();
        
        foreach ($user_keys as $a_user) {
            $users[] = json_decode($connection->get($a_user));
        }
        return $users;
    }

    static function dehydrate($user) {
        $data = array();
        $data['id'] = $user->id;
        $data['name'] = $user->name;

        return json_encode($data);
    }

    static function hydrate($data, $predis_conn) {
        $user_data = json_decode($data);

        $user = new User($predis_conn);
        $user->id = $user_data->id;
        $user->name = $user_data->name;

        return $user;
    }

    static function getRedisUserKey($id) {
        return 'user_'.$id;
    }

    function redisUserKey() {
        return self::getRedisUserKey($this->id);
    }

    static function getRedisUserConnectionKey($id) {
        return 'user_connection_'.$id;
    }

    function redisUserConnectionKey() {
        return self::getRedisUserConnectionKey($this->id);
    }

    static function getRedisUserReverseConnectionKey($id) {
        return 'user_reverse_connection_'.$id;
    }

    function redisUserReverseConnectionKey() {
        return self::getRedisUserReverseConnectionKey($this->id);
    }

    static function getRedisUserActivityKey($id) {
        return 'user_activity_'.$id;
    }

    function redisUserActivityKey() {
        return self::getRedisUserActivityKey($this->id);
    }

    function store() {
        if ($this->id == NOT_YET_STORED) {
            $this->id = $this->redis->incr('user_id');
        }

        if (!isset($this->name)) {
            $this->name = "Name number ".$this->id;
        }

        $data = self::dehydrate($this);
        $key = $this->redisUserKey();

        try {
            $this->redis->set($key, $data);
            $this->redis->sadd('users', $key);
        } catch (Exception $e) {
            var_dump($e->getmessage()." on ".$e->getLine());
        }

        return $this->id;
    }

    function addConnection($user_id) {
        try {
            $this->redis->sadd($this->redisUserConnectionKey(), $user_id);
            
            $this->redis->sadd(self::getRedisUserReverseConnectionKey($user_id), $this->id);
        } catch (Exception $e) {
            var_dump($e->getmessage()." on ".$e->getLine());
        }
    }

    function addConnections($user_ids = array()) {
        if (empty ($user_ids)) {
            return;
        }
        try {
            $redis_user_connection_key = $this->redisUserConnectionKey();
            $this_id = $this->id;
            $this->redis->pipeline(function ($pipe) use ($redis_user_connection_key, $user_ids, $this_id) {
                $pipe->sadd($redis_user_connection_key, $user_ids);
                foreach ($user_ids as $user_id) {
                    $pipe->sadd(User::getRedisUserReverseConnectionKey($user_id), $this_id);
                }
            });
        } catch (Exception $e) {
            var_dump($e->getmessage()." on ".$e->getLine());
        }
    }

    function removeConnection($user_id) {
        try {
            $this->redis->srem($this->redisUserConnectionKey(), $user_id);
            $this->redis->srem(self::getRedisUserReverseConnectionKey($user_id), $this->id);
        } catch (Exception $e) {
            var_dump($e->getmessage()." on ".$e->getLine());
        }
    }

    function getConnections() {
        try {
            $connections = $this->redis->smembers($this->redisUserConnectionKey());
            return $connections;
        } catch (Exception $e) {
            var_dump($e->getmessage()." on ".$e->getLine());
        }

        return array();
    }

    function getReverseConnections() {
        try {
            $reverse_connections = $this->redis->smembers($this->redisUserReverseConnectionKey());
            return $reverse_connections;
        } catch (Exception $e) {
            var_dump($e->getmessage()." on ".$e->getLine());
        }

        return array();
    }

    function delete() {
        if ($this->id  == NOT_YET_STORED) {
            return;
        }

        $key = $this->redisUserKey();
        $reverseConnections = $this->getReverseConnections();
        $this_id = $this->id;
        $redis_user_connection_key = $this->redisUserConnectionKey();
        $redis_user_activity_key = $this->redisUserActivityKey();
        $this->redis->pipeline(function ($pipe) use ($reverseConnections, $key, $this_id,
                                                     $redis_user_connection_key, $redis_user_activity_key) {
            foreach($reverseConnections as $user_id) {
                $pipe->srem(User::getRedisUserConnectionKey($user_id), $this_id);
            }
            $pipe->del($redisUserConnectionKey);
            $pipe->del($redisUserActivityKey);
            $pipe->del($key);
            $pipe->srem('users', $key);
        });
    }

    function addActivity($activity_text) {
        $activity = new Activity($this->redis);
        $activity->value = $activity_text;
        $activity->owner_id = $this->id;
        $activity->store();

        try {
            $reverse_connections = $this->getReverseConnections();
            $redis_user_activity_key = $this->redisUserActivityKey();
            $this->redis->pipeline(function ($pipe) use ($reverse_connections, $redis_user_activity_key, $activity){
                $pipe->lpush($redis_user_activity_key, $activity->id);
                foreach ($reverse_connections as $user_id) {
                    $pipe->lpush(User::getRedisUserActivityKey($user_id), $activity->id);
                }
            });

        } catch (Exception $e) {
            var_dump($e->getmessage()." on ".$e->getLine());
        }

        return $activity;
    }

    function getActivities($offset = 0, $limit = 500, $dehydrated = false) {
        $activities = array();
        try {
            $activity_keys = $this->redis->lrange($this->redisUserActivityKey(), $offset, $limit);

            foreach($activity_keys as $a_key) {
                $activities[] = Activity::find($a_key, $this->redis, $dehydrated);
            }
        } catch (Exception $e) {
            var_dump($e->getmessage()." on ".$e->getLine());
        }

        return $activities;
    }
}
