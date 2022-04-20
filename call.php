<?
if(trim($_SERVER["CONTENT_TYPE"]) == "application/json") {
	$msg = json_decode(trim(file_get_contents("php://input")));
	if(json_last_error() == JSON_ERROR_NONE) {
		$redis = new Redis();
		$redis->connect('127.0.0.1', 6379);
		$redis->publish('png-news', json_encode($msg));
	}
}
?>
{}