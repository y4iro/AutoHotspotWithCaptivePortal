# ControlCondo V 1.0

## Objetivo
Por medio de un sitio web, y a través de una Raspberry® poder abrir y cerrar las puertas eléctricas de nuestro domicilio.

## Introducción
Por medio del presente proyecto realizaré **desde cero** la configuración y preparación para utilizar una Raspberry® para:
- Montar un punto de acceso donde nos podremos conectar con cualquier equipo.
- Al conectarse, despliegue un formulario web para solicitar _SSID_ y _PASSWORD_ de nuestro internet.
- Tras enviar el formulario, se conecte directamente a nuestro internet.
- Por medio de un sitio web demos la orden de abrir o cerrar.
- Con ayuda de Radio Frecuencia, mandar la señal previamente seleccionada.
- La puerta de acceso abrirá o cerrará.

## Materiales
- [ ] MacBook

## Proceso Completo

### Cargando la imagen | 1
Utilizamos ` diskutil list ` para ubicar nuestro disco, en mi caso **disk2**. 
Entramos al directorio donde se encuentra la imagen ` cd Downloads/ `.
Desmontamos el disco con ` diskutil unmountdisk /dev/disk2 `.
Ejecutamos ` sudo dd if=NOMBRE_DE_LA_IMAGEN.img of=/dev/disk2 bs=2m `, solicita contraseña y tras ingresarla esperamos.
Entramos al directorio con `cd /Volumes/boot/` (siendo boot el nombre de la tarjeta) y ejecutamos `touch ssh` para habilitar la conexión por SSH, de igual forma, creamos un nuevo archivo con ` nano wpa_supplicant.conf ` y le agregamos el siguiente código:

```
# /etc/wpa_supplicant/wpa_supplicant.conf

ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev 
update_config=1

network={
 ssid="NOMBRE_DE_TU_SSID"
 psk="CONTRASEÑA"
 key_mgmt=WPA-PSK 
}
```
Ya para terminar la expulsamos con `sudo diskutil eject /dev/disk2`.
Colocándole la memoria y conectándola a la corriente o a nuestra computadora podremos acceder a ella de dos formas: `ssh pi@DIRECCION_IP_DE_LA_RASP ` o bien, si está directamente a la computadora con ` ssh pi@raspberrypi.local `. En ambos casos nos solicitará la contraseña, `raspberry`.

**Nota**: Al ser un dispositivo para estar conectado siempre a internet, lo mejor será cambiar la contraseña del mismo. Lo podemos hacer ejecutando `sudo passwd` y nos solicita digitarla dos veces.

## Configurando el dispositivo

Colocamos la memoria en la Raspberry® y en terminal ejecutamos ` ssh pi@raspberrypi.local `


### Enlaces Generales
Puedes encontrar más información en los siguientes enlaces:
1. [Cómo empezar a usar Raspberry Pi en Mac](https://hipertextual.com/archivo/2014/04/raspberry-pi-mac/)
