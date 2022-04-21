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
let sockets = [];
let socket_idx = 1;

redis.subscribe('png-news');

redis.on('message', (channel, message) => { 
  emitMessage(message,'Broadcast ' + channel);
});

io.on('connection', (socket) => {
  let ssk = pushSocket(socket);
  if(ssk) {
    console.log('User connected. Socket id: ' + ssk.id);
    emitMessage({'id':ssk.id,'list':[ssk.id],'message':'Connected','hide':true}, 1);
    socket.on('close', () => {
      killSocket(ssk);
      console.log('User disconnected. Socket id: ' + ssk.id);
      userlist();
    });

    socket.on('message', (message) => {
      emitMessage(message.toString(), 'WebSocket id: ' + ssk.id + ' ');
    });
    userlist();
  }
  else {
    killSocket(ssk);
    console.log('Too much sockets. Disconnected');
  }
});

function pushSocket(socket) {
  if(sockets.length < MAX_SOCKETS) {
    let ssk = {"socket": socket, "id" : socket_idx++};
    sockets.push(ssk);
    console.log('Total sockets now: ' + sockets.length);    
    return ssk;
  }
  return null;
}

function killSocket(ssk) {
  sockets = sockets.filter(sk => sk.id !== ssk.id);
}

function userlist() {
  let list = [];
  sockets.forEach(ssk => list.push(ssk.id));
  emitMessage({'id':0, 'clients':list, 'message':'Userlist','hide':true}, 2);
}

function emitMessage(message, chan) {
  if(typeof message == 'string') {
    try {
      message = JSON.parse(message);
    } catch(err) {
      message = {"id":0,"message":"JSON parse error"};
    }
  }
  message.channel = chan;
  let txt = message.channel + ' message no ' + message.id + ' received: ' + message.message;
	console.log(txt);
  sockets.forEach((ssk) => {
    if(!(message.list && message.list.length) || message.list.includes(ssk.id)) {
      ssk.socket.send(JSON.stringify(message));
    }
  });
}

app.get('/', (req, res) => {
  let parsed = url.parse(req.url, true);
  emitMessage({"client":0,"id": parsed.query.id,"message": parsed.query.message}, 'HTTP GET');
  res.end('{}');
});

app.post('/', jsonParser, (req, res) => {
  if(req.body) {
    emitMessage(req.body, 'HTTP POST');
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