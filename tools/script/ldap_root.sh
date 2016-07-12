#!/usr/bin/env bash
ldapsearch -Y EXTERNAL -H ldapi:/// -b "cn=config" "(olcSuffix=*)" olcSuffix|sed 's/.*olcSuffix: //g' | sed 's/ # search.*//g'
