#!/bin/bash

# nginx_dev_installer - Install a local Apache, NginX, PHP, MariaDB, Xdebug, Mailpit development environment on macOS
#
# Written by René Kreijveld - email@renekreijveld.nl
# This script is free software; you may redistribute it and/or modify it.
# This script comes without any warranty. Use it at your own risk, always backup your data and software before running this script.
#
# Version history
# 1.0 Initial version.
# 1.1 Added installation landingpage.
# 1.2 Added checks for existing formulae.
# 1.3 Added separate ini files for Xdebug.
# 1.4 Added installation of PHP 8.1 and 8.2.
# 1.5 Put default settings in a config file

VERSION="1.5"

# Folder where scripts are installed
SCRIPTS_DEST="/usr/local/bin"
INSTALL_LOG="${HOME}/nginx_dev_install.log"
CONFIG_DIR="${HOME}/.config/phpdev"
CONFIG_FILE="${CONFIG_DIR}/config"

# PHP Versions to Install
PHP_VERSIONS=("7.4" "8.1" "8.2" "8.3" "8.4")

# Homebrew Formulae to Install
FORMULAE=("wget" "mariadb" "httpd" "nginx" "dnsmasq" "mkcert" "nss" "mailpit")

# Local scripts to install
LOCAL_SCRIPTS=("addsite" "delsite" "adddb" "deldb" "restartdnsmasq" "restartmailpit" 
    "restartmariadb" "restartnginx" "restartphpfpm" "startdnsmasq" "startmailpit" 
    "startmariadb" "startnginx" "startphpfpm" "stopdnsmasq" "stopmailpit" "xdebug" 
    "stopmariadb" "stopnginx" "stopphpfpm" "startdev" "stopdev" "restartdev" "setrights" "setsitephp")

# Joomla scripts to install
JOOMLA_SCRIPTS=("jfunctions" "jbackup" "jbackupall" "jdbdropall" "jdbdump" "jdbdumpall" "jdbimp" 
    "jlistjoomlas" "joomlainfo" "latestjoomla")

# GitHub Repo Base URL
GITHUB_BASE="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main"

# Logged-in User
USERNAME=$(whoami)

trap "echo 'Installation interrupted. Exiting...'; exit 1" SIGINT

# Function to check if a Homebrew formula is installed
is_installed() {
    brew list --formula | grep -q "^$1\$"
}

# Function to prompt for a value, with the option to keep the current one
prompt_for_input() {
    local current_value="$1"
    local prompt_message="$2"
    local new_value

    if [[ -n "$current_value" ]]; then
        read -p "$prompt_message [$current_value]: " new_value
        # If the user input is empty, keep the current value
        if [[ -z "$new_value" ]]; then
            new_value="$current_value"
        fi
    else
        read -p "$prompt_message: " new_value
    fi

    echo "$new_value"
}

start() {
    clear
    echo "Welcome to the Apache, NginX, PHP, MariaDB, Xdebug, Mailpit local macOS development installer ${VERSION}."
    echo "During installation, you may be prompted for your password."
    echo "When the prompt 'Password:' appears or a popup window that asks your password, type your password and press enter."
    echo " "
    read -p "Press Enter to start the installation, press Ctrl-C to abort."
    touch "${INSTALL_LOG}"
}

prechecks() {
    PRECHECK_FORMULAE=("mariadb" "nginx" "dnsmasq" "mysql" "httpd" "mailhog" "mailpit" "apache2" "php@7.4" "php@8.1"  "php@8.2" "php@8.3" "php@8.4" "php")
    echo "The installer will first check if some required formulae are already installed."
    
    INSTALLED_FORMULAE=()

    for formula in "${PRECHECK_FORMULAE[@]}"; do
        if is_installed "${formula}"; then
            INSTALLED_FORMULAE+=("${formula}")
        fi
    done

    if [ ${#INSTALLED_FORMULAE[@]} -gt 0 ]; then
        echo " "
        echo "Cannot continue. The following formulae are already installed with Homebrew:"
        for formula in "${INSTALLED_FORMULAE[@]}"; do
            echo "  - ${formula}"
        done
        echo " "
        echo "Installing this Apache NginX, PHP, MariaDB, Xdebug, Mailpit environment would give unpredictable results."
        echo "Please uninstall these formulae and run the installer again."
        echo " "
        echo "Make sure to back up your websites and databases before uninstalling!"
        exit 1
    else
        echo "None of the precheck formulae were already installed. Proceeding."
    fi
}

# Check if the configuration file exists
check_configfile() {
    if [[ ! -f "${CONFIG_FILE}" ]]; then
        echo "Error checking configuration file: ${CONFIG_FILE}, exiting."
        exit 1
    fi   
}

ask_defaults() {
    # Create config directory if it doesn't exist
    mkdir -p "${CONFIG_DIR}"
    echo " "
    echo "Before the installarion starts, you can set some default values."
    echo "These values will be used during the installation process and will be used by the various scripts."
    echo "If the default proposed is correct, just press Enter."
    echo " "
    rootfolder=$(prompt_for_input "$HOME/Development/Sites" "Folder path where your websites will be stored:")
    sitesbackup=$(prompt_for_input "$HOME/Development/Backup/mysql" "Folder path for website backups:")
    mariadbbackup=$(prompt_for_input "$HOME/Development/Backup/sites" "Folder path for MariaDB backups:")
    mariadbpw=$(prompt_for_input "root" "Root password for MariaDB:")

    # Write the values to the config file
    NOW=$(date +"%Y-%m-%d %H:%M:%S")
    echo "# Configuration file for php development environment" > "${CONFIG_FILE}"
    echo "# Generated at ${NOW}" >> "${CONFIG_FILE}"
    echo "ROOTFOLDER=${rootfolder}" >> "${CONFIG_FILE}"
    echo "SITESBACKUP=${sitesbackup}" >> "${CONFIG_FILE}"
    echo "MARIADBBACKUP=${mariadbbackup}" >> "${CONFIG_FILE}"
    echo "MARIADBPW=${mariadbpw}" >> "${CONFIG_FILE}"
    check_configfile
}

install_formulae() {
    clear
    echo "Install Homebrew formulae:"

    PHP_REPO="shivammathur/php"
    brew tap "${PHP_REPO}" >>${INSTALL_LOG} 2>&1

    for formula in "${FORMULAE[@]}"; do
        echo "Install ${formula}."
        brew install --quiet ${formula} >>${INSTALL_LOG} 2>&1
    done

    for php_version in "${PHP_VERSIONS[@]}"; do
        echo "Install php ${php_version}."
        brew install --quiet "${PHP_REPO}/php@${php_version}" >>${INSTALL_LOG} 2>&1
    done

    # Set commandline PHP version to 8.3
    brew unlink php >>${INSTALL_LOG} 2>&1
    brew link --overwrite --force php@8.3 >>${INSTALL_LOG} 2>&1
}

configure_mariadb() {
    echo " "
    echo "Configure MariaDB."
    brew services start mariadb >>${INSTALL_LOG} 2>&1
    sleep 5

    source "${CONFIG_FILE}"

    mariadb -e "SET PASSWORD FOR root@localhost = PASSWORD('${MARIADBPW}');"
    echo -e "${MARIADBPW}\nn\nn\nY\nY\nY\nY" | mariadb-secure-installation >>${INSTALL_LOG} 2>&1

    MY_CNF_FILE="/opt/homebrew/etc/my.cnf"
    MY_CNF_ADDITION="${GITHUB_BASE}/MariaDB/my.cnf.addition"

    cp "${MY_CNF_FILE}" "${MY_CNF_FILE}.$(date +%Y%m%d-%H%M%S)"
    curl -fsSL "${MY_CNF_ADDITION}" | tee -a "${MY_CNF_FILE}" > /dev/null
    brew services stop mariadb >>${INSTALL_LOG} 2>&1
    sleep 5
}

configure_php_fpm() {
    for php_version in "${PHP_VERSIONS[@]}"; do
        CONF_FILE="/opt/homebrew/etc/php/${php_version}/php-fpm.d/www.conf"
        BACKUP="${CONF_FILE}.$(date +%Y%m%d-%H%M%S)"
        CONF_NEW="${GITHUB_BASE}/PHP_fpm_configs/php${php_version}.conf"

        cp "${CONF_FILE}" "${BACKUP}"
        curl -fsSL "${CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | tee "${CONF_FILE}" > /dev/null
    done
}

install_php_switcher() {
    echo " "
    echo "Installing PHP switcher script, when the prompt 'Password:' appears, type your password and press enter:"
    curl -fsSL "${GITHUB_BASE}/Scripts/sphp" | sudo tee "${SCRIPTS_DEST}/sphp" > /dev/null
    sudo chmod +x "${SCRIPTS_DEST}/sphp"
}

install_xdebug() {
    echo " "
    echo "Install Xdebug."
    for php_version in "${PHP_VERSIONS[@]}"; do
        echo "Installing Xdebug for php ${php_version}."
        sphp "${php_version}" >>${INSTALL_LOG} 2>&1

        if [ "${php_version}" == "7.4" ]; then
            pecl install xdebug-3.1.6 >>${INSTALL_LOG} 2>&1
        else    
            pecl install xdebug >>${INSTALL_LOG} 2>&1
        fi
    done
}

configure_php_ini() {
    echo " "
    echo "Install php.ini files."
    XDEBUG_NEW="${GITHUB_BASE}/PHP_ini_files/ext-xdebug.ini"
    for php_version in "${PHP_VERSIONS[@]}"; do
        INI_FILE="/opt/homebrew/etc/php/${php_version}/php.ini"
        XDEBUG_INI="/opt/homebrew/etc/php/${php_version}/conf.d/ext-xdebug.ini"
        BACKUP="${INI_FILE}.$(date +%Y%m%d-%H%M%S)"
        INI_NEW="${GITHUB_BASE}/PHP_ini_files/php${php_version}.ini"

        cp "${INI_FILE}" "${BACKUP}"
        curl -fsSL "${INI_NEW}" | tee "${INI_FILE}" > /dev/null
        curl -fsSL "${XDEBUG_NEW}" | tee "${XDEBUG_INI}" > /dev/null
    done
}

configure_nginx() {
    echo " "
    echo "Configure NginX."
    NGINX_CONF="/opt/homebrew/etc/nginx/nginx.conf"
    NGINX_CONF_NEW="${GITHUB_BASE}/NginX/nginx.conf"
    NGINX_TEMPLATES="/opt/homebrew/etc/nginx/templates"
    NGINX_SERVERS="/opt/homebrew/etc/nginx/servers"

    cp "${NGINX_CONF}" "${NGINX_CONF}.$(date +%Y%m%d-%H%M%S)"
    curl -fsSL "${NGINX_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | tee "${NGINX_CONF}" > /dev/null

    mkdir -p "${NGINX_TEMPLATES}" "${NGINX_SERVERS}"
    curl -fsSL "${GITHUB_BASE}/Templates/index.php" | tee "${NGINX_TEMPLATES}/index.php" > /dev/null
    curl -fsSL "${GITHUB_BASE}/Templates/nginx_vhost_template.conf" | tee "${NGINX_TEMPLATES}/template.conf" > /dev/null
}

configure_apache() {
    echo " "
    echo "Configure Apache."
    APACHE_ETC="/opt/homebrew/etc/httpd"
    APACHE_CONF="/opt/homebrew/etc/httpd/httpd.conf"
    APACHE_CONF_NEW="${GITHUB_BASE}/Apache/httpd.conf"
    APACHE_TEMPLATES="${APACHE_ETC}/templates"
    APACHE_VHOSTS="${APACHE_ETC}/vhosts"
    APACHE_VHOSTS_CONF="${APACHE_ETC}/extra/httpd-vhosts.conf"
    APACHE_VHOSTS_CONF_NEW="${GITHUB_BASE}/Apache/extra/httpd-vhosts.conf"

    cp "${APACHE_VHOSTS_CONF}" "${APACHE_VHOSTS_CONF}.$(date +%Y%m%d-%H%M%S)"
    curl -fsSL "${APACHE_VHOSTS_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | tee "${APACHE_VHOSTS_CONF}" > /dev/null
    cp "${APACHE_CONF}" "${APACHE_CONF}.$(date +%Y%m%d-%H%M%S)"
    curl -fsSL "${APACHE_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | tee "${APACHE_CONF}" > /dev/null

    mkdir -p "${APACHE_ETC}/templates" "${APACHE_ETC}/vhosts"
    curl -fsSL "${GITHUB_BASE}/Templates/index.php" | tee "${APACHE_TEMPLATES}/index.php" > /dev/null
    curl -fsSL "${GITHUB_BASE}/Templates/apache_vhost_template.conf" | tee "${APACHE_TEMPLATES}/template.conf" > /dev/null
    curl -fsSL "${GITHUB_BASE}/Apache/vhosts/localhost.conf" | sed "s/your_username/${USERNAME}/g" | tee "${APACHE_VHOSTS}/localhost.conf" > /dev/null
}

configure_dnsmasq() {
    echo " "
    echo "Configure Dnsmasq."
    echo 'address=/.dev.test/127.0.0.1' >> /opt/homebrew/etc/dnsmasq.conf
    sudo mkdir -p /etc/resolver
    echo "nameserver 127.0.0.1" | sudo tee /etc/resolver/test > /dev/null
}

create_local_folders() {
    mkdir -p "${ROOTFOLDER}"
    mkdir -p "${SITESBACKUP}"
    mkdir -p "${MARIADBBACKUP}"
}

install_ssl_certificates() {
    echo " "
    echo "Install local Certificate Authority."
    mkcert -install
    mkdir -p /opt/homebrew/etc/certs
    cd /opt/homebrew/etc/certs
    echo "Create localhost certificate."
    mkcert localhost >>${INSTALL_LOG} 2>&1
    echo "Create *.dev.test wildcard certificate."
    mkcert "*.dev.test" >>${INSTALL_LOG} 2>&1
}

install_local_scripts() {
    echo "Install local scripts."
    for script in "${LOCAL_SCRIPTS[@]}"; do
        echo "Install ${script}."
        curl -fsSL "${GITHUB_BASE}/Scripts/${script}" | sudo tee "${SCRIPTS_DEST}/${script}" > /dev/null
        sudo chmod +x "${SCRIPTS_DEST}/${script}"
    done
}

install_joomla_scripts() {
    echo "Install Joomla scripts."
    for script in "${JOOMLA_SCRIPTS[@]}"; do
        echo "Install ${script}."
        curl -fsSL "${GITHUB_BASE}/Joomla_scripts/${script}" | sudo tee "${SCRIPTS_DEST}/${script}" > /dev/null
        sudo chmod +x "${SCRIPTS_DEST}/${script}"
    done
}

install_root_tools() {
    cd "${ROOTFOLDER}"
    echo "Install landingpage."
    curl -fsSL "${GITHUB_BASE}/Localhost/index.php" > index.php
    echo "<?php phpinfo();" > phpinfo.php
    echo "Install adminer.php script."
    curl -sL "https://www.adminer.org/latest.php" > adminer.php
    echo "<?php phpinfo();" > phpinfo.php
}

the_end() {
    /usr/local/bin/stopphpfpm > /dev/null 2>&1
    echo " "
    echo "Installation completed!"
    echo " "
    echo "The installation log is available at ${INSTALL_LOG}"
    echo " "
    echo "Run 'startdev' to start your environment."
    echo "Enjoy your development setup!"
}

# Execute the script in order
start
prechecks
ask_defaults
install_formulae
configure_mariadb
configure_php_fpm
install_php_switcher
install_xdebug
configure_php_ini
configure_nginx
configure_apache
configure_dnsmasq
create_local_folders
install_ssl_certificates
install_local_scripts
install_joomla_scripts
install_root_tools
the_end
