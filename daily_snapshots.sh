#!/bin/bash
#
#	This small script is responsible for generating daily snapshots
#	of Openmailadmin from SVN->trunk.
#

REVISION=$(svn info https://svn.hurrikane.de/all/openmailadmin/trunk | grep "Revision: ")
NUMMER=${REVISION##*: }
DATUM=`date +'%Y%m%d'`

if ! [ "$NUMMER" ]; then
    echo "Error: Determining current revision number has failed."
elif [ -e /tmp/openmailadmin-svn ]; then
    echo "Error: Directory already exists in /tmp."
elif ! $(ls ~/html/static/openmailadmin/downloads/snapshots/openmailadmin*rev$NUMMER.* >/dev/null 2>&1); then
    cd /tmp
    svn export https://svn.hurrikane.de/all/openmailadmin/trunk openmailadmin-svn >/dev/null
    rm -f openmailadmin-svn/*.kpf
    chmod 0644 $(find ./openmailadmin-svn -name '*.php*')
    chmod 0600 openmailadmin-svn/samples/pam/*
    chmod 0640 openmailadmin-svn/samples/config.*
    chmod a+x openmailadmin-svn/samples/*.daimon.php

    tar -cjf ~/html/static/openmailadmin/downloads/snapshots/openmailadmin-${DATUM}rev${NUMMER}.tbz2 openmailadmin-svn
    
    rm -rf openmailadmin-svn
    cd - >/dev/null
fi
