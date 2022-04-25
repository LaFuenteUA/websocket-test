let apiData = {
  id : 1001,
  client : null,
  name : '',
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
      'Accept':       'application/json'   // expected data sent back
    },
    redirect: 'follow', // manual, *follow, error
    referrerPolicy: 'no-referrer', // no-referrer, *client
    body: JSON.stringify(data) // body data type must match "Content-Type" header
  });
  return await response.json();
}

function parseMsg(msg) {
  let msgData = JSON.parse(msg);
  let allowObserve = true;
  console.log(JSON.stringify(msgData));
  switch(msgData.command) {
    case 'set_id':     // Client ID after connect to WS
      allowObserve = false;
      apiData.client = msgData.id;
      console.log('ok');
      apiSend(apiData.senders[0], apiData.name, null, 'set_name');
      break;
    case 'error':
      if(msgData.hide) {
        allowObserve = false;
      }
      break;
    // to be continue ...
  }
  if(allowObserve) {
    apiData.observer(msgData);
  }
}

export const apiGetClient = () => {
  return apiData.client;
};

export const apiGetSenders = () => {
  return apiData.senders;
};

export const apiSend = (sender, msg, list = null, command = null) => {
  if(sender && apiData.socket && apiData.client) {
    let message = {
      command : command,
      client : apiData.client, 
      id : apiData.id++, 
      message : msg, 
      list : list,
    };
    switch(sender.id) {
      case 0:   // by Web Socket
        apiData.socket.send(JSON.stringify(message));
        break;
      default:  // by HTTP or Redis
        postUrl(sender.url, message);
    }
  }
};

export const apiConnect = (name, observer) => {
  if(!apiData.socket) {
    apiData.socket = new WebSocket(apiData.senders[0].url);
    apiData.name = name;
    apiData.observer = observer;
    apiData.socket.addEventListener('message', (msg) => {
      parseMsg(msg.data);
    });
  }
};

export const apiDisconnect = () => {
  if(apiData.socket) {
    apiData.socket.close();
    apiData.socket = null;
    apiData.client = null;
    apiData.observer = null;
  }
};