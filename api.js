let apiData = {
  id : 1001,
  client : null,
  name : '',
  clients : [],
  message : null,
  error_id : 0,
  error : ''
};

let apiParam = {
  socket : null,
  observer : null,
  senders : [{
    id : 0,
    name : 'Web Socket',
    url : 'ws://lafuente.sb:3001',
    active : false
  },{
    id : 1,
    name : 'HTTP Post',
    url : 'http://lafuente.sb:3000',
    active : false
  },{
    id : 2,
    name : 'Redis Broadcast',
    url : 'http://lafuente.sb/call.php',
    active : false
  },]
};

async function postUrl(url = '', data = {}) {
  const response = await fetch(url, {
    method: 'POST',
    mode: 'cors', // no-cors, *cors, same-origin
    cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
    credentials: 'same-origin', // include, *same-origin, omit
    headers: {
      'Content-Type': 'application/json',  // or 'application/x-www-form-urlencoded'
      "Accept":       'application/json'   // expected data sent back
    },
    redirect: 'follow', // manual, *follow, error
    referrerPolicy: 'no-referrer', // no-referrer, *client
    body: JSON.stringify(data) // body data type must match "Content-Type" header
  });
  return await response.json();
}

function parseMsg(msg) {
  let msgData = JSON.parse(msg);
  if(Number.isInteger(msgData.channel)) {
    switch(msgData.channel) {
      case 1:     // Client ID after connect to WS
        apiData.client = msgData.id;
        break;
      case 2:     // Active clients list
        apiData.clients = msgData.clients;
        break;
    }
  }
  apiData.message = msgData;
  apiParam.observer(apiData);
}

export const apiGetSenders = () => {
  return apiParam.senders;
};

export const apiSend = (sender, msg, list) => {
  if(sender && apiParam.socket && apiData.client) {
    let message = {"client" : apiData.client, "id" : apiData.id++, "message" : msg, 'list' : list};
    switch(sender.id) {
      case 0:   // by Web Socket
        apiParam.socket.send(JSON.stringify(message));
        break;
      default:  // by HTTP or Redis
        postUrl(sender.url, message);
    }
  }
};

export const apiConnect = (name, observer) => {
  if(!apiParam.socket) {
    apiParam.socket = new WebSocket(apiParam.senders[0].url);
    apiData.name = name;
    apiParam.observer = observer;
    apiParam.socket.addEventListener("message", (msg) => {
      parseMsg(msg.data);
    });
  }
};

export const apiDisconnect = () => {
  if(apiParam.socket) {
    apiParam.socket.close();
    apiParam.socket = null;
    apiData.client = null;
    apiData.clients = [];
    apiParam.observer = null;
  }
};