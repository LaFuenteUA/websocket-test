<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>socket test</title>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<script src="/js/socket.io.js"></script>
    </head>
    <body>
	<input type="text" id="msg"></input>
	<button id="lunch">Отправить</button>
<script type="text/javascript">
let socket;
let id = 1001;
$(document).ready(function() {
	$('#lunch').click(function() {
		let data = {'id': id, 'msg' : $('#msg').val()};
		id++;
		$.post('http://lafuente.sb/call.php',data,() => {}, 'json');
	});
});
</script>
    </body>
</html>