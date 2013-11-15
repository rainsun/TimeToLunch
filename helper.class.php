<?php
include "curl.baidu.map.class.php";

if(isset($_GET['k'])){
  $helper = new helper($wechat);
  $helper->debug = true;
  $res =  $helper->smartSwitch($_GET['k'], 'rainsun');
  if (is_array($res))
    print_r($res);
  else
    echo $res;
}
  
class helper{

  public $debug = false;
  private $redis = Null;
  private $wechat = null;

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
    return $this->redis->hget('user_'.$fromUsername, 'status');
  }

  private function setStatus($fromUsername, $status){
    return $this->redis->hset('user_'.$fromUsername, 'status', $status);
  }

  public function setLocation($fromUsername, $x, $y){
    return $this->redis->hset('user_'.$fromUsername, 'location', $x.",".$y);
  }

  public function getLocation($fromUsername){
    return $this->redis->hget('user_'.$fromUsername, 'location');
  }

  public function updatePic($fromUsername, $picUrl){
    $group = $this->redis->hget('user_'.$fromUsername, 'group');
    if($group){
      $this->redis->hset('group_'.$group, 'deal', $picUrl);
      return "成功接收：".$group."用户组。";
    }else{
      return "您目前还没有任何交易记录！！";
    }
  }

  public function smartSwitch($keyWord, $fromUsername, $option=null){
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

      case "deal":
      case "d":
        $status = $this->getStatus($fromUsername);
        if ( $status == 'GO' ){
          $this->setStatus($fromUsername, 'DEAL');
          return $this->menuDeal($fromUsername);
        }else{
          return $this->smartSwitch('default', $fromUsername);
        }
        break;

      case "join":
      case "j":
        $result = $this->menuJoin($fromUsername, $option[1]);
        if($result == 1)
          return "您已经是本组成员。";
        elseif($result == 2)
          return "指定用户组不存在，请仔细检查。";
        elseif($result == 3)
          return "必须指定团队号码";
        else
          return $result;
        break;


      case "list":
      case "l":
        $this->setStatus($fromUsername, 'LIST');
      	return $this->menuList($fromUsername);
      	break;

      case "welcome":
        return $this->menuDefault();
        break;

      case "r":
      case "run":
        break;

      case "del":
        $this->redis->delete('user_'.$fromUsername);
        return "ok";
        break;
      
      default: 
        return $this->menuDefault();
        break;
    }
  }
  



  private function menuHelp(){
  	$res = "go:出发去吃饭;\nlist:列出周边吃饭的地方;\n发送位置，帮助您找到周边的美食O(∩_∩)O~";
    return $res;
  }
  
  private function menuGo($fromUsername, $next){
    if(!$next){
      $uid = $this->redis->hget('user_'.$fromUsername, 'uid');
      if($uid){
        $picurl = $this->redis->hget('uid_'.$uid, 'picurl');
        $name = $this->redis->hget('uid_'.$uid, 'name');
        if($picurl){
          $addr = $this->redis->hget('uid_'.$uid, 'addr');
          $url = $this->redis->hget('uid_'.$uid, 'url');
          $ret = array(
            0 => array(
              'Title' => "【已选中】".$name,
              'Description' => $addr."。  需要更换请输入\"next\"\n  确认去腐败请输入\"deal\"",
              'PicUrl' => $picurl,
              'Url' => $url,
              ),
            );
          return $ret;
        }else
          return $name."已经被你选中了，换一个请输入\"next\"\n  确认去腐败请输入\"deal\"";
      }
    }
  	$result = $this->randomResult($fromUsername);

    if($this->debug)
      print_r($result);
    $poi = new curlbaidumap();
    $event = $poi->getTuanGouInfo( $result['uid'] );
    
    $this->redis->hset('user_'.$fromUsername, 'uid', $result['uid']);
    if(!$this->redis->hExists('uid_'.$uid, 'name')){
      $this->redis->hset('uid_'.$result['uid'], 'name', $result['name']);
      $this->redis->hset('uid_'.$result['uid'], 'addr', $result['address']);
      $this->redis->hset('uid_'.$result['uid'], 'url', $result['detail_info']['detail_url']);
      if($event)
        $this->redis->hset('uid_'.$result['uid'], 'picurl', urldecode($event[0]['groupon_image']));
      $this->redis->setTimeout('uid_'.$uid, 60*60*48);
    }
    $this->redis->setTimeout('user_'.$fromUsername, 60*60*4);
    


    if( $event ){
      $news = array(
          0 => array(
            'Title' => $result['name'],
            'Description' => $result['address'],
            'PicUrl' => urldecode( $event[0]['groupon_image'] ),
            'Url' => $result['detail_info']['detail_url'],
          ),
      );
      return $news;
    }else{
      return $result['name']. $result['address']."<".$result['detail_info']['detail_url'].">";
    }
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
      $str .= ($i+1).".".$result[$i]['name'];

      //团购信息
      $tuanGou = $poi->getTuanGouInfo( $result[$i]['uid'] );
      if ( $tuanGou ){
        foreach ($tuanGou as $value) {     
          $str .= "|".$value['cn_name'];
        }
      }	
      $str .= "\n";


    }
    return $str;
  }
  
  private function menuJoin($fromUsername, $group){
    if(isset($group)){
      $userGroup = $this->redis->hget('user_'.$fromUsername, 'group');
      if($userGroup){
        if($userGroup != $group){
          $status = $this->redis->hget('user_'.$fromUsername, 'status');
          if($status != 'JOIN'){
            $this->setStatus($fromUsername, 'JOIN');
            return "您已经加入了".$userGroup."组，如果确认加入新组请再次输入join";
          }else{
            return $this->joinGroup($fromUsername, $group);
          }
        }else{
          return 1;
        }
      }else{
        //尚未加入group
        return $this->joinGroup($fromUsername, $group);
      }
    }else{
      return 3;
    }
  }

  private function menuDeal($fromUsername){
    $group = $this->redis->hget('user_'.$fromUsername, 'group');
    $uid = $this->redis->hget('user_'.$fromUsername, 'uid');
    if($group){
      $this->redis->hset('group_'.$group, 'uid', $uid);
    }else{
      $groupNow = $this->redis->get('groupnow');
      if(!$groupNow){
        $this->redis->set('groupnow', 1000);
      }else{
        $this->redis->set('groupnow', $groupNow+1);
        $this->redis->hset('user_'.$fromUsername, 'group', $groupNow);
        $this->redis->hset('group_'.$groupNow, 'uid', $uid);
        $this->redis->hset('group_'.$groupNow, 'membernum', '1');
        $this->redis->hset('group_'.$groupNow, '1', $fromUsername);
        //TODO 组没有过期。。
        $group = $groupNow;
      }
    }
    return $group."订单已创建，腐败结束后不要忘了发送小票照片呦。";
  }

  private function menuDefault() {
  	$res = "欢迎使用午餐吃点啥：\n输入h或者help会有帮助!\n";
    return $res;
  }

  public function joinGroup($fromUsername, $group){
    if($this->redis->hExists('group_'.$group, 'uid')){
      $this->redis->hset('user_'.$fromUsername, 'group', $group);
      $uid = $this->redis->hget('group_'.$group, 'uid');
      //TODO: group 成员记录
      $this->redis->hset('user_'.$fromUsername, 'uid', $uid);
      $deal = $this->redis->hget('group_'.$group, 'deal');
      if($deal){
        $news = array(
          0 => array(
            'Title' => "本次对账单",
            'Description' => "本次腐败的战果",
            'PicUrl' => $deal,
            'Url' => $deal,
          ),
        );
        $this->setStatus($fromUsername, 'DEAL');
        return $news;
      }else{
        $name = $this->redis->hget('uid_'.$uid, 'name');
        $addr = $this->redis->hget('uid_'.$uid, 'addr');
        //$picurl = $this->redis->hget('uid_'.$uid, 'picurl');
        //$url = $this->redis->hget('uid_'.$uid, 'url');
        return "已经选定".$name."[".$addr."]";
      }
    }else{
      return 2;
    }

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
