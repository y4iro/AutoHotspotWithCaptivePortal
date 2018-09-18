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

### Configurando el dispositivo

Para configuración inicial antes de empezar, debemos ejecutar lo siguiente:
```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get install hostapd
sudo apt-get install dnsmasq
sudo apt-get install iw
# curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
# sudo apt-get install -y nodejs
```
Nota: Las lineas que inician con # son para elementos que ocuparé para después terminado el proyecto, por lo que no son necesarias en vuestra instalación.

### Montando Access Point

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

Entramos a ` sudo nano /etc/sysctl.conf ` y buscamos la línea **#net.ipv4.ip_forward=1** y eliminamos el `#`. Posteriormente modificamos ` sudo nano /etc/dhcpcd.conf ` y al final del archivo agregamos **nohook wpa_supplicant**.

Creamos un nuevo comando ` sudo nano /etc/systemd/system/autohotspot.service ` y le agregamos de contenido:
```
[Unit]
Description=Automatically generates an internet Hotspot when a valid ssid is not in range
After=multi-user.target
[Service]
Type=oneshot
RemainAfterExit=yes
ExecStart=/usr/bin/autohotspotN
[Install]
WantedBy=multi-user.target
```
y habilitamos el servicio con ` sudo systemctl enable autohotspot.service `.

### Script que nos permitirá intercambiar automáticamente entre Access Point y Wifi Client

Ejecutamos ` sudo nano /usr/bin/autohotspotN ` y le agregamos de contenido:
```
#!/bin/bash
#version 0.95-4-N/HS-I

#You may share this script on the condition a reference to RaspberryConnect.com 
#must be included in copies or derivatives of this script. 

#Network Wifi & Hotspot with Internet
#A script to switch between a wifi network and an Internet routed Hotspot
#A Raspberry Pi with a network port required for Internet in hotspot mode.
#Works at startup or with a seperate timer or manually without a reboot
#Other setup required find out more at
#http://www.raspberryconnect.com

wifidev="wlan0" #device name to use. Default is wlan0.
ethdev="eth0" #Ethernet port to use with IP tables
#use the command: iw dev ,to see wifi interface name 

IFSdef=$IFS
cnt=0
#These four lines capture the wifi networks the RPi is setup to use
wpassid=$(awk '/ssid="/{ print $0 }' /etc/wpa_supplicant/wpa_supplicant.conf | awk -F'ssid=' '{ print $2 }' ORS=',' | sed 's/\"/''/g' | sed 's/,$//')
IFS=","
ssids=($wpassid)
IFS=$IFSdef #reset back to defaults


#Note:If you only want to check for certain SSIDs
#Remove the # in in front of ssids=('mySSID1'.... below and put a # infront of all four lines above
# separated by a space, eg ('mySSID1' 'mySSID2')
#ssids=('mySSID1' 'mySSID2' 'mySSID3')

#Enter the Routers Mac Addresses for hidden SSIDs, seperated by spaces ie 
#( '11:22:33:44:55:66' 'aa:bb:cc:dd:ee:ff' ) 
mac=()

ssidsmac=("${ssids[@]}" "${mac[@]}") #combines ssid and MAC for checking

createAdHocNetwork()
{
    echo "Creating Hotspot"
    ip link set dev "$wifidev" down
    ip a add 192.168.50.5/24 brd + dev "$wifidev"
    ip link set dev "$wifidev" up
    dhcpcd -k "$wifidev" >/dev/null 2>&1
    iptables -t nat -A POSTROUTING -o "$ethdev" -j MASQUERADE
    iptables -A FORWARD -i "$ethdev" -o "$wifidev" -m state --state RELATED,ESTABLISHED -j ACCEPT
    iptables -A FORWARD -i "$wifidev" -o "$ethdev" -j ACCEPT
    systemctl start dnsmasq
    systemctl start hostapd
    echo 1 > /proc/sys/net/ipv4/ip_forward
}

KillHotspot()
{
    echo "Shutting Down Hotspot"
    ip link set dev "$wifidev" down
    systemctl stop hostapd
    systemctl stop dnsmasq
    iptables -D FORWARD -i "$ethdev" -o "$wifidev" -m state --state RELATED,ESTABLISHED -j ACCEPT
    iptables -D FORWARD -i "$wifidev" -o "$ethdev" -j ACCEPT
    echo 0 > /proc/sys/net/ipv4/ip_forward
    ip addr flush dev "$wifidev"
    ip link set dev "$wifidev" up
    dhcpcd  -n "$wifidev" >/dev/null 2>&1
}

ChkWifiUp()
{
	echo "Checking WiFi connection ok"
        sleep 20 #give time for connection to be completed to router
	if ! wpa_cli -i "$wifidev" status | grep 'ip_address' >/dev/null 2>&1
        then #Failed to connect to wifi (check your wifi settings, password etc)
	       echo 'Wifi failed to connect, falling back to Hotspot.'
               wpa_cli terminate "$wifidev" >/dev/null 2>&1
	       createAdHocNetwork
	fi
}


FindSSID()
{
#Check to see what SSID's and MAC addresses are in range
ssidChk=('NoSSid')
i=0; j=0
until [ $i -eq 1 ] #wait for wifi if busy, usb wifi is slower.
do
        ssidreply=$((iw dev "$wifidev" scan ap-force | egrep "^BSS|SSID:") 2>&1) >/dev/null 2>&1 
        echo "SSid's in range: " $ssidreply
        echo "Device Available Check try " $j
        if (($j >= 10)); then #if busy 10 times goto hotspot
                 echo "Device busy or unavailable 10 times, going to Hotspot"
                 ssidreply=""
                 i=1
	elif echo "$ssidreply" | grep "No such device (-19)" >/dev/null 2>&1; then
                echo "No Device Reported, try " $j
		NoDevice
        elif echo "$ssidreply" | grep "Network is down (-100)" >/dev/null 2>&1 ; then
                echo "Network Not available, trying again" $j
                j=$((j + 1))
                sleep 2
	elif echo "$ssidreplay" | grep "Read-only file system (-30)" >/dev/null 2>&1 ; then
		echo "Temporary Read only file system, trying again"
		j=$((j + 1))
		sleep 2
	elif ! echo "$ssidreply" | grep "resource busy (-16)"  >/dev/null 2>&1 ; then
               echo "Device Available, checking SSid Results"
		i=1
	else #see if device not busy in 2 seconds
                echo "Device unavailable checking again, try " $j
		j=$((j + 1))
		sleep 2
	fi
done

for ssid in "${ssidsmac[@]}"
do
     if (echo "$ssidreply" | grep "$ssid") >/dev/null 2>&1
     then
	      #Valid SSid found, passing to script
              echo "Valid SSID Detected, assesing Wifi status"
              ssidChk=$ssid
              return 0
      else
	      #No Network found, NoSSid issued"
              echo "No SSid found, assessing WiFi status"
              ssidChk='NoSSid'
     fi
done
}

NoDevice()
{
	#if no wifi device,ie usb wifi removed, activate wifi so when it is
	#reconnected wifi to a router will be available
	echo "No wifi device connected"
	wpa_supplicant -B -i "$wifidev" -c /etc/wpa_supplicant/wpa_supplicant.conf >/dev/null 2>&1
	exit 1
}

FindSSID

#Create Hotspot or connect to valid wifi networks
if [ "$ssidChk" != "NoSSid" ] 
then
       echo 0 > /proc/sys/net/ipv4/ip_forward #deactivate ip forwarding
       if systemctl status hostapd | grep "(running)" >/dev/null 2>&1
       then #hotspot running and ssid in range
              KillHotspot
              echo "Hotspot Deactivated, Bringing Wifi Up"
              wpa_supplicant -B -i "$wifidev" -c /etc/wpa_supplicant/wpa_supplicant.conf >/dev/null 2>&1
              ChkWifiUp
       elif { wpa_cli -i "$wifidev" status | grep 'ip_address'; } >/dev/null 2>&1
       then #Already connected
              echo "Wifi already connected to a network"
       else #ssid exists and no hotspot running connect to wifi network
              echo "Connecting to the WiFi Network"
              wpa_supplicant -B -i "$wifidev" -c /etc/wpa_supplicant/wpa_supplicant.conf >/dev/null 2>&1
              ChkWifiUp
       fi
else #ssid or MAC address not in range
       if systemctl status hostapd | grep "(running)" >/dev/null 2>&1
       then
              echo "Hostspot already active"
       elif { wpa_cli status | grep "$wifidev"; } >/dev/null 2>&1
       then
              echo "Cleaning wifi files and Activating Hotspot"
              wpa_cli terminate >/dev/null 2>&1
              ip addr flush "$wifidev"
              ip link set dev "$wifidev" down
              rm -r /var/run/wpa_supplicant >/dev/null 2>&1
              createAdHocNetwork
       else #"No SSID, activating Hotspot"
              createAdHocNetwork
       fi
fi
```
y lo hacemos ejecutable con la línea ` sudo chmod +x /usr/bin/autohotspotN `.

## Enlaces Generales
Puedes encontrar más información en los siguientes enlaces:
1. [Sitio oficial de Raspberry®](https://www.raspberrypi.org)
2. [Cómo empezar a usar Raspberry Pi en Mac](https://hipertextual.com/archivo/2014/04/raspberry-pi-mac/)
3. [Auto WiFi Hotspot Switch Internet](http://www.raspberryconnect.com/network/item/330-raspberry-pi-auto-wifi-hotspot-switch-internet)
4. [WARNING: REMOTE HOST IDENTIFICATION HAS CHANGED](https://www.digitalocean.com/community/questions/warning-remote-host-identification-has-changed)
5. [Comando inicial para agregar en el](https://www.raspberrypi.org/documentation/configuration/raspi-config.md)

## TO ADD LATER

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
