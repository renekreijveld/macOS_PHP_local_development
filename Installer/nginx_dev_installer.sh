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

# Scripts destination
SCRIPTS_DEST="/usr/local/bin"

# MariaDB main config file
MY_CNF_FILE="/opt/homebrew/etc/my.cnf"
MY_CNF_ADDITION="https://raw.githubusercontent.com/renekreijveld/macOS_NginX_local_development/refs/heads/main/MariaDB/my.cnf.addition"

# PHP 7.4 fpm config file
PHP74_WWW_CONF="/opt/homebrew/etc/php/7.4/php-fpm.d/www.conf"
# Source of new PHP 7.4 fpm config file
PHP74_WWW_CONF_NEW="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/PHP_fpm_configs/php7.4.conf"

# PHP 8.3 fpm config file
PHP83_WWW_CONF="/opt/homebrew/etc/php/8.3/php-fpm.d/www.conf"
# Source of new PHP 8.3 fpm config file
PHP83_WWW_CONF_NEW="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/PHP_fpm_configs/php8.3.conf"

# PHP 8.4 fpm config file
PHP84_WWW_CONF="/opt/homebrew/etc/php/8.4/php-fpm.d/www.conf"
# Source of new PHP 8.4 fpm config file
PHP84_WWW_CONF_NEW="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/PHP_fpm_configs/php8.4.conf"

# Location of PHP switcher script
PHP_SWITCHER="${SCRIPTS_DEST}/sphp"
# Source of PHP switcher script
PHP_SWITCHER_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/sphp"

# PHP 7.4 php.ini file
PHP74_INI="/opt/homebrew/etc/php/7.4/php.ini"
# Source of new PHP 7.4 php.ini file
PHP74_INI_NEW="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/PHP_ini_files/php7.4.ini"

# PHP 8.3 php.ini file
PHP83_INI="/opt/homebrew/etc/php/8.3/php.ini"
# Source of new PHP 8.3 php.ini file
PHP83_INI_NEW="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/PHP_ini_files/php8.3.ini"

# PHP 8.4 php.ini file
PHP84_INI="/opt/homebrew/etc/php/8.4/php.ini"
# Source of new PHP 8.4 php.ini file
PHP84_INI_NEW="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/PHP_ini_files/php8.4.ini"

# NginX config
NGINX_CONF="/opt/homebrew/etc/nginx/nginx.conf"
# Source of new nginx config file
NGINX_CONF_NEW="https://gist.githubusercontent.com/renekreijveld/70e31fdb855a8a91ea98150a2e0bc9bb/raw/58b6cf5ce9977fcca8e9fa9f61696cde28dd51fb/nginx.conf"

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

# Scripts
ADDSITE="${SCRIPTS_DEST}/addsite"
ADDSITE_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/addsite"

RESTARTDNSMASQ="${SCRIPTS_DEST}/restartdnsmasq"
RESTARTDNSMASQ_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/restartdnsmasq"

RESTARTMAILPIT="${SCRIPTS_DEST}/restartmailpit"
RESTARTMAILPIT_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/restartmailpit"

RESTARTMARIADB="${SCRIPTS_DEST}/restartmariadb"
RESTARTMARIADB_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/restartmariadb"

RESTARTNGINX="${SCRIPTS_DEST}/restartnginx"
RESTARTNGINX_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/restartnginx"

RESTARTPHPFPM="${SCRIPTS_DEST}/restartphpfpm"
RESTARTPHPFPM_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/restartphpfpm"

STARTDNSMASQ="${SCRIPTS_DEST}/startdnsmasq"
STARTDNSMASQ_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/startdnsmasq"

STARTMAILPIT="${SCRIPTS_DEST}/startmailpit"
STARTMAILPIT_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/startmailpit"

STARTMARIADB="${SCRIPTS_DEST}/startmariadb"
STARTMARIADB_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/startmariadb"

STARTNGINX="${SCRIPTS_DEST}/startnginx"
STARTNGINX_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/startnginx"

STARTPHPFPM="${SCRIPTS_DEST}/startphpfpm"
STARTPHPFPM_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/startphpfpm"

STOPDNSMASQ="${SCRIPTS_DEST}/stopdnsmasq"
STOPDNSMASQ_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/stopdnsmasq"

STOPMAILPIT="${SCRIPTS_DEST}/stopmailpit"
STOPMAILPIT_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/stopmailpit"

STOPMARIADB="${SCRIPTS_DEST}/stopmariadb"
STOPMARIADB_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/stopmariadb"

STOPNGINX="${SCRIPTS_DEST}/stopnginx"
STOPNGINX_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/stopnginx"

STOPPHPFPM="${SCRIPTS_DEST}/stopphpfpm"
STOPPHPFPM_SRC="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main/Scripts/stopphpfpm"

# Username of logged in user
USERNAME=$(whoami)

# Function to install Homebrow formulae
install_formulae() {
    tput clear
    echo "Welcome to the NginX, PHP, MariaDB local macOS development environment installer version ${VERSION}."
    echo " "

    # Install Homebrew formula
    read -p "Press enter to install Homebrew the formula. This will take a while."
    brew tap shivammathur/php
    brew install wget mariadb shivammathur/php/php@7.4 shivammathur/php/php@8.3 shivammathur/php/php@8.4 nginx dnsmasq mkcert nss mailpit
    brew unlink php
    brew link --overwrite --force php@8.3
}

# Function to install and configure mariadb
mariadb_config() {
    tput clear

    # Start mariadb
    read -p "Press enter to start mariadb."
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
    NOW=$(date +"%Y%m%d-%H%M%S")
    cp ${MY_CNF_FILE} ${MY_CNF_FILE}.${NOW}
    # Modify config
    echo " "
    read -p "Press enter to modify configuration file my.cnf. Enter your password when requested."
    curl -fsSL "${MY_CNF_ADDITION}" | sudo tee -a "${MY_CNF_FILE}" > /dev/null
}

# Function to configure php fpm files
configure_php_fpm() {
    tput clear

    # Modify PHP 7.4 FPM config
    read -p "Press enter to update the PHP fpm config files."
    # Backup first
    NOW=$(date +"%Y%m%d-%H%M%S")
    cp ${PHP74_WWW_CONF} ${PHP74_WWW_CONF}.${NOW}
    # Modify config
    curl -fsSL "${PHP74_WWW_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${PHP74_WWW_CONF}" > /dev/null

    # Modify PHP 8.3 FPM config
    # Backup first
    NOW=$(date +"%Y%m%d-%H%M%S")
    cp ${PHP83_WWW_CONF} ${PHP83_WWW_CONF}.${NOW}
    # Modify config
    curl -fsSL "${PHP83_WWW_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${PHP83_WWW_CONF}" > /dev/null

    # Modify PHP 8.4 FPM config
    # Backup first
    NOW=$(date +"%Y%m%d-%H%M%S")
    cp ${PHP84_WWW_CONF} ${PHP84_WWW_CONF}.${NOW}
    # Modify config
    curl -fsSL "${PHP84_WWW_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${PHP84_WWW_CONF}" > /dev/null
}

# Function to install php switcher script
install_php_switcher() {
    # Create php switcher script
    echo " "
    read -p "Press enter to install php switcher script."
    curl -fsSL "${PHP_SWITCHER_SRC}" | sudo tee "${PHP_SWITCHER}" > /dev/null
    sudo chmod +x "${PHP_SWITCHER}"
}

# Function to install XDebug in PHP versions
php_install_xdebug() {
    tput clear
    # Install XDebug in PHP versions
    read -p "Press enter to install XDebug in PHP versions."
    sphp 7.4
    pecl install xdebug-3.1.6

    tput clear
    sphp 8.3
    pecl install xdebug

    tput clear
    sphp 8.4
    pecl install xdebug
}

# Function to configure php.ini files
php_ini_configuration() {
    tput clear
    # Modify php.ini PHP 7.4 
    read -p "Press enter to modify php.ini files. Enter your password when requested."
    # Backup first
    NOW=$(date +"%Y%m%d-%H%M%S")
    mv ${PHP74_INI} ${PHP74_INI}.${NOW}
    # Modify config PHP 7.4
    curl -fsSL "${PHP74_INI_NEW}" | sudo tee "${PHP74_INI}" > /dev/null

    # Modify php.ini PHP 8.3
    # Backup first
    NOW=$(date +"%Y%m%d-%H%M%S")
    mv ${PHP83_INI} ${PHP83_INI}.${NOW}
    # Modify config PHP 8.3
    curl -fsSL "${PHP83_INI_NEW}" | sudo tee "${PHP83_INI}" > /dev/null

    # Modify php.ini PHP 8.4
    # Backup first
    NOW=$(date +"%Y%m%d-%H%M%S")
    mv ${PHP84_INI} ${PHP84_INI}.${NOW}
    # Modify config PHP 8.3
    curl -fsSL "${PHP84_INI_NEW}" | sudo tee "${PHP84_INI}" > /dev/null
}

# Function to configure nginx
nginx_configuration() {
    tput clear
    # Start nginx
    read -p "Press enter to start nginx."
    sudo nginx

    # Modify nginx config
    echo " "
    read -p "Press enter to modify nginx config."
    # Backup first
    NOW=$(date +"%Y%m%d-%H%M%S")
    cp ${NGINX_CONF} ${NGINX_CONF}.${NOW}
    # Modify nginx config
    curl -fsSL "${NGINX_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${NGINX_CONF}" > /dev/null

    echo " "
    read -p "Press enter to install website index en server config templates."
    mkdir -p ${NGINX_TEMPLATES_DIR}
    curl -fsSL "${INDEX_TEMPLATE_SRC}" | sudo tee "${INDEX_TEMPLATE}" > /dev/null
    curl -fsSL "${NGINX_SERVER_TEMPLATE_SRC}" | sudo tee "${NGINX_SERVER_TEMPLATE}" > /dev/null
}

# Function to configure dnsmasq
configure_dnsmasq() {
    tput clear
    read -p "Press enter to configure dnsmasq."
    echo 'address=/.dev.test/127.0.0.1' >> /opt/homebrew/etc/dnsmasq.conf
    sudo brew services start dnsmasq
    sudo mkdir -v /etc/resolver
    sudo bash -c 'echo "nameserver 127.0.0.1" > /etc/resolver/test'
}

# Function to setup local development folders
create_local_folders() {
    tput clear
    read -p "Press enter to create folders for local webprojects."
    mkdir -p ~/Development/Sites
    mkdir -p ~/Development/Backup
    echo '<h1>My User Web Root</h1>' > ~/Development/Sites/index.php
}

# Function to install SSL certificates
install_ssl_certificates() {
    tput clear
    read -p "Press enter to create local Certificate Authority. Enter your password when requested."
    mkcert -install
    mkdir -p ${NGINX_CERTS_DIR}
    cd ${NGINX_CERTS_DIR}
    read -p "Press enter to generate certificates for localhost and *.dev.test."
    mkcert localhost
    mkcert "*.dev.test"
}

# Function to install local scripts
install_local_scripts() {
    tput clear
    read -p "Press enter to install scripts."

    curl -fsSL "${ADDSITE_SRC}" | sudo tee "${ADDSITE}" > /dev/null
    sudo chmod +x "${ADDSITE}"

    curl -fsSL "${RESTARTDNSMASQ_SRC}" | sudo tee "${RESTARTDNSMASQ}" > /dev/null
    sudo chmod +x "${RESTARTDNSMASQ}"

    curl -fsSL "${RESTARTMAILPIT_SRC}" | sudo tee "${RESTARTMAILPIT}" > /dev/null
    sudo chmod +x "${RESTARTMAILPIT}"

    curl -fsSL "${RESTARTMARIADB_SRC}" | sudo tee "${RESTARTMARIADB}" > /dev/null
    sudo chmod +x "${RESTARTMARIADB}"

    curl -fsSL "${RESTARTNGINX_SRC}" | sudo tee "${RESTARTNGINX}" > /dev/null
    sudo chmod +x "${RESTARTNGINX}"

    curl -fsSL "${RESTARTPHPFPM_SRC}" | sudo tee "${RESTARTPHPFPM}" > /dev/null
    sudo chmod +x "${RESTARTPHPFPM}"

    curl -fsSL "${STARTDNSMASQ_SRC}" | sudo tee "${STARTDNSMASQ}" > /dev/null
    sudo chmod +x "${STARTDNSMASQ}"

    curl -fsSL "${STARTMAILPIT_SRC}" | sudo tee "${STARTMAILPIT}" > /dev/null
    sudo chmod +x "${STARTMAILPIT}"

    curl -fsSL "${STARTMARIADB_SRC}" | sudo tee "${STARTMARIADB}" > /dev/null
    sudo chmod +x "${STARTMARIADB}"

    curl -fsSL "${STARTNGINX_SRC}" | sudo tee "${STARTNGINX}" > /dev/null
    sudo chmod +x "${STARTNGINX}"

    curl -fsSL "${STARTPHPFPM_SRC}" | sudo tee "${STARTPHPFPM}" > /dev/null
    sudo chmod +x "${STARTPHPFPM}"

    curl -fsSL "${STOPDNSMASQ_SRC}" | sudo tee "${STOPDNSMASQ}" > /dev/null
    sudo chmod +x "${STOPDNSMASQ}"

    curl -fsSL "${STOPMAILPIT_SRC}" | sudo tee "${STOPMAILPIT}" > /dev/null
    sudo chmod +x "${STOPMAILPIT}"

    curl -fsSL "${STOPMARIADB_SRC}" | sudo tee "${STOPMARIADB}" > /dev/null
    sudo chmod +x "${STOPMARIADB}"

    curl -fsSL "${STOPNGINX_SRC}" | sudo tee "${STOPNGINX}" > /dev/null
    sudo chmod +x "${STOPNGINX}"

    curl -fsSL "${STOPPHPFPM_SRC}" | sudo tee "${STOPPHPFPM}" > /dev/null
    sudo chmod +x "${STOPPHPFPM}"}
}

# Run all installs and configurations
# install_formulae
# mariadb_config
# configure_php_fpm
# install_php_switcher
# php_install_xdebug
# php_ini_configuration
# nginx_configuration
# configure_dnsmasq
# create_local_folders
# install_ssl_certificates
install_local_scripts
