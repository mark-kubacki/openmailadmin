#!/bin/bash
#
#	This small script is responsible for generating releases
#	of Openmailadmin from SVN->tags.
#

if ! [ "$1" ]; then
    echo "You have to provide a version number."
    exit 2
else
    VERSION=$1
fi

if [ -e /tmp/openmailadmin-${VERSION} ]; then
    echo "Error: Directory already exists in /tmp."
    exit 3
else
    if [ -e ~/html/static/openmailadmin/downloads/openmailadmin-${VERSION}.tbz2 ]; then
	rm ~/html/static/openmailadmin/downloads/openmailadmin-${VERSION}.tbz2
    fi
    svn export https://svn.hurrikane.de/all/openmailadmin/tags/${VERSION} /tmp/openmailadmin-${VERSION} >/dev/null
    cd /tmp/openmailadmin-${VERSION}
    
    rm -rf *.kpf run_tests tests
    
    chmod 0644 $(find -name '*.php*')
    chmod 0600 samples/pam/*
    chmod 0640 samples/config.* samples/postfix/*
    chmod a+x samples/*.daimon.*
    
    cp ~/files/LICENSE .

    cd ..

    if [ -e ~/html/static/openmailadmin/downloads/openmailadmin-${VERSION}.tbz2 ]; then
	rm ~/html/static/openmailadmin/downloads/openmailadmin-${VERSION}.tbz2
    fi
    tar -cjf ~/html/static/openmailadmin/downloads/openmailadmin-${VERSION}.tbz2 openmailadmin-${VERSION}
    rm -rf openmailadmin-${VERSION}
fi
