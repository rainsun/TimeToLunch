<?php
include "curl.baidu.map.class.php";

if(isset($_GET['k'])){
  $helper = new helper();
  $helper->debug = true;
  echo $helper->smartSwitch($_GET['k'], 'rainsun');
}
  
class helper{

  public $debug = false;
  private $redis = Null;

  public function helper(){
    $dbname = "xlDVkryrqFtHReOjVTud";
  	$host = getenv('HTTP_BAE_ENV_ADDR_REDIS_IP');
  	$port = getenv('HTTP_BAE_ENV_ADDR_REDIS_PORT');
  	$user = getenv('HTTP_BAE_ENV_AK');
  	$pwd = getenv('HTTP_BAE_ENV_SK');
    
    $this->redis = new Redis();
    $this->redis->connect($host, $port);
    $this->redis->auth($user . "-" . $pwd . "-" . $dbname);
  }

  private function getStatus($fromUsername){
    return $this->redis->hget($fromUsername, 'status');
  }

  private function setStatus($fromUsername, $status){
    return $this->redis->hset($fromUsername, 'status', $status);
  }

  public function setLocation($fromUsername, $x, $y){
    return $this->redis->hset($fromUsername, 'location', $x.",".$y);
  }

  public function getLocation($fromUsername){
    return $this->redis->hget($fromUsername, 'location');
  }

  public function smartSwitch($keyWord, $fromUsername){
    switch ($keyWord){
      case "h":
      case "help":
        $this->setStatus($fromUsername, 'HELP');
        return $this->menuHelp();
        break;
      
      case "go":
      case "g":
      case "去哪吃":
        $this->setStatus($fromUsername, 'GO');
		    return $this->menuGo($fromUsername, false);
      	break;
      
      case "next":
      case "n":
      case "下一个":
      case "再来":
        $this->setStatus($fromUsername, 'GO');
        return $this->menuGo($fromUsername, true);
        break;

      case "list":
      case "l":
        $this->setStatus($fromUsername, 'LIST');
      	return $this->menuList($fromUsername);
      	break;
      
      default: 
        return $this->menuDefault();
        break;
    }
  }
  



  private function menuHelp(){
  	$res = "go:出发去吃饭;\nlist:列出周边吃饭的地方;";
    return $res;
  }
  
  private function menuGo($fromUsername, $next){
    if(!$next){
      $name = $this->redis->hget($fromUsername, 'name');
      if($name){
        return $name."已经被你选中了，换一个请输入next";
      }
    }
  	$result = $this->randomResult($fromUsername);

    if($this->debug)
      print_r($result);

    $this->redis->hset($fromUsername, 'uid', $result['uid']);
    $this->redis->hset($fromUsername, 'name', $result['name']);
    $this->redis->hset($fromUsername, 'addr', $result['address']);
    $this->redis->hset($fromUsername, 'url', $result['detail_info']['detail_url']);
    $this->redis->setTimeout($fromUsername, 60*60*4);


      /*$news = array(
        'Title' => $result['name'],
        'Description' => $result['address'],
        'PicUrl' => "http://api.map.baidu.com/staticimage?center=".$result['location']['lng'].",".$result['location']['lat']."&width=150&height=150&zoom=13",
        'Url' => $result['detail_info']['detail_url'],
      );*/
      //$news[0] = $news;
    return $result['name']. $result['address']."<".$result['detail_info']['detail_url'].">";
    //return $news;
  }
  
  private function menuList($fromUsername){
    $poi = new curlbaidumap();
    $location = $this->getLocation($fromUsername);
    if($location){
      if($this->debug)
        echo "== Location:". $location. "\n";
      $poi->setGeo($location);
    }
    $result = $poi->getPlace();
    $result = $result['results'];
    $count = count($result);
    $str = "";
    for($i=0;$i<$count;$i++){
      $str .= ($i+1).".".$result[$i]['name']."\n";	
    }
    return $str;
  }
  
  private function menuDefault() {
  	$res = "欢迎使用午餐吃点啥：\n输入h或者help会有帮助!\n";
    return $res;
  }
  
  public function randomResult($fromUsername){
  	$poi = new curlbaidumap();
    $location = $this->getLocation($fromUsername);
    if($location){
      if($this->debug)
        echo "== Location: ". $location. "\n";
      $poi->setGeo($location);
    }
    $res = $poi->getPlace();
    if($res['status'] == 0){
      	$res = $res['results'];
        $count = count($res);
      	$rand = rand(1,$count) - 1;
      	return $res[$rand];
    }else{
    	return "Please try again!";
	}
  }
}
?>