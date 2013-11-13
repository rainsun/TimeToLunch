<?php
 
/*从平台获取数据库名*/
$dbname = "xlDVkryrqFtHReOjVTud";
 
/*从环境变量里取host,port,user,pwd*/
$host = getenv('HTTP_BAE_ENV_ADDR_REDIS_IP');
$port = getenv('HTTP_BAE_ENV_ADDR_REDIS_PORT');
$user = getenv('HTTP_BAE_ENV_AK');
$pwd = getenv('HTTP_BAE_ENV_SK');
 
?>