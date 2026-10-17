<?php
/**
 * Bamero Redis Object Cache
 * 
 * Production-ready Redis object cache implementation for WordPress.
 * Uses php-redis extension for optimal performance.
 * 
 * Configuration via wp-config.php or environment variables:
 * - WP_REDIS_HOST: Redis server hostname (default: 127.0.0.1)
 * - WP_REDIS_PORT: Redis server port (default: 6379)
 * - WP_REDIS_TIMEOUT: Connection timeout in seconds (default: 1)
 * - WP_REDIS_PREFIX: Cache key prefix (default: wp_)
 */

defined('ABSPATH') || exit;

// Check if Redis extension is loaded
if (!extension_loaded('redis')) {
    // Fallback to file-based cache if Redis is not available
    require_once ABSPATH . 'wp-content/plugins/cache/enhanced-object-cache.php';
    return;
}

// Define defaults if not already defined
if (!defined('WP_REDIS_HOST')) {
    define('WP_REDIS_HOST', '127.0.0.1');
}
if (!defined('WP_REDIS_PORT')) {
    define('WP_REDIS_PORT', 6379);
}
if (!defined('WP_REDIS_TIMEOUT')) {
    define('WP_REDIS_TIMEOUT', 1);
}
if (!defined('WP_REDIS_PREFIX')) {
    define('WP_REDIS_PREFIX', 'wp_');
}

// Ensure WP_CACHE_KEY_SALT is set
if (!defined('WP_CACHE_KEY_SALT')) {
    define('WP_CACHE_KEY_SALT', WP_REDIS_PREFIX);
}

/**
 * Bamero_Redis_Object_Cache class
 * 
 * Implements WordPress object cache using Redis for production environments.
 */
class Bamero_Redis_Object_Cache implements WP_Object_Cache {
    
    private $redis;
    private $cache = array();
    private $mc;
    private $stats = array();
    private $group_prefix = '';
    
    public function __construct() {
        $this->connect();
    }
    
    /**
     * Connect to Redis server
     */
    private function connect() {
        try {
            $this->redis = new Redis();
            $connected = $this->redis->connect(
                WP_REDIS_HOST,
                (int) WP_REDIS_PORT,
                (float) WP_REDIS_TIMEOUT
            );
            
            if (!$connected) {
                $this->redis = null;
                return false;
            }
            
            // Authenticate if password is set
            $password = getenv('WP_REDIS_PASSWORD') ?: '';
            if (!empty($password)) {
                $this->redis->auth($password);
            }
            
            // Select database
            $db = (int) (getenv('WP_REDIS_DB') ?: 0);
            $this->redis->select($db);
            
            // Set prefix
            $this->group_prefix = WP_REDIS_PREFIX;
            
            return true;
            
        } catch (Exception $e) {
            $this->redis = null;
            error_log('Redis connection error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Redis instance
     */
    private function get_redis() {
        if ($this->redis === null) {
            $this->connect();
        }
        return $this->redis;
    }
    
    /**
     * Get cache key with prefix
     */
    private function get_key($key, $group) {
        if (empty($group)) {
            $group = 'default';
        }
        return $this->group_prefix . $group . ':' . $key;
    }
    
    /**
     * Add data to the cache if it doesn't already exist
     */
    public function add($key, $data, $group = 'default', $expire = 0) {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $cache_key = $this->get_key($key, $group);
        $result = $redis->setnx($cache_key, maybe_serialize($data));
        
        if ($result && $expire > 0) {
            $redis->expire($cache_key, $expire);
        }
        
        if ($result) {
            $this->cache[$group][$key] = $data;
        }
        
        return $result;
    }
    
    /**
     * Add multiple values to the cache at once
     */
    public function add_multiple($items, $group = 'default', $expire = 0) {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $results = array();
        foreach ($items as $key => $data) {
            $results[$key] = $this->add($key, $data, $group, $expire);
        }
        return $results;
    }
    
    /**
     * Close the cache
     */
    public function close() {
        if ($this->redis) {
            $this->redis->close();
            $this->redis = null;
        }
        return true;
    }
    
    /**
     * Decrement numeric cache item's value
     */
    public function decr($key, $offset = 1, $group = 'default') {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $cache_key = $this->get_key($key, $group);
        $value = $redis->decrBy($cache_key, $offset);
        
        if ($value >= 0) {
            $this->cache[$group][$key] = $value;
        }
        
        return $value;
    }
    
    /**
     * Delete everything from the cache
     */
    public function delete_all() {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $result = $redis->flushDB();
        $this->cache = array();
        return $result;
    }
    
    /**
     * Delete a value from the cache
     */
    public function delete($key, $group = 'default') {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $cache_key = $this->get_key($key, $group);
        $result = $redis->del($cache_key);
        
        if (isset($this->cache[$group][$key])) {
            unset($this->cache[$group][$key]);
        }
        
        return $result;
    }
    
    /**
     * Delete multiple keys from the cache
     */
    public function delete_multiple($keys, $group = 'default') {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $results = array();
        foreach ($keys as $key) {
            $results[$key] = $this->delete($key, $group);
        }
        return $results;
    }
    
    /**
     * Increment numeric cache item's value
     */
    public function incr($key, $offset = 1, $group = 'default') {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $cache_key = $this->get_key($key, $group);
        $value = $redis->incrBy($cache_key, $offset);
        
        if ($value !== false) {
            $this->cache[$group][$key] = $value;
        }
        
        return $value;
    }
    
    /**
     * Get the cache contents
     */
    public function get($key, $group = 'default', $force = false, &$found = null) {
        $redis = $this->get_redis();
        
        // Try local cache first
        if (!$force && isset($this->cache[$group][$key])) {
            $found = true;
            return $this->cache[$group][$key];
        }
        
        if (!$redis) {
            $found = false;
            return false;
        }
        
        $cache_key = $this->get_key($key, $group);
        $value = $redis->get($cache_key);
        
        if ($value === false) {
            $found = false;
            return false;
        }
        
        $data = maybe_unserialize($value);
        $this->cache[$group][$key] = $data;
        $found = true;
        return $data;
    }
    
    /**
     * Get multiple values from the cache at once
     */
    public function get_multiple($keys, $group = 'default') {
        $redis = $this->get_redis();
        if (!$redis) {
            return array();
        }
        
        $values = array();
        $cache_keys = array();
        
        foreach ($keys as $key) {
            if (isset($this->cache[$group][$key])) {
                $values[$key] = $this->cache[$group][$key];
            } else {
                $cache_keys[$key] = $this->get_key($key, $group);
            }
        }
        
        if (!empty($cache_keys)) {
            $results = $redis->mget(array_values($cache_keys));
            foreach ($cache_keys as $key => $cache_key) {
                $index = array_search($cache_key, array_values($cache_keys), true);
                if (isset($results[$index]) && $results[$index] !== false) {
                    $values[$key] = maybe_unserialize($results[$index]);
                    $this->cache[$group][$key] = $values[$key];
                }
            }
        }
        
        return $values;
    }
    
    /**
     * Replace the contents of a cache entry
     */
    public function replace($key, $data, $group = 'default', $expire = 0) {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $cache_key = $this->get_key($key, $group);
        $result = $redis->set($cache_key, maybe_serialize($data));
        
        if ($result && $expire > 0) {
            $redis->expire($cache_key, $expire);
        }
        
        if ($result) {
            $this->cache[$group][$key] = $data;
        }
        
        return $result;
    }
    
    /**
     * Reset the cache keys
     */
    public function reset() {
        $this->cache = array();
        return true;
    }
    
    /**
     * Set a cache entry
     */
    public function set($key, $data, $group = 'default', $expire = 0) {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $cache_key = $this->get_key($key, $group);
        $result = $redis->set($cache_key, maybe_serialize($data));
        
        if ($result && $expire > 0) {
            $redis->expire($cache_key, $expire);
        }
        
        if ($result) {
            $this->cache[$group][$key] = $data;
        }
        
        return $result;
    }
    
    /**
     * Set multiple cache entries
     */
    public function set_multiple($items, $group = 'default', $expire = 0) {
        $redis = $this->get_redis();
        if (!$redis) {
            return false;
        }
        
        $results = array();
        foreach ($items as $key => $data) {
            $results[$key] = $this->set($key, $data, $group, $expire);
        }
        return $results;
    }
    
    /**
     * Switch the internal blog ID
     */
    public function switch_to_blog($blog_id) {
        $this->group_prefix = WP_REDIS_PREFIX . $blog_id . '_';
        return true;
    }
    
    /**
     * Get cache statistics
     */
    public function stats() {
        $redis = $this->get_redis();
        if (!$redis) {
            return array();
        }
        
        return array(
            'info' => $redis->info(),
            'stats' => $this->stats
        );
    }
    
    /**
     * Get the cache key prefix
     */
    public function get_group_prefix($group) {
        return $this->group_prefix . $group . ':';
    }
}

// Initialize the object cache
if (!isset($wp_object_cache) || !$wp_object_cache) {
    $wp_object_cache = new Bamero_Redis_Object_Cache();
}
