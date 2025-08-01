#!/bin/sh
##### -*- mode:shell-script; indent-tabs-mode:nil; sh-basic-offset:2 -*-

TAR=/bin/tar
ZCAT=/bin/gunzip
WGET=/bin/wget
CURL=/bin/curl

DIR=`pwd`

if [ -x "$WGET" ]; then
  DOWNLOAD_CMD=$WGET
fi
if [ "x${DOWNLOAD_CMD}" = "x" -a -x "$CURL" ] ; then
  DOWNLOAD_CMD="$CURL -L -O"
fi

base=http://files.freeswitch.org/
tarfile=$1
install=$2

echo -n "#"
pwd
echo "# $0 $1 $2"

if [ -n "$FS_SOUNDS_DIR" ]; then
  [ -d $FS_SOUNDS_DIR ] || mkdir -p $FS_SOUNDS_DIR
  DIR=$FS_SOUNDS_DIR
fi

if [ ! -f $DIR/$tarfile ]; then
  (cd $DIR && $DOWNLOAD_CMD $base$tarfile)
  if [ ! -f $DIR/$tarfile ]; then
    echo "cannot find $tarfile"
    exit 1
  fi
fi

if [ ! -z "$install" ]; then
  test -d $install || mkdir $install
  (cd $install && $ZCAT -c -d $DIR/$tarfile | $TAR xf -)
fi

exit 0
