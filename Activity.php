<?php
require_once "init.php";

class Activity {
    var $redis;
    var $id = NOT_YET_STORED;
    var $owner_id;
    var $value;
    
    function __construct($predis_conn) {
        $this->redis = $predis_conn;
        $this->value = null;
        $this->owner_id = null;
        $this->id = NOT_YET_STORED;
    }

    static function find($id, $connection = null, $dehydrated = false) {
        $activity_key = self::getRedisActivityKey($id);
        if (!isset($connection)) {
            $connection = new Predis\Client();
        }
        try {
            $result = $connection->get($activity_key);
            
            if ($dehydrated) {
                return $result;
            }

            $activity = self::hydrate($result, $connection);
            return $activity;
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
        
        return NOT_YET_STORED;
    }
    
    static function getRedisActivityKey($id) {
        return 'activity_'.$id;
    }
    
    function redisActivityKey() {
        return self::getRedisActivityKey($this->id);
    }

    static function dehydrate($activity) {
        $data = array();
        $data['id'] = $activity->id;
        $data['value'] = $activity->value;
        $data['owner_id'] = $activity->owner_id;

        return json_encode($data);
    }

    static function hydrate($data, $predis_conn) {
        $activity_data = json_decode($data);

        $activity = new Activity($predis_conn);
        $activity->id = $activity_data->id;
        $activity->value = $activity_data->value;
        $activity->owner_id = $activity_data->owner_id;

        return $activity;
    }

    function store() {
        if ($this->id == NOT_YET_STORED) {
            $this->id = $this->redis->incr('activity_id');
        }

        if (!isset($this->value)) {
            $this->value = "Activity Value ".$this->id;
        }

        $data = self::dehydrate($this);
        $key = $this->redisActivityKey();

        try {
            $this->redis->set($key, $data);
            $this->redis->sadd('activities', $key);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }

        return $this->id;
    }

    function delete() {
        if ($this->id  == NOT_YET_STORED) {
            return;
        }

        $key = $this->redisActivityKey();
        $this->redis->del($key);
        $this->redis->srem('activities', $key);
    }

    function getOwner() {
        return User::find($this->owner_id);
    }
}
