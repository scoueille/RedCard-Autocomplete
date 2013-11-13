<?php namespace VictorSigma\RedCard;

class RedisAutocomplete {

	const MIN_LETTERS = 2;
	const MINUTE = 60;
	
	public static $EXCLUDE = array(
		'and' => 1,
		'or' => 1,
		'the' => 1,
	);
	
	private $redis;
	private $bin;
	
	public function __construct($redis, $bin) {
		$this->redis = $redis;
		$this->bin = $bin;
	}
	
	// Take a string and remove unalphabetic characters and make it lowercase
	private function normalize($phrase) {
		$phrase = preg_replace('~, ?~', '_', $phrase);
		$phrase = preg_replace('~[^a-z0-9_ ]+~', '', strtolower($phrase));
		return $phrase;
	}
	
	// Take a string, normalize it then return an array of words to match against
	public function words($phrase) {
		$phrase = explode(' ', $phrase);
		$filtered = array();
		
		foreach ($phrase as $word) {
			// Remove excluded words
			if (!isset(self::$EXCLUDE[$word]) && isset($word[self::MIN_LETTERS - 1])) {
				array_push($filtered, $word);
			}
		}
		return $filtered;
	}
	
	public function wordPrefixes($word) {
		$array = array();
		if (is_array($word)) {
			// If an array of words is passed in then recursively call on each element
			foreach ($word as $w) {
				$array = array_merge($array, $this->wordPrefixes($w));
			}
			return $array;
		}
		
		// Start at the minimum amount of letters till the end of the word
		// e.g. "care" gives ["ca", "car", "care"]
		for ($i = self::MIN_LETTERS - 1, $k = strlen($word); $i <= $k; $i++) {
			array_push($array, substr($word, 0, $i));
		}
		return $array;
	}
	
	private function prefixKey($prefix) {
		return 'auto:' . $this->bin . ':' . $prefix;
	}
	
	private function metaKey($suffix) {
		return 'auto:' . $this->bin . '>' . $suffix;
	}
	
	public function remove($id) {
		$phrase = $this->redis->hget($this->metaKey('ids'), $id);
		if (!$phrase) return false;
		
		$prefixes = $this->wordPrefixes(explode(' ', $phrase));
		
		foreach ($prefixes as $prefix) {
			$this->redis->zrem($this->prefixKey($prefix), $id);
		}
		$this->redis->hdel($this->metaKey('ids'), $id);
		$this->redis->hdel($this->metaKey('objects'), $id);
	}
	
	public function hasID($id) {
		return $this->redis->hget($this->metaKey('ids'), $id);
	}
	
	public function store($id, $phrase = NULL, $score = 1, $data = NULL) {
		
		$obj = array();
		if (is_array($id)) $obj = $id;
		else $obj['id'] = $id;
		
		$obj = array_merge(array(
			'id' => NULL,
			'score' => $score,
			'phrase' => $phrase,
			'data' => $data,
		), $obj);
		
		
		// Must have an ID and a phrase
		if ($obj['id'] === NULL || $obj['phrase'] === NULL) return false;
		
		if ($obj['data'] === NULL) unset($obj['data']);
		
		if ($this->hasID($obj['id'])) $this->remove($obj['id']);
		
		
	
		// Normalize string (strip non-alpha numeric, make lower case)
		$normalized = $this->normalize($obj['phrase']);
	
		// Split phrase into normalized words
		$words = $this->words($normalized);
	
		// Get prefixes for each word
		$prefixes = $this->wordPrefixes($words);
		
		foreach ($prefixes as $prefix) {
			// Add the prefix and its identifier to the set
			$this->redis->zadd($this->prefixKey($prefix), $obj['score'], $obj['id']);
		}
		
		
		// Store the phrase that is associated with the ID in a hash
		$this->redis->hset($this->metaKey('ids'), $obj['id'], $normalized);
		
		// If data is passed in with it, then store the data as well
		$this->redis->hset($this->metaKey('objects'), $obj['id'], json_encode($obj));
		
		return true;
		
	}
	
	public function find($phrase, $count = 10, $isCaching = true) {
		
		// Normalize the words
		$normalized = $this->normalize($phrase);
		
		// Get a normalized array of all the words
		$words = $this->words($normalized);
		if (count($words) == 0) return array();
		
		// Sort them for caching purposes (e.g. both "man power" and "power man" will
		// point to the same cache
		sort($words);
		$joined = implode('_', $words);
		
		$key = $this->prefixKey('cache:' . $joined);
		
		foreach ($words as &$w) {
			// Replace the words with their respective prefix keys
			$w = $this->prefixKey($w);
		}
		
		$objects = false;
		
		// Check the cache to see if we stored the intersection already
		try {
			$objects = $this->redis->get($key);
		} catch (Exception $e) {
			
		}
		
		
		if (!$objects) {
			$range = array();
			
			if (count($words) == 1) {
				// If there's only one word, no need to find the intersection
				$range = $this->redis->zrevrange($words[0], 0, $count);
			} else {
				
				// Find the intersection of all the results and store it in a separate key
				call_user_func_array(array($this->redis, 'zinterstore'), array_merge(array(
					$key, count($words),
				), $words));
				
				
				$range = $this->redis->zrevrange($key, 0, $count);
			}
			$objects = $range ? $this->redis->hmget($this->metaKey('objects'), $range) : array();
			
			foreach ($objects as &$obj) {
				$obj = json_decode($obj, true);
			}
			// Cache the results for ten minutes
			if ($isCaching){
				$this->redis->set($key, json_encode($objects));
				$this->redis->expire($key, self::MINUTE * 10);
			} 
		} else {
			// Unserialize the cache
			$objects = json_decode($objects, true);
		}
		
		
		
		return $objects;
	}


}
