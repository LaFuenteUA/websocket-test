/*
Message fields:

client, int - sender id, null for server
id, int - message index, by sender. client.id - unique message id
message, string - message body
message, array object - list of active clients
channel, string - sender channel name, null for server
list, array int - list of receivers, or for all
list, null - for all

command, string - command 
  for node server:  
    set_name - sen client name to 'message'
  for client:
    set_id - set client id to 'id'
    clients - list of active clients update to 'message'
    error - error content in 'message'
hide, bool - for client: don't show in chat

*/
const express = require('express');
const http = require('http');
const url = require('url');
const cors = require('cors');
const Redis = require('ioredis');
const WebSocket = require('ws');
const app = express();
const server = http.createServer(app);
const redis = new Redis();
const io = new WebSocket.Server({port: 3001});
const corsOptions = {
	origin: ["http://lafuente.sb","http://lafuente.sb/call.php","http://lafuente.sb/main.php"],
  methods: ["POST", "GET"],
	credentials: true,
	optionsSuccessStatus: 200
};

app.use(cors(corsOptions));
const MAX_SOCKETS = 100;
const urlencodedParser = express.urlencoded({extended: false});
const jsonParser = express.json();

let sockets = new Map();
let socket_idx = 0;

redis.subscribe('png-news');

redis.on('message', (channel, message) => { 
  processMessage(message, 'Broadcast ' + channel);
});

io.on('connection', (socket) => {
  let socket_id = pushSocket(socket);
  if(socket_id) {
    console.log('User connected. Socket id: ' + socket_id);
    processMessage({command:'set_id', id:socket_id, list:[socket_id], hide:true});

    socket.on('close', () => {
      killSocket(socket_id);
      console.log('User disconnected. Socket id: ' + socket_id);
      clientsList();
    });

    socket.on('message', (message) => {
      processMessage(message.toString(), 'WebSocket');
    });

    // clientsList();
  } else {
    socket.destroy();
    console.log('Too much sockets. Disconnected');
  }
});

function pushSocket(socket, name = '') {
  if(sockets.size < MAX_SOCKETS) {
    socket_idx++;
    sockets.set(socket_idx, {socket: socket, name : name});
    console.log('Total sockets now: ' + sockets.size);    
    return socket_idx;
  }
  return null;
}

function killSocket(socket_id) {
  sockets.delete(socket_id);
}

function clientsList(list = null) {
  let clients = [];
  sockets.forEach((ssk, idx) => clients.push({id : idx, name : ssk.name}));
  processMessage({ command:'clients', message: clients, list: list, hide:true });
}

function processMessage(message, chan = 'server') {
  if(typeof message == 'string') {
    try {
      message = JSON.parse(message);
    } catch(err) {
      message = {command: 'error', 
        message: 'JSON parse error', 
        list: [message.client]};
    }
  }
  message.channel = chan;
  console.log('Message');
  console.log(JSON.stringify(message));
  let allowTransmitt = true;
  switch(message.command) {
    case 'set_name':
      allowTransmitt = false;
      sockets.get(message.client).name = message.message;
      clientsList();
      break;
  }
  if(allowTransmitt) {
    sockets.forEach((ssk, idx) => {
      if(!(message.list && message.list.length) || message.list.includes(idx)) {
        ssk.socket.send(JSON.stringify(message));
      }
    });
  }
}

app.get('/', (req, res) => {
  let parsed = url.parse(req.url, true);
  processMessage({client: parsed.query.client, id: parsed.query.id, message: parsed.query.message}, 'HTTP GET');
  res.end('{}');
});

app.post('/', jsonParser, (req, res) => {
  if(req.body) {
    processMessage(req.body, 'HTTP POST');
    res.end('{}');
  }
  else {
    res.sendStatus(400);
    res.end();
  }
});

server.listen(3000, () => {
  console.log('Listening on *:3000');
});