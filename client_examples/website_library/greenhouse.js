let websocket;
let tunnelCallbacks;

let onServerConnect;
let onServerDisconnect;

function connect(uri, type = "controller") {
    websocket = new WebSocket(uri + "?type=" + type);

    websocket.onopen = onOpen;
    websocket.onmessage = onMessage;
    websocket.onerror = onError;
    websocket.onclose = onClose;

    tunnelCallbacks = new Map();
}

function onOpen() {
    if (onServerConnect) onServerConnect();
}

function onMessage(event) {
    // event.data example value: {"fromType":"controller","fromTunnel":"temp"}|hej
    const parts = event.data.split("|");

    const information = JSON.parse(parts[0]);
    const content = parts[1];

    const callback = tunnelCallbacks.get(information.fromTunnel);
    callback(information, content);

    // tunneln som meddelandet kom från hamnar i:
    // information.fromTunnel

    // typen som meddelandet kommer från hamnar i:
    // information.fromType

    // meddelandet som skickades hamnar i:
    // content
}

function onError() {}

function onClose() {
    if (onServerDisconnect) onServerDisconnect();
}

function sendMessage(tunnel, content, recipientType = "worker") {
    const object = {
    sendTo: [{ tunnel: tunnel, recipientType: recipientType }],
    };
    const formatted = JSON.stringify(object) + "|" + content;

    websocket.send(formatted);
}

function startReceivingFromTunnel(tunnel, callback) {
    const object = {
    receiveFrom: [tunnel],
    };
    const formatted = JSON.stringify(object);

    websocket.send(formatted);
    tunnelCallbacks.set(tunnel, callback);
}

function stopReceivingFrom(tunnel) {
    const object = {
    stopReceivingFrom: [tunnel],
    };
    const formatted = JSON.stringify(object);

    websocket.send(formatted);

    // forget callback function
    map.delete(tunnel);
}