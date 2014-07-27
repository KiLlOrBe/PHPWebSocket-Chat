<?php
// prevent the server from timing out
set_time_limit(0);

// include the web sockets server script (the server is started at the far bottom of this file)
require 'class/class.PHPWebSocket.php';
function pseudo($id, $ip){
	return substr(md5($id.$ip), 0, 10);
}
// when a client sends data to the server
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server;
	$ip = long2ip($Server->wsClients[$clientID][6]);
	$pseudo = pseudo($clientID,$Server->wsClients[$clientID][6]);
	// check if message length is 0
	if ($messageLength == 0) {
		$Server->wsClose($clientID);
		return;
	}

	//The speaker is the only person in the room. Don't let them feel lonely.
	if(sizeof($Server->wsClients) == 1) {
		$Server->wsSend($clientID, "[error]There isn't anyone else in the room.");
	}
	else {
		//Send the message to everyone but the person who said it
		foreach($Server->wsClients as $id => $client) {
			if($id == $clientID) {
				$Server->wsSend($id, '[ymsg]<strong>'.$pseudo.' (You): </strong>'.htmlspecialchars($message));
			}
			else {
				$Server->wsSend($id, '[msg]<strong>'.$pseudo.': </strong>'.htmlspecialchars($message));
			}
		}
	}
}

// when a client connects
function wsOnOpen($clientID) {
	global $Server;
	$ip = long2ip($Server->wsClients[$clientID][6]);
	$pseudo = pseudo($clientID,$Server->wsClients[$clientID][6]);
	$Server->log("$pseudo $ip ($clientID) has connected.");
	//Send a join notice to everyone but the person who joined
	foreach($Server->wsClients as $id => $client) {
		$Server->wsSend($id, "[nbu]".sizeof($Server->wsClients)."/".$Server->getMaxClients());
		if($id != $clientID) {
			$Server->wsSend($id, "[u]<strong>$pseudo</strong> has joined the room.");
		}
	}
}

// when a client closes or lost connection
function wsOnClose($clientID, $status) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	$pseudo = pseudo($clientID,$Server->wsClients[$clientID][6]);
	$Server->log("$pseudo $ip ($clientID) has disconnected.");

	//Send a user left notice to everyone in the room
	foreach($Server->wsClients as $id => $client) {
		$Server->wsSend($id, "[nbu]".sizeof($Server->wsClients)."/".$Server->getMaxClients());
		
		$Server->wsSend($id, "[u]<strong>$pseudo</strong> has left the room.");
	}
}

// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer('127.0.0.1', 9300);

?>