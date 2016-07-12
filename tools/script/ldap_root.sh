#!/usr/bin/env bash
ldapsearch -Y EXTERNAL -H ldapi:/// -b "cn=config" "(olcSuffix=*)" olcSuffix|grep -e olcSuffix: | sed 's/olcSuffix: //g'
