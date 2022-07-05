## Log de git

aws_access_key_id='AQLA5M37BDN6FJP76TDC',
aws_secret_access_key='OsK0o/glWwcjk2U3vVEowkvq5t4EiIreB+WdFo1A'
endpoint_url='http://cloud.epsilong.htb')
client('lambda')  
region_name='us-east-1',
endpoint_url='http://cloud.epsilong.htb')
-aws_lambda = session.client('lambda')    
+    endpoint_url='http://cloud.epsilon.htb')
+aws_lambda = session.client('lambda')

## codigo
```bash
if request.method=="POST":
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
```

## Master code

```
0000000000000000000000000000000000000000 7cf92a7a09e523c1c667d13847c9ba22464412f3 root <root@epsilon.htb> 1637143228 +0000	commit (initial): Adding Tracking API Module
7cf92a7a09e523c1c667d13847c9ba22464412f3 c51441640fd25e9fba42725147595b5918eba0f1 root <root@epsilon.htb> 1637143258 +0000	commit: Updatig Tracking API
c51441640fd25e9fba42725147595b5918eba0f1 b10dd06d56ac760efbbb5d254ea43bf9beb56d2d root <root@epsilon.htb> 1637143379 +0000	commit: Adding Costume Site
b10dd06d56ac760efbbb5d254ea43bf9beb56d2d c622771686bd74c16ece91193d29f85b5f9ffa91 root <root@epsilon.htb> 1637170867 +0000	commit: Fixed Typo
```


## head master
c622771686bd74c16ece91193d29f85b5f9ffa91

## code unizp from aws

```python
import json

secret='RrXCv`mrNe!K!4+5`wYq' #apigateway authorization for CR-124

'''Beta release for tracking'''
def lambda_handler(event, context):
    try:
        id=event['queryStringParameters']['order_id']
        if id:
            return {
               'statusCode': 200,
               'body': json.dumps(str(resp)) #dynamodb tracking for CR-342
            }
        else:
            return {
                'statusCode': 500,
                'body': json.dumps('Invalid Order ID')
            }
    except:
        return {
                'statusCode': 500,
                'body': json.dumps('Invalid Order ID')
            }

```

secret='RrXCv`mrNe!K!4+5`wYq' 





