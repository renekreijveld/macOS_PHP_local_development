#!/bin/bash

# nginx_dev_installer -- Install a NginX, PHP, MariaDB development environment on a macOS system.
#
# This development installation runs with PHP 7.4, 8.3 and 8.4.
#
# After installation you can install and run websites on macOS.
# Websites will have a SSL certificate installed.
# For email testing mailpit is provided.
#
# Also a set of easy-to-use command line scripts will be installed to add and delete websites and database and to create website and database backups.
#
# Copyright 2025 René Kreijveld - email@renekreijveld.nl
# This script is free software; you may redistribute it and/or modify it. This script comes with no warranties.
#
# Version history
# 1.0 Initial version

VERSION="1.0"

# MariaDB main config file
MY_CNF_FILE="/opt/homebrew/etc/my.cnf"
MY_CNF_ADDITION="https://gist.githubusercontent.com/renekreijveld/30a81f570f297a345474eea5d681d17c/raw/d39a544e18d2433023852cbc851bb3fd7ffd3daa/my.cfg.extra"

# PHP 7.4 fpm config file
PHP74_WWW_CONF="/opt/homebrew/etc/php/7.4/php-fpm.d/www.conf"
# Source of new PHP 7.4 fpm config file
PHP74_WWW_CONF_NEW="https://gist.githubusercontent.com/renekreijveld/e845ba3ccfe4519b19273539f5258f68/raw/f603e291ee0691ad1614ce2799073ea2342a38c4/www_conf_php74"

# PHP 8.3 fpm config file
PHP83_WWW_CONF="/opt/homebrew/etc/php/8.3/php-fpm.d/www.conf"
# Source of new PHP 8.3 fpm config file
PHP83_WWW_CONF_NEW="https://gist.githubusercontent.com/renekreijveld/def8491afb91d547e540bf3fb629c24e/raw/4e303b4d2cc2f54911270c635a1cd0c196f41715/www_conf_php83"

# PHP 8.4 fpm config file
PHP84_WWW_CONF="/opt/homebrew/etc/php/8.4/php-fpm.d/www.conf"
# Source of new PHP 8.4 fpm config file
PHP84_WWW_CONF_NEW="https://gist.githubusercontent.com/renekreijveld/9b67250f6114f1bbe55726557e7a6ee8/raw/32d0c558c19888bdac7e64e3484e43f9a7218a75/www_conf_php84"

# Location of PHP switcher script
PHP_SWITCHER="/usr/local/bin/sphp"
# Source of PHP switcher script
PHP_SWITCHER_SCRIPT="https://gist.githubusercontent.com/renekreijveld/58e255f475068bfb785cf3f2d1b0a503/raw/ae364677681502b888d11f603a1f44304803055e/sphp"

# PHP 7.4 php.ini file
PHP74_INI="/opt/homebrew/etc/php/7.4/php.ini"
# Source of new PHP 7.4 php.ini file
PHP74_INI_NEW="https://gist.githubusercontent.com/renekreijveld/51827b13f2f8b3d7f6329d8da861252e/raw/dc6c8c967e68e818a33211324263f975969208d2/php_ini_php74"

# PHP 8.3 php.ini file
PHP83_INI="/opt/homebrew/etc/php/8.3/php.ini"
# Source of new PHP 8.3 php.ini file
PHP83_INI_NEW="https://gist.githubusercontent.com/renekreijveld/c89e428d4860559e933a2dd4c125e060/raw/c4b54cbbac423e8166bf29102b7ab7a365a1a81f/php_ini_php83"

# PHP 8.4 php.ini file
PHP84_INI="/opt/homebrew/etc/php/8.4/php.ini"
# Source of new PHP 8.4 php.ini file
PHP84_INI_NEW="https://gist.githubusercontent.com/renekreijveld/6d2cec19068670a94814f44355b828cc/raw/04f78660d4f0d0cd7ddf9d0fae90ac8a4fedccb1/php_ini_php84"

# NginX config
NGINX_CONF="/opt/homebrew/etc/nginx/nginx.conf"
# Source of new nginx config file
NGINX_CONF_NEW="https://gist.githubusercontent.com/renekreijveld/70e31fdb855a8a91ea98150a2e0bc9bb/raw/58b6cf5ce9977fcca8e9fa9f61696cde28dd51fb/nginx.conf"

# Default index file location
DEFAULT_INDEX="/opt/homebrew/var/www/index.html"
# Source of new default index file
DEFAULT_INDEX_NEW="https://gist.githubusercontent.com/renekreijveld/75f998c3dc0cad16a468fc48710d3412/raw/50dfdbbfc31c88c6276938516087d40d005d8edd/index.php.new"

# NginX templates dir
NGINX_TEMPLATES_DIR="/opt/homebrew/etc/nginx/templates"

# Default website index.php
INDEX_TEMPLATE="/opt/homebrew/etc/nginx/templates/index.php"
# Source of default website index.php
INDEX_TEMPLATE_SRC="https://gist.githubusercontent.com/renekreijveld/c22498dace75e275929172ca2dd05c9e/raw/ad60a648a648bf149af51ed271e3818cd425e36d/index.php.default"

# Default nginx server template
NGINX_SERVER_TEMPLATE="/opt/homebrew/etc/nginx/templates/template.conf"
# Source of default nginx server template
NGINX_SERVER_TEMPLATE_SRC="https://gist.githubusercontent.com/renekreijveld/ee666ea5dd051b57475763e69dd568e3/raw/9f954aad7aead2542b4741efb5e6d020d0c7a633/template.conf"

# NginX servers folder
NGINX_SERVERS_DIR="/opt/homebrew/etc/nginx/servers"

# NginX certificates folder
NGINX_CERTS_DIR="/opt/homebrew/etc/nginx/certs"

# Username of logged in user
USERNAME=$(whoami)

tput clear
echo "Welcome to the NginX, PHP, MariaDB local macOS development environment installer version ${VERSION}."
echo " "

# Install Homebrew formula
read -p "Press enter to install Homebrew the formula. This will take a while."
brew tap shivammathur/php
brew install wget mariadb shivammathur/php/php@7.4 shivammathur/php/php@8.3 shivammathur/php/php@8.4 nginx dnsmasq mkcert nss mailpit
brew unlink php
brew link --overwrite --force php@8.3

tput clear

# Start mariadb
read -p "Formula installations done. Press enter to start mariadb."
brew services start mariadb

# Set mariadb root password
echo " "
read -p "Press enter to set root password in mariadb."
mariadb -e "SET PASSWORD FOR root@localhost = PASSWORD('root');"

# Secure mariadb installation
echo " "
read -p "Press enter to secure MariaDB installation."
echo -e "root\nn\nn\nY\nY\nY\nY" | mariadb-secure-installation

# Modify mariadb config file
# Backup first
NOW=$(date +".%Y%m%d-%H%M%S")
cp ${MY_CNF_FILE} ${MY_CNF_FILE}.${NOW}
# Modify config
echo " "
read -p "Press enter to modify configuration file my.cnf. Enter your password when requested."
curl -fsSL "${MY_CNF_ADDITION}" | sudo tee -a "${MY_CNF_FILE}" > /dev/null

tput clear

# Modify PHP 7.4 FPM config
read -p "MariaDB installation and configuration done. Press enter to update the PHP fpm config files."
# Backup first
NOW=$(date +".%Y%m%d-%H%M%S")
cp ${PHP74_WWW_CONF} ${PHP74_WWW_CONF}.${NOW}
# Modify config
curl -fsSL "${PHP74_WWW_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${PHP74_WWW_CONF}" > /dev/null

# Modify PHP 8.3 FPM config
# Backup first
NOW=$(date +".%Y%m%d-%H%M%S")
cp ${PHP83_WWW_CONF} ${PHP83_WWW_CONF}.${NOW}
# Modify config
curl -fsSL "${PHP83_WWW_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${PHP83_WWW_CONF}" > /dev/null

# Modify PHP 8.4 FPM config
# Backup first
NOW=$(date +".%Y%m%d-%H%M%S")
cp ${PHP84_WWW_CONF} ${PHP84_WWW_CONF}.${NOW}
# Modify config
curl -fsSL "${PHP84_WWW_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${PHP84_WWW_CONF}" > /dev/null

# Create php switcher script
echo " "
read -p "Press enter to install php switcher script."
curl -fsSL "${PHP_SWITCHER_SCRIPT}" | sudo tee "${PHP_SWITCHER}" > /dev/null
sudo chmod 755 "${PHP_SWITCHER}"

tput clear

# Install XDebug in PHP versions
read -p "PHP fpm configurations done. Press enter to install XDebug in PHP versions."
sphp 7.4
pecl install xdebug-3.1.6

tput clear

sphp 8.3
pecl install xdebug

tput clear

sphp 8.4
pecl install xdebug

tput clear

# Modify php.ini PHP 7.4 
read -p "XDebug installations done. Press enter to modify php.ini files. Enter your password when requested."
# Backup first
NOW=$(date +".%Y%m%d-%H%M%S")
mv ${PHP74_INI} ${PHP74_INI}.${NOW}
# Modify config PHP 7.4
curl -fsSL "${PHP74_INI_NEW}" | sudo tee "${PHP74_INI}" > /dev/null

# Modify php.ini PHP 8.3
# Backup first
NOW=$(date +".%Y%m%d-%H%M%S")
mv ${PHP83_INI} ${PHP83_INI}.${NOW}
# Modify config PHP 8.3
curl -fsSL "${PHP83_INI_NEW}" | sudo tee "${PHP83_INI}" > /dev/null

# Modify php.ini PHP 8.4
# Backup first
NOW=$(date +".%Y%m%d-%H%M%S")
mv ${PHP84_INI} ${PHP84_INI}.${NOW}
# Modify config PHP 8.3
curl -fsSL "${PHP84_INI_NEW}" | sudo tee "${PHP84_INI}" > /dev/null

tput clear

# Start nginx
read -p "Php.ini files configured. Press enter to start nginx."
sudo nginx

# Modify nginx config
echo " "
read -p "Press enter to modify nginx config."
# Backup first
NOW=$(date +".%Y%m%d-%H%M%S")
cp ${NGINX_CONF} ${NGINX_CONF}.${NOW}
# Modify nginx config
curl -fsSL "${NGINX_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${NGINX_CONF}" > /dev/null

# Modify old default index.html
echo " "
read -p "Press enter to modify default index file."
# Backup first
NOW=$(date +".%Y%m%d-%H%M%S")
mv ${DEFAULT_INDEX} ${DEFAULT_INDEX}.${NOW}
# Place new index file
curl -fsSL "${DEFAULT_INDEX_NEW}" | sudo tee "${DEFAULT_INDEX}" > /dev/null

# Install nginx website index and server config templates
echo " "
read -p "Press enter to install website index en server config templates."
mkdir -p ${NGINX_TEMPLATES_DIR}
curl -fsSL "${INDEX_TEMPLATE_SRC}" | sudo tee "${INDEX_TEMPLATE}" > /dev/null
curl -fsSL "${NGINX_SERVER_TEMPLATE_SRC}" | sudo tee "${NGINX_SERVER_TEMPLATE}" > /dev/null

tput clear

# Configure dnsmasq
read -p "Ngninx configurations done. Press enter to configure dnsmasq."
echo 'address=/.dev.test/127.0.0.1' >> /opt/homebrew/etc/dnsmasq.conf
sudo brew services start dnsmasq
sudo mkdir -v /etc/resolver
sudo bash -c 'echo "nameserver 127.0.0.1" > /etc/resolver/test'

tput clear

# Create folders for your local webprojects
read -p "Press enter to create folders for local webprojects."
mkdir -p ~/Development/Sites
mkdir -p ~/Development/Backup
echo '<h1>My User Web Root</h1>' > ~/Development/Sites/index.php

tput clear

# Setup SSL
read -p "Press enter to create local Certificate Authority. Enter your password when requested."
mkcert -install
mkdir -p ${NGINX_CERTS_DIR}
cd ${NGINX_CERTS_DIR}
read -p "Press enter to generate certificates for localhost and *.dev.test."
mkcert localhost
mkcert "*.dev.test"
