#!/bin/bash
filename="$1$3"
if [ -f $filename ] 
then
    echo "$2" > "$filename"
fi
