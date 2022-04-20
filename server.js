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
    socket.on('close', () => {
      killSocket(ssk);
      console.log('User disconnected. Socket id: ' + ssk.id);
    });

    socket.on('message', (message) => {
      emitMessage('' + message, 'WebSocket id: ' + ssk.id + ' ');
    });
  }
  else {
    killSocket(ssk);
    console.log('Too much sockets. Disconnected');
  }
});

function pushSocket(socket) {
  if(sockets.length < MAX_SOCKETS) {
    console.log('Total sockets now: ' + (1 + sockets.length));
    let ssk = {"socket": socket, "id" : socket_idx++};
    sockets.push(ssk);
    return ssk;
  }
  return null;
}

function killSocket(ssk) {
  sockets = sockets.filter(sk => sk.id !== ssk.id);
}

function emitMessage(message, chan) {
  message = JSON.parse(message);
  let txt = chan + ' message no ' + message.id + ' received: ' + message.message;
	console.log(txt);
  sockets.forEach(ssk => ssk.socket.send(txt));
}

app.get('/', (req, res) => {
  let parsed = url.parse(req.url, true);
  emitMessage(JSON.stringify({"id": parsed.query.id,"message": parsed.query.message}), 'HTTP GET');
  res.end();
});

app.post('/', urlencodedParser, (req, res) => {
  if(req.body && req.body.id && req.body.message) {
    emitMessage(JSON.stringify({"id": req.body.id,"message": req.body.message}), 'HTTP POST');
    res.end();
  }
  else {
    res.sendStatus(400);
    res.end();
  }
});

server.listen(3000, () => {
  console.log('Listening on *:3000');
});