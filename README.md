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
- [ ] 

## Preconfiguración
utilizamos ` diskutil list ` para ubicar nuestro disco, en mi caso **disk2**.
entramos al directorio donde se encuentra la imagen ` cd Downloads/ `.
desmontamos el disco con ` diskutil unmountdisk /dev/disk2 `.
y ejecutamos ` sudo dd if=Raspbian.img of=/dev/disk2 bs=2m `.
