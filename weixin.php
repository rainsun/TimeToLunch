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

    $wechat->text($reply)->reply();
    exit;
    break;
  case Wechat::MSGTYPE_EVENT:
      break;
  case Wechat::MSGTYPE_IMAGE:
      break;
  default:
      $weObj->text("help info")->reply();
}