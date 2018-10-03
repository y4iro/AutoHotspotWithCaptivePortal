# Auto-Hotspot with Captive Portal

## Objetivo
Por medio de un sitio web, y a través de una Raspberry® para poder intentar autoconectarse a una red, si no encuentra monte un access point y nos permita ingresar los datos de la red, para auto-conectarte.
## Introducción
Por medio del presente proyecto realizaré **desde cero** la configuración y preparación para utilizar una Raspberry® para:
- Montar un punto de acceso donde nos podremos conectar con cualquier equipo.
- Al conectarse, despliegue un formulario web para solicitar _SSID_ y _PASSWORD_ de nuestro internet.
- Tras enviar el formulario, se conecte directamente a nuestro internet.

## Materiales
- [ ] Memoria microSD de por lo menos 8GB
- [ ] Raspberry Pi®
- [ ] [Última versión de Raspian](https://www.raspberrypi.org/downloads/raspbian/)
- [ ] MacBook (todo el proceso se realizará desde **Terminal**)

## Proceso Completo

### Cargando la imagen | [0](https://www.raspberrypi.org/documentation/configuration/raspi-config.md), [1](https://www.raspberrypi.org), [2](https://hipertextual.com/archivo/2014/04/raspberry-pi-mac/)

Con ayuda de un adaptador conectamos la memoria a la computadora y con ayuda de la **Utilidad de Discos** borramos la memoria, le colocamos de nombre **boot** y con formato **MS-DOS FAT**.

Utilizamos ` diskutil list ` para ubicar nuestro disco, en nuestro caso **disk2**. Entramos al directorio donde se encuentra la imagen previamente extraída ` cd Downloads/ `, desmontamos el disco con ` diskutil unmountdisk /dev/disk2 ` y ejecutamos ` sudo dd if=NOMBRE_DE_LA_IMAGEN.img of=/dev/disk2 bs=2m `, nos solicitará contraseña.

Entramos al directorio ` cd /Volumes/boot/ ` (siendo boot el nombre de la tarjeta) y ejecutamos `touch ssh` para habilitar la conexión por SSH, de igual forma, creamos un nuevo archivo con ` nano wpa_supplicant.conf ` y le agregamos el siguiente código:

```
https://github.com/y4iro/AutoHotspotWithCaptivePortal/blob/master/README.md# /etc/wpa_supplicant/wpa_supplicant.conf

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

### Pre-configurando el dispositivo

Para configuración inicial antes de empezar, debemos ejecutar lo siguiente:

```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get install hostapd
sudo apt-get install dnsmasq
sudo apt-get install iw
sudo apt-get install nginx
sudo apt-get install php-fpm
# sudo apt-get install mysql-server
# sudo apt-get install php-mysql
```

### Montando Access Point | [3](http://www.raspberryconnect.com/network/item/330-raspberry-pi-auto-wifi-hotspot-switch-internet)

Deshabilitamos los procesos con **sudo systemctl disable hostapd** y **sudo systemctl disable dnsmasq** respectivamente. Ejecutamos ` sudo nano /etc/hostapd/hostapd.conf ` y colocamos el siguiente contenido:

```
#2.4GHz setup wifi 80211 b,g,n
interface=wlan0
driver=nl80211
ssid=NOMBRE DE LA RED
hw_mode=g
channel=8
wmm_enabled=0
macaddr_acl=0
auth_algs=1
ignore_broadcast_ssid=0
#wpa=2
#wpa_passphrase=CONTRASEÑA_DE_LA_RED
#wpa_key_mgmt=WPA-PSK
#wpa_pairwise=CCMP TKIP
#rsn_pairwise=CCMP

country_code=US
ieee80211n=1
ieee80211d=1
```

Nota: cambiamos la ssid y si queremos que cuente con contraseña, eliminamos el # de las líneas que lo contienen.

Ejecutamos ` sudo nano /etc/default/hostapd ` y cambiamos la línea **#DAEMON_CONF=""** por **DAEMON_CONF="/etc/hostapd/hostapd.conf"**, de igual forma validamos que se encuentre la siguiente línea comentada **#DAEMON_OPTS=""**, si es así guardamos y cerramos. A contiuación ejecutamos `
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

Realizamos un respaldo del siguiente documento con la línea ` sudo cp /etc/network/interfaces /etc/network/interfaces-bk `, y una vez respaldada, modificamos el documento ` sudo nano /etc/network/interfaces `, borramos su contenido y le escribimos:

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

### Configuración para el Captive Portal | [4](https://brennanhm.ca/knowledgebase/2016/10/raspberry-pi-access-point-and-captive-portal-without-internet/#Configure_Nginx)

Ejecutamos ' sudo nano /etc/hosts ' y agregamos al final de la tabla: **192.160.50.5    NOMBRE_DEL_DOMINIO_QUE_QUEREMOS_UTILIZAR **

Creamos el directorio donde crearemos nuestro sitio web con ` sudo mkdir /usr/share/nginx/html/cp --mode=u+rwx,g+srw,o-w `. Como nginx utiliza el grupo www-data, necesitamos ` sudo chown pi:www-data -R /usr/share/nginx/html ` y copiamos nuestro sitio web dentro de la carpeta en **/usr/share/nginx/html/cp**. En nuestro caso utilizaremos los archivos html y php aquí contenidos.

Modificamos el archivo ` sudo nano /etc/php/7.0/fpm/php.ini ` y nos aseguramos que el archivo exprese la linea como la siguiente: **cgi.fix_pathinfo=0**.

Accedemos a ` sudo nano /etc/nginx/sites-available/default ` y modificamos el archivo para que se vea así:

```
server {
        # Listen for requests over both HTTP and HTTPS
        listen 80;
        listen [::]:80;
        #listen 443 ssl;
        #listen [::]:443;
        # Present a friendly name to the client, instead of an IP address
        server_name NOMBRE_DE_MI_SERVIDOR;
        #Include HTTPS configuration from the snippets directory
        #include snippets/self-signed.conf;
        #include snippets/ssl-params.conf;
 
        root /usr/share/nginx/html/cp;
 
        index index.html index.htm index.nginx-debian.html;
 
        # Redirect requests for /generate_204 to open the captive portal screen
        location /generate_204 {
                return 302 http://NOMBRE_DE_MI_SERVIDOR/index.html;
        }
 
        # Redirect requests for files that don't exist to the download page
        location / {
                try_files $uri $uri/ /index.html;
        }
	
	location ~ \.php$ {
        	include snippets/fastcgi-php.conf;
        	fastcgi_pass unix:/run/php/php7.0-fpm.sock;
    	}
	
}
```

### Configuraciones finales

Es necesario autorizar a los archivos para hacer los respetivos cambios, que queremos dentro de nuestro sistema, por lo que ejecutamos:
```
sudo chmod 555 /usr/share/nginx/html/cp/index.php
sudo chmod 555 /usr/share/nginx/html/cp/rb.php
sudo chmod 722 /etc/wpa_supplicant/wpa_supplicant.conf
```

Para poder concluir es necesario reiniciar todo para que se configure correctamente:

```
sudo service networking restart
sudo service hostapd restart
sudo service dnsmasq restart
sudo service nginx restart
sudo systemctl restart php7.0-fpm
sudo shutdown -r now
```

## Enlaces para más información
Puedes encontrar más información en los siguientes enlaces:

0. [RASPI-CONFIG](https://www.raspberrypi.org/documentation/configuration/raspi-config.md)
1. [Sitio oficial de Raspberry®](https://www.raspberrypi.org)
2. [Cómo empezar a usar Raspberry Pi en Mac](https://hipertextual.com/archivo/2014/04/raspberry-pi-mac/)
3. [Auto WiFi Hotspot Switch Internet](http://www.raspberryconnect.com/network/item/330-raspberry-pi-auto-wifi-hotspot-switch-internet)
4. [Raspberry Pi Access Point and Captive Portal](https://brennanhm.ca/knowledgebase/2016/10/raspberry-pi-access-point-and-captive-portal-without-internet/#Configure_Nginx)

## Enlaces de apoyo
1. [HAC 3. Installing Lighttpd and PHP](https://www.youtube.com/watch?v=gx8oVDK1PUU)
2. [Instalar Linux, Nginx, MySQL, PHP](https://www.digitalocean.com/community/tutorials/como-instalar-linux-nginx-mysql-php-lemp-stack-in-ubuntu-16-04-es)
3. [Uso de permisos UNIX para proteger archivos](https://docs.oracle.com/cd/E24842_01/html/E23286/secfile-60.html)

## Principales problemas presentados y su respectiva solución

### Problema de conexión con la Raspberry por la llave
#### Solución

```
ssh-keygen -R "you server hostname or ip"
```

Para más información en el siguiente [enlace](https://www.digitalocean.com/community/questions/warning-remote-host-identification-has-changed).

### Problema de lenguaje como el siguiente

LANGUAGE = (unset),
        LC_ALL = (unset),

#### Solución
	
``` 
echo "LC_ALL=en_US.UTF-8" >> /etc/environment
echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen
echo "LANG=en_US.UTF-8" > /etc/locale.conf
locale-gen en_US.UTF-8
```

Para más información en el siguiente [enlace](https://unix.stackexchange.com/a/431963).
