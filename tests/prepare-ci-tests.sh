#!/bin/bash
set -eu

if [ -z "${OWNCLOUD_BRANCH:-}" ]; then
    echo "No ownclound branch specified."
    exit 1
fi

if [ -z "${OWNCLOUD_DATABASE:-}" ]; then
    echo "No ownclound database specified."
    exit 1
fi

if [ -x "/usr/bin/ci-apt-get-install" ]; then
    APT_GET_INSTALL="/usr/bin/ci-apt-get-install"
else
    APT_GET_INSTALL="apt-get -y install"
fi

IS_GITLAB=
IS_TRAVIS=
SUDO=""
if [ -z "${GITLAB_CI:-}" ]; then
    # Running on Travis CI.
    PROJECT_ROOT="${TRAVIS_BUILD_DIR:-}"
    IS_TRAVIS=1
    SUDO="sudo"
    # Make sure the package lists are up to date.
    sudo apt-get -y update
else
    # Running on Gitlab CI.
    PROJECT_ROOT="${CI_PROJECT_DIR:-}"
    IS_GITLAB=1
fi

if [ -z "${PROJECT_ROOT}" ]; then
    echo "This program must be run on a CI environment (no project root found)."
    exit 1
fi

pushd "${PROJECT_ROOT}"

echo "Installing ocdev ..."
${SUDO} ${APT_GET_INSTALL} \
    python3-jinja2 \
    python3-setuptools

${SUDO} easy_install3 requests==2.10.0
${SUDO} easy_install3 ocdev

case "${OWNCLOUD_DATABASE}" in
    pgsql)
        echo "Setting up postgresql ..."
        createuser -s oc_autotest
        ;;

    mysql)
        echo "Setting up mysql ..."
        if [ ! -z "${IS_GITLAB}" ]; then
            service mysql start
        fi

        mysql -e "create database oc_autotest;"
        mysql -u root -e "CREATE USER 'oc_autotest'@'localhost' IDENTIFIED BY '';"
        mysql -u root -e "grant all on oc_autotest.* to 'oc_autotest'@'localhost';"
        ;;

    *)
        echo "No additional setup required for ${OWNCLOUD_DATABASE}"
        ;;
esac

echo "Installing owncloud ..."
cd ..
rm -rf owncloud
ocdev setup core --dir owncloud --branch "${OWNCLOUD_BRANCH}" --no-history
cp -r "${PROJECT_ROOT}" owncloud/apps/spreedme
cd owncloud/apps/spreedme
cp config/config.php.in config/config.php
cp extra/static/config/OwnCloudConfig.js.in extra/static/config/OwnCloudConfig.js
cd ../../..
cd owncloud
./occ -vvv maintenance:install \
    --database-name oc_autotest \
    --database-user oc_autotest \
    --database-pass \
    --admin-user admin \
    --admin-pass admin \
    --database "${OWNCLOUD_DATABASE}"

echo "Enabling spreedme app ..."
./occ app:enable spreedme

popd
