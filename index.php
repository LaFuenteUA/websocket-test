<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>socket test</title>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    </head>
    <body>
		<div id="app">
			<div id="msg-in"></div><br/><br/>
			<input type="text" id="msg-out"></input><br/><br/>
			<button id="b-connect">WS Connect</button><br/><br/>
			<button id="b-disconnect">WS Disconnect</button><br/><br/>
			<button id="b-ws-send">WS Message</button><br/><br/>
			<button id="b-bc-send">Broadcast Message</button><br/><br/>
			<button id="b-gt-send">Http GET Message</button><br/><br/>
			<button id="b-ps-send">Http POST Message</button>
		</div>
    </body>
</html>	
<style>
	button { width: 100%; }
	#app { text-align: center; width: 200px; margin: 20px auto auto auto }
	#msg-out { width: 100%; height: 30px }
	#msg-in { border: 1px solid black; width: 100%; height: 60px }
</style>
<script type="text/javascript">
/*
fetch vs jquery
get my id and key
send my id
signup message
filter by id
*/

const socketUrl = 'ws://lafuente.sb:3001';
const httpUrl = 'http://lafuente.sb:3000';
const redisUrl = 'http://lafuente.sb/call.php';
let socket = false;
let id = 1001;
$(document).ready( () => {
	$('#b-connect').click( () => {
		socket = new WebSocket(socketUrl);
		socket.addEventListener("message", (msg) => {
		  $('#msg-in').html(msg.data);
		});
	});
	$('#b-ws-send').click(() => {
		if(socket && (msg = buildMsg())) {			
			socket.send(JSON.stringify(msg));
		}
	});
	$('#b-gt-send').click(() => {
		if(msg = buildMsg()) {
			$.get(httpUrl, msg, () => {}, 'json');
		}
	});
	$('#b-ps-send').click(() => {
		if(msg = buildMsg()) {
			$.post(httpUrl, msg, () => {}, 'json');
		}
	});		
	$('#b-bc-send').click(() => {
		if(msg = buildMsg()) {
			$.post(redisUrl, msg, () => {}, 'json');
		}
	});	
	$('#b-disconnect').click(() => {
		if(socket) {
			socket.close();
			socket = false;
		}
	});	
});

function buildMsg() {
	let msg = $('#msg-out').val();
	if(msg) {
		$('#msg-out').val('');
		return {"id": id++, "message" : msg};
	}
	return null;
}
</script>