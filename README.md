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
- [ ] Memoria microSD de por lo menos 8GB
- [ ] Raspberry Pi®
- [ ] [última versión de Raspian](https://www.raspberrypi.org/downloads/raspbian/)
- [ ] MacBook (todo el proceso se realizará desde **Terminal**)

## Proceso Completo

### Cargando la imagen | 1

Con ayuda de un adaptador conectamos la memoria a la computadora y con ayuda de la **Utilidad de Discos** borramos la memoria, le colocamos de nombre **boot** y con formato **MS-DOS FAT**.

Utilizamos ` diskutil list ` para ubicar nuestro disco, en nuestro caso **disk2**. Entramos al directorio donde se encuentra la imagen previamente extraída ` cd Downloads/ `, desmontamos el disco con ` diskutil unmountdisk /dev/disk2 ` y ejecutamos ` sudo dd if=NOMBRE_DE_LA_IMAGEN.img of=/dev/disk2 bs=2m `, nos solicitará contraseña.

Entramos al directorio ` cd /Volumes/boot/ ` (siendo boot el nombre de la tarjeta) y ejecutamos `touch ssh` para habilitar la conexión por SSH, de igual forma, creamos un nuevo archivo con ` nano wpa_supplicant.conf ` y le agregamos el siguiente código:

```
# /etc/wpa_supplicant/wpa_supplicant.conf

ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev 
update_config=1

network={
 ssid="NOMBRE_DE_SSID"
 psk="CONTRASEÑA_DE_SSID"
 key_mgmt=WPA-PSK 
}
```
Ya para terminar la expulsamos con ` sudo diskutil eject /dev/disk2 `. Colocándole la memoria y conectándola a la corriente o a nuestra computadora podremos acceder a ella de dos formas: `ssh pi@DIRECCION_IP_DE_LA_RASPBERRY ` o bien, si está directamente a la computadora con ` ssh pi@raspberrypi.local `. En ambos casos nos solicitará la contraseña, que por default es `raspberry`.

**Nota**: Al ser un dispositivo para estar conectado siempre a internet, lo mejor será cambiar la contraseña del mismo. Lo podemos hacer ejecutando `sudo passwd` y nos solicita digitarla dos veces.

## Configurando el dispositivo

Para configuración inicial antes de empezar, debemos ejecutar lo siguiente:
```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get install hostapd
sudo apt-get install dnsmasq
# curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
# sudo apt-get install -y nodejs
```
Nota: Las lineas que inician con # son para elementos que ocuparé para después terminado el proyecto, por lo que no son necesarias en vuestra instalación.

## Montando Access Point

Deshabilitamos los procesos con **sudo systemctl disable hostapd** y **sudo systemctl disable dnsmasq** respectivamente.

ejecutamos ` sudo nano /etc/hostapd/hostapd.conf ` y colocamos el siguiente contenido:
```
#2.4GHz setup wifi 80211 b,g,n
interface=wlan0
driver=nl80211
ssid=RPiHotspotN
hw_mode=g
channel=8
wmm_enabled=0
macaddr_acl=0
auth_algs=1
ignore_broadcast_ssid=0
wpa=2
wpa_passphrase=1234567890
wpa_key_mgmt=WPA-PSK
wpa_pairwise=CCMP TKIP
rsn_pairwise=CCMP

#80211n - Change GB to your WiFi country code
country_code=GB
ieee80211n=1
ieee80211d=1
```
ejecutamos ` sudo nano /etc/default/hostapd ` y cambiamos la línea `#DAEMON_CONF=""` por `DAEMON_CONF="/etc/hostapd/hostapd.conf"`, de igual forma validamos que se encuentre la siguiente línea **#DAEMON_OPTS=""**, si es así guardamos y cerramos. A contiuación ejecutamos `
sudo nano /etc/dnsmasq.conf ` y pegamos al final del archivo las siguientes líneas:
```
#AutoHotspot config
interface=wlan0
bind-dynamic 
server=8.8.8.8
domain-needed
bogus-priv
dhcp-range=192.168.50.150,192.168.50.200,255.255.255.0,12h
```

Realizamos un respaldo del siguiente documento con la línea ` sudo cp /etc/network/interfaces /etc/network/interfaces-backup `, y una vez respaldada, modificamos el documento ` sudo nano /etc/network/interfaces `, borramos su contenido y le escribimos:
```
# interfaces(5) file used by ifup(8) and ifdown(8)
# Please note that this file is written to be used with dhcpcd
# For static IP, consult /etc/dhcpcd.conf and 'man dhcpcd.conf'
# Include files from /etc/network/interfaces.d:
source-directory /etc/network/interfaces.d
```

Entramos a ` sudo nano /etc/sysctl.conf ` y buscamos la línea **#net.ipv4.ip_forward=1** y eliminamos el `#`.

### Enlaces Generales
Puedes encontrar más información en los siguientes enlaces:
1. [Sitio oficial de Raspberry®](https://www.raspberrypi.org)
2. [Cómo empezar a usar Raspberry Pi en Mac](https://hipertextual.com/archivo/2014/04/raspberry-pi-mac/)
3. [Auto WiFi Hotspot Switch Internet](http://www.raspberryconnect.com/network/item/330-raspberry-pi-auto-wifi-hotspot-switch-internet)
4. [WARNING: REMOTE HOST IDENTIFICATION HAS CHANGED](https://www.digitalocean.com/community/questions/warning-remote-host-identification-has-changed)
5. [Comando inicial para agregar en el](https://www.raspberrypi.org/documentation/configuration/raspi-config.md)


### TO ADD LATER

LANGUAGE = (unset),
        LC_ALL = (unset),
```

# echo "LC_ALL=en_US.UTF-8" >> /etc/environment
# echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen
# echo "LANG=en_US.UTF-8" > /etc/locale.conf
# locale-gen en_US.UTF-8
```


These commands saved my life
https://unix.stackexchange.com/a/431963
