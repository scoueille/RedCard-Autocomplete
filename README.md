Redis Autocomplete
==================

Autocomplete implementation using PHP+Redis.

Inspired by https://github.com/seatgeek/soulmate

This library handles a basic implementation of autocomplete with sorted results (according to "scores") as well as arbitrary metadata for results. Also has the ability to separate different autocomplete databases in to "bins" (e.g. have separate bins "users" and another for "videos" so when querying against "users" it doesn't show results from "videos")

## Installation

Add `victorsigma/redcard` as a requirement to `composer.json`:

```javascript
{
    "require": {
        "victorsigma/redcard": "dev-master"
    }
}
```

Update your packages with `composer update` or install with `composer install`.

### Laravel 4 Integration

The RedCard supports Laravel 4 integration. Best practice to use the library in Laravel 4 is to add the ServiceProvider.

Open your Laravel config file `config/app.php` and add the following lines.

In the `$providers` array add the service providers for this package.
    
    'providers' => array(
        
        [...]

        'VictorSigma\RedCard\RedCardServiceProvider'
    ),


### Standalone
 
You will need to create a Predis Client Instance and provide it to the autocomplete class. 

```php   
    $redis = new Predis\Client(array(
        'scheme' => 'tcp',
        'host'   => 'localhost',
        'port'   => 6379,
    ));

    $autocomplete = new VictorSigma\RedCard\RedisAutocomplete( $redis );

    // You are ready to go!
```

## Basic Usage

To store data you must have a unique ID for an item and the phrase that should be searchable.

### Laravel

```php

RedCard::store(2, "cat");
RedCard::store(3, "care");
RedCard::store("MYCRAZYID", "caress");
RedCard::store(55, "cars");
RedCard::store(6, "camera");

$results = RedCard::find("car");

var_dump($results)

```


### Standalone

```php

$autocomplete->store(2, "cat");
$autocomplete->store(3, "care");
$autocomplete->store("MYCRAZYID", "caress");
$autocomplete->store(55, "cars");
$autocomplete->store(6, "camera");

$results = $autocomplete->find("car");

var_dump($results)

```

## Bins 

Different types of data can be distinguished from one another through bins. Each bin has its own name and when searching and removing they will not conflict with one another. 

### Laravel

```php

RedCard::store(2, "Mary", "users");
RedCard::store(3, "Sally", "users");
RedCard::store(4, "Leo", "users" );

RedCard::store(5, "Mary Had A Litte Lamb", "blog-title");
RedCard::store(6, "Redis Rocks, A Life Story", "blog-title");

$results = RedCard::find("Mary", "users");

// Will only return Mary instead of "Mary Had A Litte Lamb"
```

### Standalone

```php

$autocomplete->store(2, "Mary", "users");
$autocomplete->store(3, "Sally", "users");
$autocomplete->store(4, "Leo", "users" );

$autocomplete->store(5, "Mary Had A Litte Lamb", "blog-title");
$autocomplete->store(6, "Redis Rocks, A Life Story", "blog-title");

$results = $autocomplete->find("Mary", "users");

// Will only return Mary instead of "Mary Had A Litte Lamb"

```



## Interface

The basic functions that you need to be aware of to utilize RedCard.

- **store**: store a new item to autocomplete

	```php
	    store($id, $phrase, $bin = '', $score = 1, $data = NULL) 
	```

	example 
	```php
	    RedCard::store('id123', "Clockwork Orange", "Books", 3, array('author'=>'Anthony Burgess'))
	```


- **find**: find an item. Searches are cached in a seperate hash. 

	```php
	    find($phrase, $bin = '', $count = 10, $isCaching = true)
	```

	example 
	```php
	    RedCard::find("Clock", "Books" , 1, true)
	```

- **remove**: remove an item from a bin. Searches are cached in a seperate hash. 

	```php
	    remove($id, $bin = '')
	```

	example 
	```php
	    RedCard::remove('id123', 'Books')
	```





## License

RedCard Autocomplete is licensed under the [MIT License](http://opensource.org/licenses/MIT).
Original Copyright (c) 2011 Rishi Ishairzay, released under the MIT license   