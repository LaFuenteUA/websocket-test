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
		<div class="container">
      <div class="col-lg-12">
        <h2 id="cnx">{{ connectedStatus }}</h2>
      </div>
      <div class="col-lg-3" id="clientlist" v-if="clients.size">
        <table>
          <tr>
            <td class="minitab"> <input @click="toggleClient(null)" type="checkbox" :checked="allClientsActive"/></td>
            <td class="minitab"><b>ALL</b></td>
          </tr>
          <template v-for="(client, index) in clientsList" :key="index">
            <tr>
              <td class="minitab"><input @click="toggleClient(index)" type="checkbox" :checked="client.active"/></td>
              <td class="minitab">{{ index }}</td>
              <td :class="[(currentClient == index) ? 'minitab-hi' : 'minitab']">{{ client.name }}</td>
              <td class="minitab" style="width : 250px">
                <div class="user-bar" :style="getClientBarStyle(index)">&nbsp</div>
              </td>
            </tr>
          </template>
        </table>
      </div>
      <div class="col-lg-9">
        <chat-list :chat="chat">
        </chat-list>
        <div class="form-group">
		      <label for="msg-out" class="sr-only"></label>
		      <input v-model="message" type="text" class="form-control" id="msg-out" placeholder="Type here" v-show="connected"/>
		    </div>
        <div class="form-group col-lg-8">
		      <input v-model="username" type="text" class="form-control" placeholder="User name to connect (4 chars min.)" :readonly="connected"/>
		    </div>        
        <div class="form-group col-lg-4">
          <button @click="toggleConnect" class="form-control btn btn-success" :disabled="disableToggleConnect">{{ toDoConnect }}</button>
        </div>        
        <template v-for="(sender, idx) in senders" :key="idx">
          <div class="form-group col-lg-4">
            <button @click="sendMsg(sender)" class="form-control btn btn-success" v-show="connected">Send by {{ sender.name }}</button>
          </div>
        </template>
      </div>
		</div>
  </div>
</body>
</html>	

<script type="module">
import { apiGetSenders, apiGetClient, apiConnect, apiDisconnect, apiSend } from "./api.js";

const chatModule = {
  props: {
    chat: {
      type: Array,
      required: true
    }
  },
  template: `<div id="chat">
    <table>
      <template v-for="(msg, idx) in chat" :key="idx">
        <tr>
          <td class="minitab channel">{{ msg.channel }}</td>            
          <td class="minitab client">[{{ msg.client }}] {{ msg.client_name }}</td>
          <td class="minitab message">{{ msg.message }}</td>
        </tr>
      </template>
    </table>
  </div>`
};

const wsTalker = {
  name: "App",
  data() {
    return {
      connected : false,
      username : '',
      clients : new Map(),
      message : '',
      chat : [],
    };
  },
  components: {
    'chat-list' : chatModule
  },
  computed: {
    disableToggleConnect() {
      return !((this.username.length > 3) || this.connected);
    },
    allClientsActive() {
      let total = true;
      this.clients.forEach((client) => { 
        if(!client.active) {
          total = false;
        }
      });
      return total;
    },
    clientsList() {
      return Object.fromEntries(this.clients);
    },
    connectedStatus() {
      return this.connected ? 'Connected' : 'Offline';
    },
    toDoConnect() {
      return this.connected ? 'Disconnect' : 'Connect';
    },
    senders() {
      return apiGetSenders();
    },
    currentClient() {
      return apiGetClient();
    }
  },
  mounted() {
    setInterval(async () => {
      await this.clients.forEach((client) => {
        client.bar -= ((client.bar > 0) ? 0.1 : 0);
      });          
    }, 500);
    
  },
  beforeUnmount() {
    apiDisconnect();
  },
  created() {  
  },
  methods: {  
    receiveMessage(messageData) {
      let allowChat = true;
      if(messageData.command)
      {
        switch(messageData.command) {
          case 'clients':     // Clients list
            allowChat = false;
            if(typeof messageData.message == 'object') {
              this.applyClientsList(messageData.message);
            }
            break;
          case 'close':
            allowChat = false;
            if(this.connected) {
              this.toggleConnect();
            }
            break;
          // to be continue ...
        }
      }
      if(allowChat) {
        this.chatAdd({
          channel : messageData.channel[0],
          client : messageData.client,
          client_name : this.clients.get(messageData.client).name,
          message : messageData.message});
        this.updateClientBar(messageData.client);
      }    
    },
    applyClientsList(list) {
      let exists = new Map(this.clients);
      this.clients.clear();
      list.forEach((client) => {
        let bar = 0;
        let active = false;
        if(exists.has(client.id)) {
          bar = exists.get(client.id).bar;
          active = exists.get(client.id).active;
        }
        this.clients.set(client.id,{ name : client.name, bar : bar, active : active });
      });
    },
    sendMsg(sender) {
      let list = [];
      this.clients.forEach((client, idx) => {
        if(client.active) {
          list.push(idx);
        }
      });
      apiSend(sender, this.message, list);
      this.message = '';
    },
    chatAdd(msg) {
      this.chat.push(msg);
      if(this.chat.length > 10)
        this.chat.shift();
    },
    clientAdd(client) {
      this.clients.set(Number(client.id), client.name ?? 'anonymus');
    },
    toggleConnect() {
      if(this.connected = !this.connected) {
        apiConnect(this.username, this.receiveMessage);
      } else {
        apiDisconnect();
        this.username = '';
        this.clients.clear();
        this.chat = [];
      }
    },
    toggleClient(client_id) {
      client_id = Number(client_id);
      if(client_id) {
        this.clients.get(client_id).active = !this.clients.get(client_id).active;
      } else {
        let sts = !this.allClientsActive;
        this.clients.forEach((clnt) => { clnt.active = sts; });
      }
    },
    updateClientBar(client) {
      this.clients.get(client).bar = 100;
    },
    getClientBarStyle(client) {
      return { 
        width : this.clients.get(Number(client)).bar + '%',
        backgroundColor : 'blue'
      };
    }
  },
  watch: {
  }
};

const app = Vue.createApp(wsTalker);
// app.component('chat-list', chatModule);
app.mount('#app');
</script>