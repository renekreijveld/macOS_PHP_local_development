#!/bin/bash

VERSION="1.1"
SCRIPTS_DEST="/usr/local/bin"
SITESROOT="${HOME}/Development/Sites"
INSTALL_LOG="${HOME}/nginx_dev_install.log"

# MariaDB Config
MY_CNF_FILE="/opt/homebrew/etc/my.cnf"
MY_CNF_ADDITION="https://raw.githubusercontent.com/renekreijveld/macOS_NginX_local_development/refs/heads/main/MariaDB/my.cnf.addition"

# PHP Versions to Install
PHP_VERSIONS=("7.4" "8.3" "8.4")
PHP_REPO="shivammathur/php"

# Homebrew Formulae to Install
FORMULAE=("wget" "mariadb" "nginx" "dnsmasq" "mkcert" "nss" "mailpit")

# Local scripts to install
LOCAL_SCRIPTS=("addsite" "restartdnsmasq" "restartmailpit" "restartmariadb" "restartnginx" "restartphpfpm"
    "startdnsmasq" "startmailpit" "startmariadb" "startnginx" "startphpfpm"
    "stopdnsmasq" "stopmailpit" "stopmariadb" "stopnginx" "stopphpfpm"
    "startdev" "stopdev" "restartdev" "setrights")

# Joomla scripts to install
JOOMLA_SCRIPTS=("jfunctions" "jbackup" "jdbdropall" "jdbdumpall" "jdbimp" "jlistjoomlas" "joomlainfo"
    "latestjoomla")

# GitHub Repo Base URL
GITHUB_BASE="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main"

# Logged-in User
USERNAME=$(whoami)

trap "echo 'Installation interrupted. Exiting...'; exit 1" SIGINT

start() {
    clear
    echo "Welcome to the NginX, PHP, MariaDB local macOS development installer version ${VERSION}."
    read -p "Press Enter to start the installation..."
    touch "${INSTALL_LOG}"
}

install_formulae() {
    clear
    echo "Installing required Homebrew formulae."

    brew tap "${PHP_REPO}" >>${INSTALL_LOG} 2>&1

    for formula in "${FORMULAE[@]}"; do
        echo "Installing ${formula}"
        brew install --quiet ${formula} >>${INSTALL_LOG} 2>&1
    done

    for php_version in "${PHP_VERSIONS[@]}"; do
        echo "Installing PHP ${php_version}"
        brew install --quiet "${PHP_REPO}/php@${php_version}" >>${INSTALL_LOG} 2>&1
    done

    # Set commandline PHP version to 8.3
    brew unlink php >>${INSTALL_LOG} 2>&1
    brew link --overwrite --force php@8.3 >>${INSTALL_LOG} 2>&1
}

mariadb_config() {
    clear
    echo "Configuring MariaDB. Input your password when requested."
    brew services start mariadb >>${INSTALL_LOG} 2>&1
    sleep 5
    mariadb -e "SET PASSWORD FOR root@localhost = PASSWORD('root');"
    echo -e "root\nn\nn\nY\nY\nY\nY" | mariadb-secure-installation >>${INSTALL_LOG} 2>&1

    cp "${MY_CNF_FILE}" "${MY_CNF_FILE}.$(date +%Y%m%d-%H%M%S)"
    curl -fsSL "${MY_CNF_ADDITION}" | sudo tee -a "${MY_CNF_FILE}"  > /dev/null
    brew services stop mariadb >>${INSTALL_LOG} 2>&1
    sleep 5
}

configure_php_fpm() {
    for php_version in "${PHP_VERSIONS[@]}"; do
        CONF_FILE="/opt/homebrew/etc/php/${php_version}/php-fpm.d/www.conf"
        BACKUP="${CONF_FILE}.$(date +%Y%m%d-%H%M%S)"
        CONF_NEW="${GITHUB_BASE}/PHP_fpm_configs/php${php_version}.conf"

        cp "${CONF_FILE}" "${BACKUP}"
        curl -fsSL "${CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${CONF_FILE}"  > /dev/null
    done
}

install_php_switcher() {
    curl -fsSL "${GITHUB_BASE}/Scripts/sphp" | sudo tee "${SCRIPTS_DEST}/sphp" > /dev/null
    sudo chmod +x "${SCRIPTS_DEST}/sphp"
}

php_install_xdebug() {
    clear
    for php_version in "${PHP_VERSIONS[@]}"; do
        echo "Installing XDebug for PHP ${php_version}"
        sphp "${php_version}" >>${INSTALL_LOG} 2>&1
        if [ "${php_version}" == "7.4" ]; then
            pecl install xdebug-3.1.6 >>${INSTALL_LOG} 2>&1
        else    
            pecl install xdebug >>${INSTALL_LOG} 2>&1
        fi
        echo " "
    done
}

php_ini_configuration() {
    for php_version in "${PHP_VERSIONS[@]}"; do
        INI_FILE="/opt/homebrew/etc/php/${php_version}/php.ini"
        BACKUP="${INI_FILE}.$(date +%Y%m%d-%H%M%S)"
        INI_NEW="${GITHUB_BASE}/PHP_ini_files/php${php_version}.ini"

        cp "${INI_FILE}" "${BACKUP}"
        curl -fsSL "${INI_NEW}" | sudo tee "${INI_FILE}"  > /dev/null
    done
}

nginx_configuration() {
    clear
    echo "Configuring NginX..."
    NGINX_CONF="/opt/homebrew/etc/nginx/nginx.conf"
    NGINX_CONF_NEW="${GITHUB_BASE}/nginx.conf"

    cp "${NGINX_CONF}" "${NGINX_CONF}.$(date +%Y%m%d-%H%M%S)"
    curl -fsSL "${NGINX_CONF_NEW}" | sed "s/your_username/${USERNAME}/g" | sudo tee "${NGINX_CONF}"  > /dev/null
}

configure_dnsmasq() {
    echo 'address=/.dev.test/127.0.0.1' >> /opt/homebrew/etc/dnsmasq.conf
    sudo mkdir -p /etc/resolver
    echo "nameserver 127.0.0.1" | sudo tee /etc/resolver/test > /dev/null
}

create_local_folders() {
    mkdir -p "$HOME/Development/Sites" "$HOME/Development/Backup"
    echo '<h1>My User Web Root</h1>' > "$HOME/Development/Sites/index.php"
}

install_ssl_certificates() {
    clear
    echo "Creating local Certificate Authority. Input your password when requested."
    mkcert -install
    mkdir -p /opt/homebrew/etc/nginx/certs
    cd /opt/homebrew/etc/nginx/certs
    mkcert localhost "*.dev.test" >>${INSTALL_LOG} 2>&1
}

install_local_scripts() {
    clear
    echo "Installing local scripts."
    echo " "

    for script in "${LOCAL_SCRIPTS[@]}"; do
        echo "Installing ${script}"
        curl -fsSL "${GITHUB_BASE}/Scripts/${script}" | sudo tee "${SCRIPTS_DEST}/${script}"  > /dev/null
        sudo chmod +x "${SCRIPTS_DEST}/${script}"
    done
}

install_joomla_scripts() {
    clear
    echo "Installing Joomla scripts."
    echo " "

    for script in "${JOOMLA_SCRIPTS[@]}"; do
        echo "Installing ${script}"
        curl -fsSL "${GITHUB_BASE}/Scripts/${script}" | sudo tee "${SCRIPTS_DEST}/${script}"  > /dev/null
        sudo chmod +x "${SCRIPTS_DEST}/${script}"
    done
}

install_root_tools() {
    cd "${SITESROOT}"
    curl -fsSL "https://www.adminer.org/latest.php" | sudo tee "adminer.php"  > /dev/null
    echo "<?php phpinfo();" > phpinfo.php
}

the_end() {
    clear
    echo "Installation completed!"
    echo " "
    echo "The installation log is available at ${INSTALL_LOG}"
    echo " "
    echo "Run 'startdev' to start your environment."
    echo "Enjoy your development setup!"
}

# Execute the script in order
start
install_formulae
mariadb_config
configure_php_fpm
install_php_switcher
php_install_xdebug
php_ini_configuration
nginx_configuration
configure_dnsmasq
create_local_folders
install_ssl_certificates
install_local_scripts
install_joomla_scripts
install_root_tools
the_end
