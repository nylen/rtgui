#!/bin/sh

ver=1.8.9
dir=jquery-ui-$ver-files
mkdir -p $dir
unzip -u -o jquery-ui-$ver.custom.zip \
  js/jquery-ui-$ver.custom.min.js \
  css/ui-darkness/jquery-ui-$ver.custom.css \
  css/ui-darkness/images/* \
  -d $dir
