#!/bin/sh
##### -*- mode:shell-script; indent-tabs-mode:nil; sh-basic-offset:2 -*-

TAR=/bin/tar
ZCAT=/bin/gunzip
BZIP=/bin/bzip2
XZ=/bin/xz
WGET=/bin/wget
CURL=/bin/curl

if [ -f "$WGET" ]; then
  DOWNLOAD_CMD=$WGET
elif [ -f "$CURL" ]; then
  DOWNLOAD_CMD="$CURL -L -O"
fi

if [ -n "`echo $1 | grep '://'`" ]; then
  base=$1/
  tarfile=$2
else
  base=http://files.freeswitch.org/downloads/libs/
  tarfile=$1
fi

uncompressed=`echo $tarfile | sed 's/\(\(\.tar\.gz\|\.tar\.bz2\|\.tar\.xz\)\|\(\.tgz\|\.tbz2\)\)$//'`

case `echo $tarfile | sed 's/^.*\.//'` in
  bz2|tbz2) UNZIPPER=$BZIP ;;
  xz) UNZIPPER=$XZ ;;
  gz|tgz|*) UNZIPPER=$ZCAT ;;
esac

if [ ! -d $tarfile ]; then
  if [ ! -f $tarfile ]; then
    rm -fr $uncompressed
    $DOWNLOAD_CMD $base$tarfile
    if [ ! -f $tarfile ]; then
      echo cannot find $tarfile
      exit 1
    fi
  fi
  if [ ! -d $uncompressed ]; then
    $UNZIPPER -c -d $tarfile | $TAR -xf -
  fi
fi

exit 0
