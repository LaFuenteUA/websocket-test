<?
if($data = $_REQUEST['data']) {
	$msg = json_decode($data);
	if(json_last_error() == JSON_ERROR_NONE) {
		$redis = new Redis();
		$redis->connect('127.0.0.1', 6379);
		$redis->publish('png-news', json_encode($msg));
	}
}
?>
{}