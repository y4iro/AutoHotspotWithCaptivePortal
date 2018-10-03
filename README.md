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

Ejecutamos ` sudo nano /usr/bin/autohotspotN ` y le agregamos de contenido el [siguiente script](https://github.com/y4iro/AutoHotspotWithCaptivePortal/blob/master/autohotspotN), y lo hacemos ejecutable con la línea ` sudo chmod +x /usr/bin/autohotspotN `.

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
1. [Installing Lighttpd and PHP](https://www.youtube.com/watch?v=gx8oVDK1PUU)
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
