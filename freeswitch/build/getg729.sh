#!/bin/sh

TAR=/bin/tar
ZCAT=/bin/gunzip
WGET=/bin/wget
CURL=/bin/curl

if [ -f "$WGET" ] ; then
    DOWNLOAD_CMD=$WGET
else
    if [ -f "$CURL" ] ; then
        DOWNLOAD_CMD="$CURL -L -O"
    fi
fi

base=http://files.freeswitch.org/g729/
tarfile=$1
url=`echo $tarfile | grep "://"`

if [ ! -z $url ] ; then
    base=$tarfile/
    tarfile=$2
fi

if [ ! -f $tarfile ] ; then
    $DOWNLOAD_CMD $base$tarfile
    if [ ! -f $tarfile ] ; then
	echo cannot find $tarfile
	exit 1
    fi
fi

exit 0

