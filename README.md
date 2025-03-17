# Apache/NginX/MariaDB/PHP development stack for macOS

This is a command-line-driven local PHP development environment for macOS. It allows you to run PHP-based websites on your macOS machine, providing a powerful setup for local web development.

### Supported macOS Versions

Compatible with Big Sur, Monterey, Ventura, Sonoma, and Sequoia on both Intel and Apple Silicon processors.Supported macOS versions: Big Sur, Monterey, Ventura, Sonoma, Sequoia running on Intel and Silicon prcessors.

### Features

- While the setup is command-line-based, a collection of scripts automates most of the heavy lifting.
- Supports both Apache and NginX as web servers, allowing easy switching between the two.
- Includes MariaDB as the database server.
- Supports multiple PHP versions: 7.4, 8.1, 8.2, 8.3, 8.4 – allowing different local sites to run concurrently with different PHP versions.
- Xdebug is installed and enabled by default for all PHP versions, with an option to toggle it on or off.
- Automatic SSL certificates for all local websites.
- Mailpit for easy email testing.
- A comprehensive set of command-line scripts to simplify website and database management:
  - Easily add and delete local websites and databases.
  - Create backups effortlessly.
  - Start, stop, and restart the development environment with ease.
- All features are well-documented on the local landing page.

### Backup & Safety

If you already have a Homebrew-based PHP development setup, don't worry! The installer script automatically creates backups of all configuration files before making any changes.

### Installation & Updates

Follow these <a href="../../blob/main/install.md">installation instructions</a> to get everything up and running.

Regular updates are provided in this repository. Use the <a href="../../blob/main/update.md">update instructions</a> to keep your setup up to date.

### Is This Tool Right for You?

If you dislike working with the command line, this tool is not for you.<br>
But if you're comfortable in the terminal, it provides a powerful, flexible, and customizable local PHP development environment.

Is this better or easier than tools like MAMP, Laravel Valet, or Laravel Herd?<br>
Probably not. 😄

But it’s open, flexible, and free – and if you're not afraid of the macOS terminal, you can modify it to suit your needs.

### Support the Project

If you like and use this tool, please consider <a href="https://renekreijveld.nl/donate" target="_blank">making a donation</a> to support further development. 🙌
