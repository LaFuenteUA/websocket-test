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
      <div class="col-lg-3" id="clientlist" v-if="users.length">
        <div>
          <input @click="toggleUser(null)" type="checkbox" :checked="allUsersActive"/> To all
        </div><br/>
        <table>
          <template v-for="user in users">
            <tr>
              <td><input @click="toggleUser(user)" type="checkbox" :checked="user.active"/></td>
              <td>{{ user.id }}</td>
              <td>{{ user.name }}</td>
              <td width="100">
                <div class="user-bar" :style="getUserBarStyle(user)">&nbsp</div>
              </td>
            </tr>
          </template>
        </table>
      </div>
      <div class="col-lg-9">
        <div class="form-group">
		      <label for="msg-in" class="sr-only"></label>
		      <div id="chat">
            <table>
            <template v-for="msg in chat">
              <tr>
                <td class="channel">{{ msg.channel }}</td>
                <td class="client">{{ msg.client_name }}</td>
                <td class="client-id">[{{ msg.client }}]:</td>
                <td class="message">{{ msg.message }}</td>
              </tr>
            </template>
            </table>
        </div>
		    </div>
        <div class="form-group">
		      <label for="msg-out" class="sr-only"></label>
		      <input v-model="message" type="text" class="form-control" id="msg-out" placeholder="Type here"/>
		    </div>
        <div class="form-group col-lg-8">
		      <input v-model="username" type="text" class="form-control" placeholder="User name" :readonly="connected"/>
		    </div>        
        <div class="form-group col-lg-4">
          <button @click="toggleConnect" class="form-control btn btn-success">{{ toDoConnect }}</button>
        </div>        
        <template v-for="sender in senders">
          <div class="form-group col-lg-4">
            <button @click="sendMsg(sender)" class="form-control btn btn-success">Send by {{ sender.name }}</button>
          </div>
        </template>
      </div>
		</div>
  </div>
</body>
</html>	
<script type="module">
import { apiGetSenders, apiConnect, apiDisconnect, apiSend } from "./api.js";
const wsTalker = {
  name: "App",
  data() {
    return {
      connected : false,
      username : '',
      users : [],
      message : '',
      chat : []
    };
  },
  computed: {
    allUsersActive() {
      let total = true;
      this.users.forEach((user) => { 
        if(!user.active) {
          total = false;
        }
      });
      return total;
    },
    connectedStatus() {
      return this.connected ? 'Connected' : 'Offline';
    },
    toDoConnect() {
      return this.connected ? 'Disconnect' : 'Connect';
    },
    senders() {
      return apiGetSenders();
    }
  },
  mounted() {
    this.userAdd({id : 99, name : 'Piggy', active : false, bar: 75});
    this.userAdd({id : 98, name : 'Figgy', active : true,  bar: 100});
    this.userAdd({id : 95, name : 'Diggy', active : true,  bar: 20});
    this.chatAdd({channel : 'http',   client_name : 'Simon', client : 87, message : 'Hello!'});
    this.chatAdd({channel : 'redis',  client_name : 'Voron', client : 17, message : 'Hello all!'});
    this.chatAdd({channel : 'socket', client_name : 'Kelly', client : 34, message : 'Hello here!'});
    setInterval(async () => {
      await this.users.forEach((user) => {
        user.bar = (user.bar > 0) ? (user.bar-0.05) : 0;
      });          
    }, 50);
  },
  beforeUnmount() {
    apiDisconnect();
  },
  created() {  
  },
  methods: {  
    receiveMessage(apiData) {
      this.chatAdd(apiData.message);
    },
    sendMsg(sender) {
      let list = [];
      this.users.forEach((user) => {
        list.push(user.id);
      });
      apiSend(sender, this.message, list);
      this.message = '';
    },
    chatAdd(msg) {
      this.chat.push(msg);
      if(this.chat.length > 10)
        this.chat.shift();
    },
    userAdd(user) {
      this.users.push({
        id : Number(user.id),
        name : user.name ?? 'anonymus',
        active : user.active ? true : false,
        bar : Number(user.bar ? Math.min(100, user.bar) : 100)
      });
    },
    toggleConnect() {
      if(this.connected = !this.connected) {
        apiConnect(this.username, this.receiveMessage);
      } else {
        apiDisconnect();
        this.username = '';
      }
    },
    toggleUser(user) {
      if(user) {
        user.active = !user.active;
      } else {
        let sts = this.allUsersActive ? false : true;
        this.users.forEach((user) => { user.active = sts; });
      }
    },
    getUserBarStyle(user) {
      return { 
        width : user.bar + '%',
        backgroundColor : 'rgb(' + (55+2*user.bar) + ', 0, ' + (255-2*user.bar) + ')'
      };
    }
  },
  watch: {
  }
};
Vue.createApp(wsTalker).mount('#app');
</script>