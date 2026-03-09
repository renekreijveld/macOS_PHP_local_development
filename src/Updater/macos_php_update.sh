#!/bin/bash

# macos_php_update - Update local scripts and joomla scripts
#
# Written by René Kreijveld - email@renekreijveld.nl
# This script is free software; you may redistribute it and/or modify it.
# This script comes without any warranty. Use it at your own risk, always backup your data and software before running this script.
#
# Version history
# 1.0 Initial version.
# 1.1 Moved all scripts to one Scripts folder
# 1.2 Added jrestore script
# 1.3 Improved error handling, password validation, and temp file cleanup

VERSION=1.3

# Folder where scripts are installed
SCRIPTS_DEST="/usr/local/bin"
CONFIG_DIR="${HOME}/.config/phpdev"
CONFIG_FILE="${CONFIG_DIR}/config"

LOCAL_SCRIPTS=( "adddb" "addsite" "checkupdates" "deldb" "delsite" "jbackup" "jbackupall" "jdbdropall" "jdbdump" 
    "jdbdumpall" "jdbimp" "jfunctions" "jlistjoomlas" "joomlainfo" "jrestore" "latestjoomla" "restartapache" "restartdev" 
    "restartdnsmasq" "restartmailpit" "restartmariadb" "restartnginx" "restartphpfpm" "setrights" "setserver" 
    "setsitephp" "sphp" "startapache" "startdev" "startdnsmasq" "startmailpit" "startmariadb" "startnginx" "startphpfpm" 
    "stopapache" "stopdev" "stopdnsmasq" "stopmailpit" "stopmariadb" "stopnginx" "stopphpfpm" "xdebug" )

# GitHub Repo Base URL
GITHUB_BASE="https://github.com/renekreijveld/macOS_NginX_local_development/raw/refs/heads/main"

# Create a temporary directory for downloads
TMPDIR=$(mktemp -d)

cleanup() {
    rm -rf "${TMPDIR}"
}
trap "cleanup; echo 'Installation interrupted. Exiting...'; exit 1" SIGINT
trap cleanup EXIT

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
    echo -e "Welcome to the Apache, NginX, PHP, MariaDB, Xdebug, Mailpit local macOS development script updater ${VERSION}.\n"
    echo -e "This updater and the software it installs come without any warranty. Use it at your own risk.\nAlways backup your data and software before running the installer and use the software it installs.\n"
    read -s -p "Input your password, this is needed for updating system files: " PASSWORD

    # Validate the password
    if ! echo "${PASSWORD}" | sudo -S -v 2>/dev/null; then
        echo "Error: incorrect password, exiting."
        exit 1
    fi
}

# Load configuration file defaults
load_configfile() {
    if [[ -f "${CONFIG_FILE}" ]]; then
        source "${CONFIG_FILE}"
    else
        echo "Error: configuration file ${CONFIG_FILE} not found, exiting."
        exit 1
    fi   
}

update_local_scripts() {
    echo -e "\n\nUpdate local scripts:"
    for script in "${LOCAL_SCRIPTS[@]}"; do
        echo "- update ${script}."
        if ! curl -fsSL "${GITHUB_BASE}/src/Scripts/${script}" -o "${TMPDIR}/${script}"; then
            echo "  Warning: failed to download ${script}, skipping."
            continue
        fi

        if [ -f "${SCRIPTS_DEST}/${script}" ]; then
            echo "${PASSWORD}" | sudo -S mv -f "${SCRIPTS_DEST}/${script}" "${SCRIPTS_DEST}/${script}.$(date +%Y%m%d-%H%M%S)"
        fi

        echo "${PASSWORD}" | sudo -S mv -f "${TMPDIR}/${script}" "${SCRIPTS_DEST}/${script}" > /dev/null
        echo "${PASSWORD}" | sudo -S chmod +x "${SCRIPTS_DEST}/${script}"
    done
    echo "For each installed script a backup was made. Check the folder ${SCRIPTS_DEST}."
}

update_root_tools() {
    echo -e "\nUpdate landingpage."

    if ! curl -fsSL "${GITHUB_BASE}/src/Localhost/index.php" -o "${TMPDIR}/index.php"; then
        echo "Warning: failed to download landing page, skipping."
        return
    fi

    if [ -f "${ROOTFOLDER}/index.php" ]; then
        BACKUPFILE="${ROOTFOLDER}/index.php.$(date +%Y%m%d-%H%M%S)"
        cp "${ROOTFOLDER}/index.php" "${BACKUPFILE}"
        echo "Existing landingpage index.php backed up to ${BACKUPFILE}."
    fi

    mv -f "${TMPDIR}/index.php" "${ROOTFOLDER}/index.php"
}

the_end() {
    echo -e "\nUpdate completed, enjoy your development setup!"
}

# Execute the script in order
start
load_configfile
update_local_scripts
update_root_tools
the_end
