# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository contains a command-line-driven local PHP development environment for macOS, built on Homebrew. It provides Apache/NginX, MariaDB, and multiple PHP versions (7.4, 8.1–8.5) with Xdebug, automatic SSL, and Mailpit for email testing.

## Repository Structure

```
src/
├── Installer/         # macos_php_install.sh — one-shot installer script
├── Updater/           # macos_php_update.sh — updates scripts and landing page
├── Scripts/           # All ~40 management scripts installed to /usr/local/bin
├── Apache/            # httpd.conf, vhosts/, extra/ — Apache configuration files
├── NginX/             # nginx.conf — NginX configuration file
├── Templates/         # apache_vhost_template.conf, nginx_vhost_template.conf, index.php
├── PHP_ini_files/     # php{version}.ini and ext-xdebug.ini for each PHP version
├── PHP_fpm_configs/   # php{version}.conf — PHP-FPM pool config for each version
├── Localhost/         # Landing page index.php served at https://localhost
├── MariaDB/           # MariaDB configuration
└── Casks/             # Optional install guides for iTerm2 and VS Code
```

## Scripts Architecture

All scripts in `src/Scripts/` are installed to `/usr/local/bin/`. Scripts follow a consistent pattern:

- **Configuration**: All scripts source `~/.config/phpdev/config` for shared settings (WEBSERVER, ROOTFOLDER, MARIADBPW, PHP_CLI, etc.)
- **Options**: Scripts use `getopts`; `-s` for silent mode, `-h` for help, `-v` for verbose (on service scripts), `-f` for force (skip confirmation)
- **Output**: Scripts use a `showmessage()` function gated on the `$SILENT` variable

**PHP-FPM port mapping** (used across `addsite`, `setsitephp`):
- PHP 7.4 → port 9074
- PHP 8.1 → port 9081
- PHP 8.2 → port 9082
- PHP 8.3 → port 9083
- PHP 8.4 → port 9084
- PHP 8.5 → port 9085

## Key Scripts Reference

| Script | Purpose |
|--------|---------|
| `startdev` / `stopdev` / `restartdev` | Start/stop/restart all services (dnsmasq, php-fpm, apache or nginx, mariadb, mailpit) |
| `addsite -n <name> -p <php_version> [-d <db>] [-j] [-o]` | Add a new local website with optional DB and Joomla install |
| `delsite -n <name> [-d] [-f]` | Delete a site (with `-d` to also drop DB) |
| `adddb -d <name>` | Create a MariaDB database |
| `deldb -d <name> [-f]` | Drop a MariaDB database |
| `sphp <version>` | Switch the CLI PHP version (e.g. `sphp 8.3`) |
| `setsitephp -n <name> -p <version>` | Change a site's PHP-FPM version and restart webserver |
| `setserver -a \| -n` | Switch active webserver between Apache (`-a`) and NginX (`-n`) |
| `xdebug on \| off \| status` | Enable/disable Xdebug across all PHP versions |
| `checkupdates [-u] [-i]` | Check (`-u`) or install (`-i`) updates for local scripts |
| `jfunctions` | Sourced library — reads Joomla `configuration.php` to extract DB info, version |
| `jbackup` / `jbackupall` | Backup a Joomla site / all Joomla sites |
| `jrestore` | Restore a Joomla backup |
| `latestjoomla` | Download the latest Joomla release |
| `setrights` | Fix file permissions on a website folder |

## Installation & Updates

Install the full environment (run once on a fresh macOS):
```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/renekreijveld/macOS_PHP_local_development/refs/heads/main/src/Installer/macos_php_install.sh)"
```

Update scripts and landing page:
```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/renekreijveld/macOS_PHP_local_development/refs/heads/main/src/Updater/macos_php_update.sh)"
```

Or use `checkupdates -i` after installing the environment.

Update Homebrew formulae:
```bash
brew update && brew upgrade
```

Renew local SSL certificates:
```bash
cd $HOMEBREW_PREFIX/etc/certs
mkcert localhost
mkcert "*.dev.test"
```

## Script Conventions

- Scripts use `THISVERSION=x.x` at the top — this is how `checkupdates` detects outdated versions
- Version history is maintained in comments within each script
- Each script that modifies system state requires the config file at `~/.config/phpdev/config`
- Service scripts (start/stop/restart for individual services) accept `-v` for verbose output
- Website local URLs follow the pattern `https://<sitename>.dev.test`
- MariaDB root credentials come from the config file (`MARIADBPW` variable)
- The active webserver (apache/nginx) is tracked in `~/.config/phpdev/config` as `WEBSERVER=`
