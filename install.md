## Disclaimer

This setup works fine on my macOS machines. I am no NginX, PHP and MariaDB expert so should something go wrong in your setup, check the sources I used.

## Requirements

- macOS Sequoia (tested on macOS Sequoia 15.3.1).
- This installation assumes you are running Zsh in your terminal.

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
echo >> /Users/renekreijveld/.zprofile; echo 'eval "$(/opt/homebrew/bin/brew shellenv)"' >> /Users/renekreijveld/.zprofile; eval "$(/opt/homebrew/bin/brew shellenv)"
```

### Check Homebrew installation

In the terminal enter the following command:

```
brew --version
```

The Homebrew version is then displayed. The Homebrew version at the time of this writing was 4.4.22. Your version might be newer.

## Optional: install Visual Studio Code through Homebrew

Yes, you can also install complete applications with Homebrew :-)

In the terminal enter the following command:

```
brew install cask visual-studio-code
```

After installation consider adding the Visual Studio Code (VSCode) icon to your dock.

- Start VSCode and configure to your desire. When done, click on Command-Shift-P.
- Type 'path' (without the quotes).
- Choose Shell Command: Install 'code' command in PATH.
- Click OK.
- Type your password and click OK.

VSCode is now configured so you can start it from the commandline in the terminal.

## Optional: Install and configure iTerm2 through Homebrew

iTerm2 is a very good alternative for the default terminal on macOS. In the terminal enter the following command:

```
brew install cask iterm2
```

After installation quit the default terminal and consider adding the iTerm2 icon to your dock.

Start iTerm2 and configure it. 

- In the menu bar choose iTerm2 > Settings.
- General > Closing: check Quit when all windows are closed.
- Appearance > General: Theme: Dark.
- Profiles > Text: Check blinking cursor.
- Profiles > Text: Font: Menlo, regular, 16.
- Profiles > Window: Columns: 120, Rows: 40.
- Click on the Other actions button at the left bottom and choose 'Set as default'.

## Install the development environment

In the terminal enter the following command:

```
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/renekreijveld/macOS_NginX_local_development/refs/heads/main/Installer/nginx_dev_installer.sh)"
```

