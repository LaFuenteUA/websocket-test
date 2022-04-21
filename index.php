<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>socket test</title>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/js/bootstrap.min.js"></script>
    <script src="/node_modules/vue/dist/vue.global.js"></script>
		<link href="/css/bs.css" rel="stylesheet">
		<link href="/css/local.css" rel="stylesheet">    
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
<script type="text/javascript">
/*
get my key
signup message
*/

const socketUrl = 'ws://lafuente.sb:3001';
const httpUrl = 'http://lafuente.sb:3000';
const redisUrl = 'http://lafuente.sb/call.php';

let socket = null;
let id = 1001;
let client = null;
let clients = [];

async function postUrl(url = '', data = {}) {
  const response = await fetch(url, {
    method: 'POST',
    mode: 'cors', // no-cors, *cors, same-origin
    cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
    credentials: 'same-origin', // include, *same-origin, omit
    headers: {
      'Content-Type': 'application/json',
      // 'Content-Type': 'application/x-www-form-urlencoded'
      "Accept":       "application/json"   // expected data sent back
    },
    redirect: 'follow', // manual, *follow, error
    referrerPolicy: 'no-referrer', // no-referrer, *client
    body: JSON.stringify(data) // body data type must match "Content-Type" header
  });
  return await response.json();
}

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
      postUrl(httpUrl, msg);
		}
	});		
	$('#b-bc-send').click(() => {
		if(msg = buildMsg()) {
      postUrl(redisUrl, msg);
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
</script>