## Updating Homebrew and the local installed scripts.

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
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/renekreijveld/macOS_PHP_local_development/refs/heads/main/src/Updater/macos_php_update.sh)"
```

This will update all local scripts and the landingpage running at https://localhost.

## Renewing you local SSL certificates

To renew your local SSL sertificates do the following. Open a Terminal and run the following commands:

```
cd $HOMEBREW_PREFIX/etc/certs
mkcert localhost
mkcert mkcert "*.dev.test"
```
