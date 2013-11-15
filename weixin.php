<?php
//$wechat->getRevFrom()
//$wechat->getRevContent()
require "wechat.class.php";

$option = array(
    'token' => 'rainsunv5',
  );


$wechat = new Wechat($option);
$wechat->valid();
$type = $wechat->getRev()->getRevType();
switch($type){
  case Wechat::MSGTYPE_TEXT:
    include "helper.class.php";
    $helper = new helper($wechat);
    $revContent = $wechat->getRevContent();
    if(strpos($revContent, ' ')){
      $result = explode(' ', $revContent);
      $reply = $helper->smartSwitch($result[0], $wechat->getRevFrom(), $result);
    }else{
      $reply = $helper->smartSwitch($revContent, $wechat->getRevFrom(), null);
    }
    if(is_array($reply))
      $wechat->news($reply)->reply();
    else
      $wechat->text($reply)->reply();
    exit;
    break;
  case Wechat::MSGTYPE_LOCATION:
    include "helper.class.php";
    $helper = new helper();
    $result = $wechat->getRevGeo();
    $result = $helper->setLocation($wechat->getRevFrom(), $result['x'], $result['y']);
    if($result)
      $wechat->text("成功记录您的位置，请继续")->reply();
    else
      $wechat->text("定位失败，请重试")->reply();
    break;
  case Wechat::MSGTYPE_IMAGE:
    include "helper.class.php";
    $url = $wechat->getRevPic();
    $helper = new helper();
    $result = $helper->updatePic($wechat->getRevFrom(), $url);
    $wechat->text($result)->reply();
    break;
  case Wechat::MSGTYPE_EVENT:
    $ret = $wechat->getRevEvent();
    if($ret['event'] == 'subscribe'){
      include "helper.class.php";
      $helper = new helper();
      $reply = $helper->smartSwitch('welcome', $wechat->getRevFrom(), null);
    }
    $wechat->text($reply)->reply();
    break;
  default:
    $weObj->text("help info")->reply();
}