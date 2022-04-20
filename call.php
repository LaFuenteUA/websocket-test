<?
if(($id = $_REQUEST['id']) &&
	($msg = $_REQUEST['message']))
{
	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
	$redis->rawCommand('SET', 'msg_'.$id, $msg, 'EX', 20);
	$redis->publish('png-news', json_encode(['id' => $id, 'message' => $msg]));
}
?>
{}