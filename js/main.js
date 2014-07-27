var Server;
var view = true;
window.onblur = function(){
	view = false;
}
window.onfocus = function(){
	view = true;
	document.title = "Chat";
}
function log(text) {
	$log = $('#log');
	//Add text to log
	var date=new Date()
	var h=date.getHours();
	if (h<10) {h = "0" + h}
	var m=date.getMinutes();
	if (m<10) {m = "0" + m}
	var s=date.getSeconds();
	if (s<10) {s = "0" + s}
	var time = h+":"+m+":"+s;
	$log.append($log.val()+'<table><tr><td class="rowMessage">'+text+'</td><td class="rowTime">'+time+'</td></tr></table>');
	//Autoscroll
	$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
}
function send( text ) {
	Server.send( 'message', text );
}
$(document).ready(function() {
	log('Connecting...');
	Server = new FancyWebSocket('ws://127.0.0.1:9300');
	$('#message').keypress(function(e) {
		if(e.keyCode == 13 && this.value) {
			send(this.value);
			$(this).val('');
		}
	});
	//Let the user know we're connected
	Server.bind('open', function() {
		log( "Connected." );
	});
	//OH NOES! Disconnection occurred.
	Server.bind('close', function( data ) {
		log( "Disconnected." );
	});
	//Log any messages sent from server
	Server.bind('message', function(payload) {
		if(/^\[([^\]]+)\](.+)$/.test(payload)) {
			if(RegExp.$1 == "ymsg" || RegExp.$1 == "u" || RegExp.$1 == "error"){
				log(RegExp.$2);
			}
			else if(RegExp.$1 == "msg"){
				log(RegExp.$2);
				if(view==false) {
					document.title = "* Chat";
				}
			}
			else if(RegExp.$1 == "nbu"){
				document.getElementById("userCo").innerHTML = RegExp.$2;
			}
		}
	});
		Server.connect();
});