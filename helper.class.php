<?php
include "curl.baidu.map.class.php";
//include "redis.class.php";
$helper = new helper();
echo $helper->smartSwitch($_GET['k'],'');
  
class helper{
  public function smartSwitch($keyWord, $fromUsername){
    switch ($keyWord){
      case "h":
      case "help":
        return $this->menuHelp();
        break;
      
      case "go":
      case "g":
      case "去哪吃":
		    return $this->menuGo();
      	break;
      
      case "list":
      case "l":
      	return $this->menuList();
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
  
  private function menuGo(){
  	$result = $this->randomResult();
    return $result['name']. $result['address']."<".$result['detail_info']['detail_url'].">";
  }
  
  private function menuList(){
    $poi = new curlbaidumap();
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
  
  public function randomResult() {
  	$poi = new curlbaidumap();
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