<?php

use VictorSigma\RedCard\RedisAutocomplete;

class helloTest extends PHPUnit_Framework_TestCase {

    private $ra;
    private $redis;

    public function setUp(){ 

        /*************************
            WARNING: 
            This does a FLUSHALL so make sure you don't have 
            anything imortant in your redis store.  
        **************************/

        $this->redis = new Predis\Client(array(
            'scheme' => 'tcp',
            'host'   => 'localhost',
            'port'   => 6379,
        ));

        $this->ra = new RedisAutocomplete( $this->redis );
    }

    public function test_Simple_List_Store()
    {
        $this->redis->FLUSHALL();

        $this->ra->store(2, "cat");
        $this->ra->store(3, "care");
        $this->ra->store(4, "caress");
        $this->ra->store(5, "cars");
        $this->ra->store(6, "camera");

        $this->assertEquals(3, count($this->ra->find("car")));
    }

    public function test_No_Bin_Conflict()
    {
        $this->redis->FLUSHALL();

        $this->ra->store(2, "cat", '');
        $this->ra->store(3, "care", '');
        $this->ra->store(4, "caress", '');
        $this->ra->store(5, "cars", '');
        $this->ra->store(6, "camera", '');
        $this->ra->store(6, "Carmen Elektra", '');

        $this->ra->store(2, "cat", 'words');
        $this->ra->store(3, "care", 'words');
        $this->ra->store(4, "caress", 'words');
        $this->ra->store(5, "cars", 'words');
        $this->ra->store(6, "camera", 'words');

        $this->assertEquals(4, count($this->ra->find("car", '')) );  
              
        $this->assertEquals(3, count($this->ra->find('car' , 'words')) );
    }

    public function test_Remove()
    {
        $this->redis->FLUSHALL();

        $ra = new RedisAutocomplete( $this->redis , false ); 

        $this->ra->store(2, "cat");
        $this->ra->store(3, "care");
        $this->ra->store(4, "caress");
        $this->ra->store(5, "cars");
        $this->ra->store(6, "camera");

        $this->ra->remove(4);

        $this->ra->find('car');
        $this->assertEquals(2, count($this->ra->find("car")));
    }

    public function test_Score()
    {
        $this->redis->FLUSHALL();

        $ra = new RedisAutocomplete( $this->redis ); 

        $this->ra->store(2, "cat");
        $this->ra->store(3, "care", '', 1);
        $this->ra->store(4, "caress", '', 2);
        $this->ra->store(5, "cars", '', 5);
        $this->ra->store(6, "camera");

        $results = $this->ra->find('car');
        
        $this->assertEquals($results[0]["phrase"], "cars" );
        $this->assertEquals($results[1]["phrase"], "caress" );
        $this->assertEquals($results[2]["phrase"], "care" );
    }

    public function test_Meta()
    {
        $this->redis->FLUSHALL();

        $ra = new RedisAutocomplete( $this->redis ); 

        $this->ra->store(2, "cat");
        $this->ra->store(3, "care");
        $this->ra->store(4, "caress");
        $this->ra->store(5, "cars");
        $this->ra->store(6, "camera",'', 1, array('test'=>'me', 'baby'=>'one more time') );

        $results = $this->ra->find('camera');
        
        $this->assertEquals($results[0]["data"]["test"], "me" );
        $this->assertEquals($results[0]["data"]["baby"], "one more time" );
        
    }

    public function test_Same_Key()
    {
        $this->redis->FLUSHALL();

        $ra = new RedisAutocomplete( $this->redis ); 

        $this->ra->store(2, "cat");
        $this->ra->store(3, "care");
        $this->ra->store(4, "caress");
        $this->ra->store(5, "cars");
        $this->ra->store(6, "camera");

        $this->ra->store("cheese", "camera");

        $results = $this->ra->find('camera');

        $this->assertEquals(2, count($results));
        
    }

    public function test_Same_Key_Same_Bin()
    {
        $this->redis->FLUSHALL();

        $ra = new RedisAutocomplete( $this->redis ); 

        $this->ra->store(2, "cat", "tim");
        $this->ra->store(3, "care", "tim");
        $this->ra->store(4, "caress", "tim");
        $this->ra->store(5, "cars", "tim");
        $this->ra->store(6, "camera", "tim");

        $this->ra->store("cheese", "camera", "tim");

        $results = $this->ra->find('camera', "tim");
    

        $this->assertEquals(2, count($results));
        
    }

    public function test_Same_ID()
    {
        $this->redis->FLUSHALL();

        $this->ra->store(2, "cat");
        $this->ra->store(3, "care");
        $this->ra->store(4, "caress");
        $this->ra->store(5, "cars");
        $this->ra->store(6, "camera");

        $this->ra->store(3, "phone");

        $results = $this->ra->find("car");

        $this->assertEquals(2, count($results) );

    }

}