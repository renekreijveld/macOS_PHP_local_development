#!/bin/bash

# macos_php_install - Install a local Apache, NginX, PHP, MariaDB, Xdebug, Mailpit development environment on macOS
#
# This is a universal installer for both Intel and Silicon processors.
# macOS versions supported: Big Sur, Monterey, Ventura, Sonoma, Sequoia
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
# 1.6 Added new scripts for apache
# 1.7 Added password processing
# 1.8 Added modification of sudoers for easy starting and stopping of services
# 1.9 Made installer universal for Intel and Silicon processors
# 1.10 More consistent variable naming
# 1.11 Added checks and handling of existing Homebrew based installation
# 1.12 Added check if installed scripts are already in the PATH variable
# 1.13 Moved all scripts to one Scripts folder

THISVERSION=1.13

# Folder where scripts are installed
HOMEBREW_PATH=$(brew --prefix)
SCRIPTS_DEST="/usr/local/bin"
INSTALL_LOG="${HOME}/nginx_dev_install.log"
CONFIG_DIR="${HOME}/.config/phpdev"
CONFIG_FILE="${CONFIG_DIR}/config"

# PHP Versions to Install
PHP_VERSIONS=("7.4" "8.1" "8.2" "8.3" "8.4")

# Homebrew Formulae to Install
FORMULAE=("wget" "mariadb" "httpd" "nginx" "dnsmasq" "mkcert" "nss" "mailpit")

# Local scripts to install
LOCAL_SCRIPTS=( "adddb" "addsite" "checkupdates" "deldb" "delsite" "jbackup" "jbackupall" "jdbdropall" "jdbdump" 
    "jdbdumpall" "jdbimp" "jfunctions" "jlistjoomlas" "joomlainfo" "latestjoomla" "restartapache" "restartdev" 
    "restartdnsmasq" "restartmailpit" "restartmariadb" "restartnginx" "restartphpfpm" "setrights" "setserver" 
    "setsitephp" "startapache" "startdev" "startdnsmasq" "startmailpit" "startmariadb" "startnginx" "startphpfpm" 
    "stopapache" "stopdev" "stopdnsmasq" "stopmailpit" "stopmariadb" "stopnginx" "stopphpfpm" "xdebug" )

# GitHub Repo Base URL
GITHUB_BASE="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main"

# Logged-in User
USERNAME=$(whoami)

EXISTING_PATHS=()

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
    echo -e "Welcome to the Apache, NginX, PHP, MariaDB, Xdebug, Mailpit local macOS development installer script ${THISVERSION}.\n"
    echo -e "\t#########################################################"
    echo -e "\t## PLEASE READ EVERYTHING CAREFULLY BEFORE CONTINUING! ##"
    echo -e "\t#########################################################\n"
    echo "Installation output will be logged in the file ${INSTALL_LOG}."
    echo -e "Check this file if you encounter any issues during installation.\n"
    echo -e "\tWARNING! If you already have a PHP development environment other than"
    echo -e "\tHomebrew installed (like MAMP/Apachefriends/Laravel Valet/Laravel Herd/other)"
    echo -e "\tthat runs a webserver on port 80 and/or a database server at port 3306:\n"
    echo -e "\t##############################"
    echo -e "\t## THEN DO NOT INSTALL THIS ##"
    echo -e "\t##############################\n"
    echo -e "If you already have a Homebrew based installation, this script will try to update its configuration to work with the new setup."
    echo -e "You mast stop all services (Apache, NginX, PHP's, MariaDB, Mysql, Dnsmasq, Mailhog, Mailpit) before running this script."
    echo -e "If you need to stop current running services, press Ctrl-C now.\n"
    echo -e "This installer and the software it installs come without any warranty. Use it at your own risk.\nAlways backup your data and software before running the installer and use the software it installs.\n"
    read -p "Press Enter to start the installation, press Ctrl-C to abort. "
    touch "${INSTALL_LOG}"
}

prechecks() {
    clear
    # Check ETC DIR
    if [ "${HOMEBREW_PATH}" == "/opt/homebrew" ]; then
        PROCESSOR="silicon"
    fi
    if [ "${HOMEBREW_PATH}" == "/usr/local" ]; then
        PROCESSOR="intel"
    fi

    PRECHECK_FORMULAE=("mariadb" "nginx" "dnsmasq" "mysql" "httpd" "mailhog" "mailpit" "apache2" "php@7.4" "php@8.1"  "php@8.2" "php@8.3" "php@8.4" "php")
    echo "The installer will first check if some required formulae are already installed."
    
    INSTALLED_FORMULAE=()

    for formula in "${PRECHECK_FORMULAE[@]}"; do
        if is_installed "${formula}"; then
            INSTALLED_FORMULAE+=("${formula}")
        fi
    done

    if [ ${#INSTALLED_FORMULAE[@]} -gt 0 ]; then
        echo -e "\nThe following formulae are already installed with Homebrew:\n"
        for formula in "${INSTALLED_FORMULAE[@]}"; do
            echo "  - ${formula}"
        done
        echo -e "\nThis installer does not update these formulae."
        echo "It is best to run a 'brew update' followed by 'brew upgrade' first to update all formulae to their latest version."
        echo -e "\nThis will script will do its best to make backups of all current configuration files."
        echo -e "If a local configuration file was found at ${HOME}/.config/phpdev it will backup that too.\n"
        read -p "Press Enter to start the installation, or press Ctrl-C to abort and update brew first. "
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
    clear
    # Check if config file already exists and if so, backup it
    if [[ -f "${CONFIG_FILE}" ]]; then
        # Backup existing config file
        BACKUPFILE="${CONFIG_FILE}.$(date +%Y%m%d-%H%M%S)"
        cp "${CONFIG_FILE}" "${BACKUPFILE}"
        echo "Existing config file backupped to ${BACKUPFILE}."        
    fi
    # Create config directory if it doesn't exist
    mkdir -p "${CONFIG_DIR}"
    echo -e "\nBefore the installation starts, some default values need to be set."
    echo "These values will be used during installation and will be saved in a config file."
    echo "There are various scripts the depend on this config file. These scripts will not work without it."
    echo -e "\nThe location of the config file is ${CONFIG_FILE}.\n"
    echo -e "If the default proposed value is correct, just press Enter.\n"
    rootfolder=$(prompt_for_input "$HOME/Development/Sites" "Folder path where your websites will be stored:")
    sitesbackup=$(prompt_for_input "$HOME/Development/Backup/sites" "Folder path for website backups:")
    mariadbbackup=$(prompt_for_input "$HOME/Development/Backup/databases" "Folder path for database backups:")
    mariadbpw=$(prompt_for_input "root" "Root password for MariaDB:")
    read -s -p "Input your password, this is needed for updating system files: " PASSWORD

    # Write the values to the config file
    NOW=$(date +"%Y-%m-%d %H:%M:%S")
    echo "# Configuration file for php development environment" > "${CONFIG_FILE}"
    echo "# Generated at ${NOW}" >> "${CONFIG_FILE}"
    echo "ROOTFOLDER=${rootfolder}" >> "${CONFIG_FILE}"
    echo "SITESBACKUP=${sitesbackup}" >> "${CONFIG_FILE}"
    echo "MARIADBBACKUP=${mariadbbackup}" >> "${CONFIG_FILE}"
    echo "MARIADBPW=${mariadbpw}" >> "${CONFIG_FILE}"
    echo "WEBSERVER=nginx" >> "${CONFIG_FILE}"
    echo "INSTALLER_VERSION=${THISVERSION}" >> "${CONFIG_FILE}"
    check_configfile
}

disable_old_apache() {
    clear
    echo "Disable default macOS Apache installation."
    echo "${PASSWORD}" | sudo -S apachectl -k stop > /dev/null 2>&1
    echo "${PASSWORD}" | sudo -S launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist >/dev/null 2>&1
}

install_formulae() {
    echo -e "\nInstall Homebrew formulae:"

    PHP_REPO="shivammathur/php"
    brew tap "${PHP_REPO}" >>${INSTALL_LOG} 2>&1

    for formula in "${FORMULAE[@]}"; do
        echo "- install ${formula}."
        brew install --quiet ${formula} >>${INSTALL_LOG} 2>&1
    done

    for php_version in "${PHP_VERSIONS[@]}"; do
        echo "- install php ${php_version}."
        brew install --quiet "${PHP_REPO}/php@${php_version}" >>${INSTALL_LOG} 2>&1
    done

    # Set commandline PHP version to 8.3
    brew unlink php >>${INSTALL_LOG} 2>&1
    brew link --overwrite --force php@8.3 >>${INSTALL_LOG} 2>&1
}

configure_mariadb() {
    echo -e "\nConfigure MariaDB:"
    brew services start mariadb >>${INSTALL_LOG} 2>&1
    sleep 5

    echo "- Set MariaDB root password."
    mariadb -e "SET PASSWORD FOR root@localhost = PASSWORD('${MARIADBPW}');"

    echo "- Secure MariaDB installation."
    echo -e "${MARIADBPW}\nn\nn\nY\nY\nY\nY" | mariadb-secure-installation >>${INSTALL_LOG} 2>&1

    MY_CNF_FILE="${HOMEBREW_PATH}/etc/my.cnf"
    MY_CNF_ADDITION="${GITHUB_BASE}/src/MariaDB/my.cnf.addition"

    echo "- Patch my.cnf file."

    if grep -q "bind-address = 127.0.0.1" ${MY_CNF_FILE}; then
        echo "The my.cnf file is already patched."
    else
        BACKUPFILE="${MY_CNF_FILE}.$(date +%Y%m%d-%H%M%S)"
        cp "${MY_CNF_FILE}" "${BACKUPFILE}"
        echo "- existing config file backupped to ${BACKUPFILE}."
        curl -fsSL "${MY_CNF_ADDITION}" | tee -a "${MY_CNF_FILE}" > /dev/null
    fi

    brew services stop mariadb >>${INSTALL_LOG} 2>&1
    sleep 5
}

configure_php_fpm() {
    echo -e "\nInstall PHP FPM ini files:"
    for php_version in "${PHP_VERSIONS[@]}"; do
        CONF_FILE="${HOMEBREW_PATH}/etc/php/${php_version}/php-fpm.d/www.conf"
        BACKUPFILE="${CONF_FILE}.$(date +%Y%m%d-%H%M%S)"
        CONF_NEW="${GITHUB_BASE}/src/PHP_fpm_configs/php${php_version}.conf"

        echo -e "- install for PHP ${php_version}."
        cp "${CONF_FILE}" "${BACKUPFILE}"
        echo "- existing www.conf file backupped to ${BACKUPFILE}."
        curl -fsSL "${CONF_NEW}" | sed "s|<your_username>|${USERNAME}|g" | tee "${CONF_FILE}" > /dev/null
    done
}

test_script_path() {
    local script_path="$1"
    local script_name=$(basename "${script_path}")
    local current_path=$(which "${script_name}")
    if [ "${current_path}" != "${script_path}" ]; then
        EXISTING_PATHS+=("${current_path}")
    fi
}

install_php_switcher() {
    echo -e "\nInstall PHP switcher script."

    if [ -f "${SCRIPTS_DEST}/sphp" ]; then
        BACKUPFILE="${SCRIPTS_DEST}/sphp.$(date +%Y%m%d-%H%M%S)"
        echo "${PASSWORD}" | sudo -S cp "${SCRIPTS_DEST}/sphp" "${BACKUPFILE}" > /dev/null
        echo "Existing sphp script backupped to ${BACKUPFILE}."
    fi 

    curl -fsSL "${GITHUB_BASE}/src/Scripts/sphp" | tee "sphp" > /dev/null
    echo "${PASSWORD}" | sudo -S mv -f sphp "${SCRIPTS_DEST}/sphp" > /dev/null
    echo "${PASSWORD}" | sudo -S chmod +x "${SCRIPTS_DEST}/sphp" > /dev/null
    test_script_path "${SCRIPTS_DEST}/sphp"
}

install_xdebug() {
    echo -e "\n\nInstall Xdebug:"
    for php_version in "${PHP_VERSIONS[@]}"; do
        echo "- install Xdebug for PHP ${php_version}."
        sphp "${php_version}" >>${INSTALL_LOG} 2>&1

        if [ "${php_version}" == "7.4" ]; then
            pecl install xdebug-3.1.6 >>${INSTALL_LOG} 2>&1
        else    
            pecl install xdebug >>${INSTALL_LOG} 2>&1
        fi
    done
}

configure_php_ini() {
    echo -e "\nInstall php.ini files:"
    for php_version in "${PHP_VERSIONS[@]}"; do
        INI_FILE="${HOMEBREW_PATH}/etc/php/${php_version}/php.ini"
        XDEBUG_INI="${HOMEBREW_PATH}/etc/php/${php_version}/conf.d/ext-xdebug.ini"
        BACKUPFILE="${INI_FILE}.$(date +%Y%m%d-%H%M%S)"
        BACKUPFILEXDB="${XDEBUG_INI}.$(date +%Y%m%d-%H%M%S)"
        INI_NEW="${GITHUB_BASE}/src/PHP_ini_files/php${php_version}.ini"
        XDEBUG_NEW="${GITHUB_BASE}/src/PHP_ini_files/ext-xdebug${php_version}.ini"

        echo "- install php.ini for PHP ${php_version}."
        cp "${INI_FILE}" "${BACKUPFILE}"
        cp "${XDEBUG_INI}" "${BACKUPFILEXDB}"
        echo "- existing php.ini file backupped to ${BACKUPFILE}."
        echo "- existing ext-xdebug.ini file backupped to ${BACKUPFILEXDB}."
        curl -fsSL "${INI_NEW}" | tee "${INI_FILE}" > /dev/null
        curl -fsSL "${XDEBUG_NEW}" | sed "s|<lib_path>|${HOMEBREW_PATH}|g" | tee "${XDEBUG_INI}" > /dev/null
    done
}

configure_nginx() {
    echo -e "\nConfigure NginX."
    NGINX_CONF="${HOMEBREW_PATH}/etc/nginx/nginx.conf"
    NGINX_CONF_NEW="${GITHUB_BASE}/src/NginX/nginx.conf"
    NGINX_TEMPLATES="${HOMEBREW_PATH}/etc/nginx/templates"
    NGINX_SERVERS="${HOMEBREW_PATH}/etc/nginx/servers"

    BACKUPFILE="${NGINX_CONF}.$(date +%Y%m%d-%H%M%S)"
    cp "${NGINX_CONF}" "${BACKUPFILE}"
    echo "Existing NginX config file backupped to ${BACKUPFILE}."
    curl -fsSL "${NGINX_CONF_NEW}" | sed "s|<your_username>|${USERNAME}|g" | sed "s|<start_path>|${HOMEBREW_PATH}|g" | tee "${NGINX_CONF}" > /dev/null

    mkdir -p "${NGINX_TEMPLATES}" "${NGINX_SERVERS}"
    curl -fsSL "${GITHUB_BASE}/src/Templates/index.php" | tee "${NGINX_TEMPLATES}/index.php" > /dev/null
    curl -fsSL "${GITHUB_BASE}/src/Templates/nginx_vhost_template.conf" | tee "${NGINX_TEMPLATES}/template.conf" > /dev/null
}

configure_apache() {
    echo -e "\nConfigure Apache."
    APACHE_ETC="${HOMEBREW_PATH}/etc/httpd"
    APACHE_CONF="${HOMEBREW_PATH}/etc/httpd/httpd.conf"
    APACHE_CONF_NEW="${GITHUB_BASE}/src/Apache/httpd.conf"
    APACHE_TEMPLATES="${APACHE_ETC}/templates"
    APACHE_VHOSTS="${APACHE_ETC}/vhosts"
    APACHE_VHOSTS_CONF="${APACHE_ETC}/extra/httpd-vhosts.conf"
    APACHE_VHOSTS_CONF_NEW="${GITHUB_BASE}/src/Apache/extra/httpd-vhosts.conf"
    APACHE_SSL_CONF="${APACHE_ETC}/extra/httpd-ssl.conf"
    APACHE_SSL_CONF_NEW="${GITHUB_BASE}/src/Apache/extra/httpd-ssl.conf"

    BACKUPFILE="${APACHE_CONF}.$(date +%Y%m%d-%H%M%S)"
    cp "${APACHE_CONF}" "${BACKUPFILE}"
    echo "Existing Apache config file backupped to ${BACKUPFILE}."
    curl -fsSL "${APACHE_CONF_NEW}" | sed "s|<homebrew_path>|${HOMEBREW_PATH}|g" | sed "s|<your_username>|${USERNAME}|g" | sed "s|<root_folder>|${ROOTFOLDER}|g" | tee "${APACHE_CONF}" > /dev/null

    BACKUPFILE="${APACHE_VHOSTS_CONF}.$(date +%Y%m%d-%H%M%S)"
    cp "${APACHE_VHOSTS_CONF}" "${BACKUPFILE}"
    echo "Existing Vhosts config file backupped to ${BACKUPFILE}."
    curl -fsSL "${APACHE_VHOSTS_CONF_NEW}" | sed "s|<root_folder>|${ROOTFOLDER}|g" | sed "s|<start_dir>|${HOMEBREW_PATH}|g" | tee "${APACHE_VHOSTS_CONF}" > /dev/null

    BACKUPFILE="${APACHE_SSL_CONF}.$(date +%Y%m%d-%H%M%S)"
    cp "${APACHE_SSL_CONF}" "${BACKUPFILE}"
    echo "Existing SSL config file backupped to ${BACKUPFILE}."
    curl -fsSL "${APACHE_SSL_CONF_NEW}" | sed "s|<start_dir>|${HOMEBREW_PATH}|g" | tee "${APACHE_SSL_CONF}" > /dev/null

    mkdir -p "${APACHE_TEMPLATES}" "${APACHE_VHOSTS}"
    curl -fsSL "${GITHUB_BASE}/src/Templates/index.php" | tee "${APACHE_TEMPLATES}/index.php" > /dev/null
    curl -fsSL "${GITHUB_BASE}/src/Templates/apache_vhost_template.conf" | tee "${APACHE_TEMPLATES}/template.conf" > /dev/null
    curl -fsSL "${GITHUB_BASE}/src/Apache/vhosts/localhost.conf" | sed "s|<root_folder>|${ROOTFOLDER}|g" | sed "s|<start_dir>|${HOMEBREW_PATH}|g" | tee "${APACHE_VHOSTS}/localhost.conf" > /dev/null
}

configure_dnsmasq() {
    echo -e "\nConfigure Dnsmasq."
    if [ -f "${HOMEBREW_PATH}/etc/dnsmasq.conf" ]; then
        echo "- Dnsmasq already configured."
    else
        echo 'address=/.dev.test/127.0.0.1' >> ${HOMEBREW_PATH}/etc/dnsmasq.conf
    fi

    if [ -f "/etc/resolver/test" ]; then
        echo "- Resolver already configured."
    else
        echo "${PASSWORD}" | sudo -S mkdir -p /etc/resolver > /dev/null
        echo "nameserver 127.0.0.1" | tee resolver.test > /dev/null
        echo "${PASSWORD}" | sudo -S mv -f resolver.test /etc/resolver/test > /dev/null
    fi
}

create_local_folders() {
    mkdir -p "${ROOTFOLDER}"
    mkdir -p "${SITESBACKUP}"
    mkdir -p "${MARIADBBACKUP}"
}

install_ssl_certificates() {
    echo -e "\nInstall local SSL certificates:"
    if [ "${PROCESSOR}" == "silicon" ]; then
        echo "Two pup-up windows will appear."
        read -p "Input your password in these windows to install the certificates. Press Enter to continue. "
    fi
    echo "- install local Certificate Authority."
    mkcert -install
    mkdir -p ${HOMEBREW_PATH}/etc/certs
    cd ${HOMEBREW_PATH}/etc/certs

    if [ -f "${HOMEBREW_PATH}/etc/certs/localhost.pem" ]; then
        echo "- localhost certificate already installed."
    else
        echo "- create localhost certificate."
        mkcert localhost >>${INSTALL_LOG} 2>&1
    fi

    if [ -f "${HOMEBREW_PATH}/etc/certs/_wildcard.dev.test.pem" ]; then
        echo "- *.dev.test wildcard certificate already installed."
    else
        echo "- create *.dev.test wildcard certificate."
        mkcert "*.dev.test" >>${INSTALL_LOG} 2>&1
    fi
}

install_local_scripts() {
    echo -e "\nInstall local scripts:"
    echo -e "If a script already exists, a backup copy will be made."
    for script in "${LOCAL_SCRIPTS[@]}"; do
        echo "- install ${script}."
        curl -fsSL "${GITHUB_BASE}/src/Scripts/${script}" | tee "script.${script}" > /dev/null

        if [ -f "${SCRIPTS_DEST}/${script}" ]; then
            echo "${PASSWORD}" | sudo -S mv -f "${SCRIPTS_DEST}/${script}" "${SCRIPTS_DEST}/${script}.$(date +%Y%m%d-%H%M%S)"
        fi

        echo "${PASSWORD}" | sudo -S mv -f "script.${script}" "${SCRIPTS_DEST}/${script}" > /dev/null
        echo "${PASSWORD}" | sudo -S chmod +x "${SCRIPTS_DEST}/${script}"
        test_script_path "${SCRIPTS_DEST}/${script}"
    done
}

install_root_tools() {
    cd "${ROOTFOLDER}"
    echo -e "\nInstall landingpage."

    if [ -f "${ROOTFOLDER}/index.php" ]; then
        BACKUPFILE="${ROOTFOLDER}/index.php.$(date +%Y%m%d-%H%M%S)"
        cp "${ROOTFOLDER}/index.php" "${BACKUPFILE}"
        echo "Existing landingpage index.php backupped to ${BACKUPFILE}."
    fi

    curl -fsSL "${GITHUB_BASE}/src/Localhost/index.php" > index.php
    echo "<?php phpinfo();" > phpinfo.php
    echo "Install adminer.php script."
    curl -sL "https://www.adminer.org/latest.php" > adminer.php
    echo "<?php phpinfo();" > phpinfo.php
}

fix_sudoers() {
    echo -e "Modify /etc/sudoers so you don't have to enter your password to start and stop services."

    echo "${PASSWORD}" | sudo -S cp /etc/sudoers sudoers.tmp
    echo "${PASSWORD}" | sudo -S chmod 666 sudoers.tmp

    if grep -q "/bin/brew" sudoers.tmp; then
        echo "The /etc/sudoers is already modified."
    else
        echo "${PASSWORD}" | sudo -S chmod 640 /etc/sudoers
        if [ "${PROCESSOR}" == "silicon" ]; then
            echo "${USERNAME} ALL=(ALL) NOPASSWD: ${HOMEBREW_PATH}/bin/brew" | sudo tee -a /etc/sudoers > /dev/null
        fi
        if [ "${PROCESSOR}" == "intel" ]; then
            echo "${USERNAME} ALL=(ALL) NOPASSWD: ${HOMEBREW_PATH}/Homebrew/bin/brew" | sudo tee -a /etc/sudoers > /dev/null
        fi
        echo "${PASSWORD}" | sudo -S chmod 440 /etc/sudoers
    fi
    rm sudoers.tmp
}

report_existing_paths() {
    if [ ${#EXISTING_PATHS[@]} -gt 0 ]; then
        echo -e "\n################"
        echo -e "## ATTENTION! ##"
        echo -e "################\n"
        echo -e "The following scripts were already installed in a different location than ${SCRIPTS_DEST}:\n"
        for path in "${EXISTING_PATHS[@]}"; do
            echo "- ${path}"
        done
        echo -e "\nAll new scripts are installed in ${SCRIPTS_DEST}."
        echo "The scripts in the list above are still available in the old locations and these come first in the PATH variable."
        echo -e "If you want to use the new development enviroment,\nyou MUST delete or rename the scripts in the old locations first!.\n"
        echo -e "Staring the new environment without cleaning up the old scripts first, will result in errors."
    fi
}

the_end() {
    /usr/local/bin/stopphpfpm > /dev/null 2>&1
    echo -e "\nInstallation completed!\n"
    echo -e "The installation log is available at ${INSTALL_LOG}.\n"
    echo "Run 'startdev' to start your environment. The current webserver is set to NginX."
    echo "You can switch between NginX and Apache with the 'setserver' script."
    if [ ${#EXISTING_PATHS[@]} -gt 0 ]; then
        echo "Do not forget to clean up the old scripts before starting the new development enviroment!\n"
    fi
    echo "Enjoy your development setup!"
    echo -e "\nIf you like this tool, please consider a donation to support further development: https://renekreijveld.nl/donate."
}

# Execute the script in order
start
prechecks
ask_defaults
source "${CONFIG_FILE}"
disable_old_apache
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
install_root_tools
fix_sudoers
report_existing_paths
the_end
