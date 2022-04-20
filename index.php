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
      <table id="tbl">
        <tr>
          <td id="panel" colspan="2">
            <div id="cnx"></div>
          </td>
        </tr>
        <tr>
          <td id="panel">
            <div id="msg-in"></div>
            <input type="text" id="msg-out" placeholder="Type here"></input>
            <button id="b-connect">WS Connect</button>
            <button id="b-disconnect">WS Disconnect</button>
            <button id="b-ws-send">WS Message</button>
            <button id="b-bc-send">Broadcast Message</button>
            <button id="b-ps-send">Http Message</button>
          </td>
          <td id="clientlist">
          </td>
        </tr>
      </table>
		</div>
    </body>
</html>	
<style>
  body { font-family: arial }
	button { margin-top: 10px; width: 100% }
	#app { text-align: center; width: 400px; margin: 20px auto auto auto; }
	#msg-out { margin-top: 10px; width: 100%; height: 30px }
	#msg-in { margin-top: 10px; padding: 4px; border: 1px solid black; width: 100%; height: 60px; text-align: left }
  #tbl {width: 100% }
  #cnx { padding: 4px; border: 0; width: 100%; height: 20px; text-align: left }
  #panel { text-align: center; width: 80% }
  #clientlist { width: 20%; vertical-align: top; padding: 10px; text-align: left }
  .v-list-current {color: blue }
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
let socket = null;
let id = 1001;
let client = null;
let clients = [];

$(document).ready( () => {
	$('#b-connect').click( () => {
		if(!socket) {
			socket = new WebSocket(socketUrl);
			socket.addEventListener("message", (msg) => {
        parseMsg(msg.data);
			});
		}
	});
	$('#b-ws-send').click(() => {
		if(socket && (msg = buildMsg())) {			
			socket.send(JSON.stringify(msg));
		}
	});
	$('#b-ps-send').click(() => {
		if(msg = buildMsg()) {
			$.post(httpUrl, {'data' : JSON.stringify(msg)}, () => {}, 'json');
		}
	});		
	$('#b-bc-send').click(() => {
		if(msg = buildMsg()) {
			$.post(redisUrl,{'data' : JSON.stringify(msg)}, () => {}, 'json');
		}
	});	
	$('#b-disconnect').click(() => {
		if(socket) {
			socket.close();
      clearClient();
			socket = null;
			client = null;
		}
	});	
  clearClient();
});

function buildMsg() {
	if(msg = $('#msg-out').val()) {
		$('#msg-out').val('');
    let list = [];
    $('.v-list:checked').each(function (idx) {
      list.push(Number($(this).attr('v-client')));
    });
		return {"client" : client, "id": id++, "message" : msg, 'list' : list};
	}
	return null;
}

function setClient(id) {
  client = id;
  $('#cnx').html('Connected. ID: ' + client);
}

function clearClient() {
  client = null;
  $('#cnx').html('Disconnected');
  $('#clientlist').html('');
}

function setClients(list) {
  let html = '';
  list.forEach((id) => {
    html += '<input type="checkbox" class="v-list';
    if(id == client) {
      html += ' v-list-current'
    }
    html += ('" v-client="' + id + '"> ' + id + '<br/>');
  });
  $('#clientlist').html(html);
}

function parseMsg(msg) {
  let data = JSON.parse(msg);
  let txt = 'Channel: ' + data.channel + '<br/>ID: ' + data.id + '<br/>Text: ' + data.message;
  if(Number.isInteger(data.channel)) {
    switch(data.channel) {
      case 1:     // Client ID after connect to WS
        setClient(data.id);
        break;
      case 2:     // Active clients list
        setClients(data.clients);
        break;
    }
  }
  if(!data.hide) {
    $('#msg-in').html(txt);
  }
}
</script>