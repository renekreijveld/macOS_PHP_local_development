## Disclaimer

This setup works fine on my macOS machines. Should something go wrong in your setup, check the sources I used.

## Requirements

- macOS Big Sur / Monterey / Ventura / Sonoma / Sequoia (tested on macOS Sequoia 15.3.1).

This installation assumes you are running the Zsh shell in your terminal. The installer and installed software were tested on macOS Sequoia 15.3.1 with Apple Silicon processor and on macOS Ventura 13.7.4 with Intel processor.

## Update Homebrew

There will be regular updates for the formulae that were installed during installation. To update these formulau you need to do the following.

First check which updates are avaialable. In a terminal enter this command:

```
brew update
```

If Homebrew finds updates, it will report that with a text line like this:

```
You have 4 outdated formulae installed.
```

To update the utdated formulae enter this command:

```
brew upgrade
```

## Updating the installed command line tools and Joomla scripts 

To update the installed command line tools and Joomla scripts enter the following command:

```
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/renekreijveld/macOS_PHP_local_development/refs/heads/main/Updater/macos_php_update.sh)"
```

This will update all local scripts and the landingpage running at https://localhost.