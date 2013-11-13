<?php

class curlbaidumap {
  
  private $uid = NULL;
  private $geo = "40.037556,116.423708";
  private $ak = "387c7138aa48098fe355d41f5b225926";
  private $placeUrl = "http://api.map.baidu.com/place/v2/search?ak=<<--ak-->>&output=json&query=%E9%A4%90%E9%A6%86&page_size=10&page_num=0&scope=2&location=<<--geo-->>&radius=10000";        
  private $tuangouUrl = "http://api.map.baidu.com/place/v2/eventdetail?uid=<<--uid-->>&output=json&ak=<<--ak-->>";
  
  public function getPlace() {
    $placeUrl = $this->wrapUrl($this->placeUrl);
    $res = $this->cUrl($placeUrl);
    $res = json_decode($res, true);
    return $res;
  }
  
  public function getTuanGouInfo( $uid ) {
    $this->uid = $uid;
    $tuangouUrl = $this->wrapUrl($this->tuangouUrl);
  	$res = $this->cUrl($tuangouUrl);
    return json_decode($res, true);
  }
  
  private function wrapUrl($url) {
    $search  = array(
      "<<--uid-->>",
      "<<--ak-->>",
      "<<--geo-->>",
    );
    $replace = array(
    	$this->uid,
      	$this->ak,
      	$this->geo,
    );
  	return str_replace($search, $replace, $url);
  }
  
  private function cUrl( $url ){
  	$ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tmp = curl_exec($ch);
    curl_close($ch);
    return $tmp;
  }
}
?>
