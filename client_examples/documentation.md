# Hur man använder GreenhouseServer

## Bra att veta

GreenhouseServer är en websocket-server som gör det möjligt för olika delar av växthuset att kunna kommunicera med varandra. Klienter har delats upp i två olika typer:

- Controller = Enheter som styr växthuset. Exempelvis en hemsida, en app, eller en fysisk kontrollpanel. En controller är inte en del av växthuset, och behöver inte alltid vara ansluten till servern.
- Worker = Enheter som är del av växthuset. Exempelvis en Arduino som har en temperatursensor eller en Raspberry Pi som kontrollerar en vattenpump.
