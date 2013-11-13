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
    $helper = new helper();
    $reply = $helper->smartSwitch($wechat->getRevContent(), $wechat->getRevFrom());
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
    $wechat->text("图片功能正在开发中!!")->reply();
    break;
  default:
    $weObj->text("help info")->reply();
}