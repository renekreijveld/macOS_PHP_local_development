## Disclaimer

This setup works fine on my macOS machines. Should something go wrong in your setup, check the sources I used.

A logfile is created during installation.

## Requirements

- M1/M2/M3/M4 Mac running macOS Sequoia (tested on macOS Sequoia 15.3.1).
- This installation assumes you are running the Zsh shell in your terminal.

## User resources

This setup is based on the work of myself and others. The most importand resources I used:

- <a href="https://getgrav.org/blog/macos-sequoia-apache-multiple-php-versions">macOS Development setup by Andy Miller</a>.
- <a href="https://github.com/renekreijveld/macOS-Local-Development-Setup/tree/master">macOS Local Development setup by myself</a>.
- <a href="https://kevdees.com/install-nginx-amp-multiple-php-versions-on-macos-15-sequoia/">NginX and Multiple PHP Versions on macOS by Kevin Dees</a>.

## Install XCode

First you need to install XCode command line tools. Start a terminal and enter the following command:

```
xcode-select --install
```

A window appears with the message The "xcode-select" command requires the command line developer tools. Would you like to install the tools now?

Click install, and on the next window click agree. The command line developer tools will then be downloaded and installed.

## Install Homebrew

In the terminal enter the command to install Homebrew:

```
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

Type your password to continue the installation.

After installation enter this command in the terminal to add Homebrew to your PATH:

```
echo >> $HOME/.zprofile; echo 'eval "$(/opt/homebrew/bin/brew shellenv)"' >> $HOME/.zprofile; eval "$(/opt/homebrew/bin/brew shellenv)"
```

### Check Homebrew installation

In the terminal enter the following command:

```
brew --version
```

The Homebrew version is then displayed. The Homebrew version at the time of this writing was 4.4.22. Your version might be newer.

## Optional: install Visual Studio Code through Homebrew

To install Visual Studio Code <a href="https://github.com/renekreijveld/macOS_PHP_local_development/blob/main/Casks/install_vscode.md" target="_blank">follow these installation instructions</a> to get that up and running.

## Optional: Install and configure iTerm2 through Homebrew

To install iTerm2 <a href="https://github.com/renekreijveld/macOS_PHP_local_development/blob/main/Casks/install_iterm2.md" target="_blank">follow these installation instructions</a> to get that up and running.

## Install the development environment

In the terminal enter the following command:

```
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/renekreijveld/macOS_PHP_local_development/refs/heads/main/Installer/nginx_dev_installer.sh)"
```
