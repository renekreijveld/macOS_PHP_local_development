# Apache/NginX/MariaDB/PHP development stack for macOS

This a command-line driven local PHP development environment for macOS. It allows you to run PHP based websites on your macOS machine, for local PHP webdevelopment.

Supported macOS versions: Big Sur, Monterey, Ventura, Sonoma, Sequoia running on Intel and Silicon prcessors.

You will get a setup with the following elements:

- Although this setup is command-line driven, a set of scripts is provided to do the heavy lifting.
- Both Apache and NginX are installed as webservers. You can easyily switch between the two.
- MariaDB database server.
- PHP 7.4/8.1/8.2/8.3/8.4, multiple local sites can run concurrently with different PHP versions.
- Xdebug installed and enabled in all PHP versions.
- Option to disable/enable Xdebug.
- SSL certificates on all local websites installed automatically.
- Mailpit for easy email testing.
- A large number of commandline scripts are installed to make life easy for you. For example scripts to add and delete local websites and databases, scripts to backup websites and databases and scripts to start, stop and restart the development stack.
- Everything is well documented in the Localhost landing page.

If you already have a Homebrew based PHP development setup: do not worry. The installer script creates backups of all configuration files it installs.

Follow these <a href="../../blob/main/install.md">installation instructions</a> to get everything up and running.

Regular updates will be published in this repository. Follow these <a href="../../blob/main/update.md">update instructions</a> to update your local scripts.

If you really **dislike working on the commandline** in a terminal, then this tool is not for you. But if you are comfortable with the commandline, then this tool will give you a powerfull local PHP development environment.

Do you like and use this tool? Please consider <a href="https://renekreijveld.nl/donate" target="_blank">a donation</a> to support further development.