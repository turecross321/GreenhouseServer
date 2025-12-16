# Hur man använder GreenhouseServer

## Bra att veta

GreenhouseServer är en websocket-server som gör det möjligt för olika delar av växthuset att kunna kommunicera med varandra. Vi har delat upp klienter i två olika typer:

- Controller = Enheter som styr växthuset. Exempelvis en hemsida, en app, eller en fysisk kontrollpanel. En controller är inte en del av växthuset, och behöver inte alltid vara ansluten till servern.
- Worker = Enheter som är del av växthuset. Exempelvis en Arduino som har en temperatursensor eller en Raspberry Pi som kontrollerar en vattenpump.

## Websocket-meddelande Exempel

### Meddelande från worker till controller

På controller:
`{"receiveFrom": ["temp"]}`

På worker:
`{"sendTo": [{"tunnel": "temp", "recipientType": "controller"}]}|122`

## Javascript library

Vi har skapat en enkel library för kommunikation med servern, som kan importeras i en hemsida.

För att använda den:

1. Ladda ned [greenhouse.js](https://github.com/turecross321/GreenhouseServer/blob/main/client_examples/website_library/greenhouse.js), och placera den i samma mapp som din hemsida.
2. Importera `greenhouse.js` i din html:

```html
<!-- import library -->
<script src="greenhouse.js"></script>
```

3. Anslut till server i din egna javascript:

```js
const url = "ws://192.168.30.168:8080?type=controller";
connect(url);

onServerConnect = () => {
  // Denna funktion kommer att köras när du har anslutit till servern.
};

onServerDisconnect = () => {
  // Denna funktion kommer att köras när du tappar anslutning till servern.
};
```

### Hur du skickar meddelanden

```js
const tunnel = "waterPump";
const message = "true";

sendMessage(tunnel, message);
```

`sendMessage` kommer, om inget annat specifieras, bara skicka meddelanden till `worker´s. Du kan specifiera en annan mottagartyp genom att fylla i det tredje argumentet:

```js
sendMessage("testTunnel", "hello world", "controller");
```

### Hur du tar emot meddelanden

```js
const tunnel = "temperature";

function temperatureCallback(information, content) {
  // Denna funktion kommer att kallas när vi tar emot ett meddelande genom `temperature`-tunneln

  // information.fromType är typen av klient som har skickat meddelandet (t.ex. 'worker' eller 'controller)
  // information.fromTunneln är tunneln som meddelandet har skickats genom. Denna information är egentligen dock onödig, eftersom funktionen bara bör kallas när ett meddelande går genom vår förbestämda tunnel.
  // content är meddelandets innehåll, t.ex. 'Hello world'

  console.log(
    `${information.fromType} skickar genom tunneln '${information.fromTunnel}': ${content}`
  );
}

startReceivingFromTunnel(tunnel, temperatureCallback);
```

OBS! Glöm inte att sluta lyssna på en tunnel om du inte längre vill ta emot något.

```js
const tunnel = "temperature";
stopReceivingFromTunnel(temperature);
```
