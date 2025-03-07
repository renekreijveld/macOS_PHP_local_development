<?php
/**
 * Localhost index.php
 * 
 * Landing for the local development environment.
 * Witten by: René Kreijveld
 * 
 * Version 1.0
 */

$phpDir     = '/opt/homebrew/etc/php';
$apacheServersDir = '/opt/homebrew/etc/httpd/vhosts';
$nginxServersDir = '/opt/homebrew/etc/nginx/servers';
$username = getenv('USER');

function getPhpVersionFromConfig ( $configFile )
{
    // Mapping of PHP FPM ports to PHP versions
    $phpVersions = [ 
        '9074' => '7.4',
        '9081' => '8.1',
        '9082' => '8.2',
        '9083' => '8.3',
        '9084' => '8.4',
    ];

    // Read the config file
    $content = file_get_contents( $configFile );
    if ( $content === false )
    {
        return "";
    }

    // Search for the PHP-FPM port pattern
    if ( preg_match( '/127\.0\.0\.1:(90\d{2});/', $content, $matches ) )
    {
        $port = $matches[ 1 ]; // Extract the port number
        return $phpVersions[ $port ] ?? "Unknown PHP version for port $port";
    }

    return "No PHP version found in $configFile";
}

// Get all entries in the PHPdirectory
$entries = scandir( $phpDir );

// Filter only valid PHP version directories (ignoring '.' and '..')
$phpVersions = array_filter( $entries, function ($entry) use ($phpDir)
{
    return is_dir( $phpDir . DIRECTORY_SEPARATOR . $entry ) && preg_match( '/^\d+\.\d+$/', $entry );
} );

// Reset array keys
$phpVersions = array_values( $phpVersions );

// Get all .conf files in the directory
$apacheconfigs = glob( $apacheServersDir . '/*.conf' );
$nginxconfigs = glob( $nginxServersDir . '/*.conf' );

// Extract Apache website names by removing the directory path and ".conf" extension
$apacheWebsites = array_map( function ($file)
{
    return pathinfo( $file, PATHINFO_FILENAME );
}, $apacheconfigs );

// Extract NginX website names by removing the directory path and ".conf" extension
$nginxWebsites = array_map( function ($file)
{
    return pathinfo( $file, PATHINFO_FILENAME );
}, $nginxconfigs );

$status = [ 
    'nginx'   => strpos( shell_exec( 'pgrep nginx' ), "\n" ) !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>',
    'httpd'   => strpos( shell_exec( 'pgrep httpd' ), "\n" ) !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>',
    'mariadb' => strpos( shell_exec( 'pgrep mariadbd' ), "\n" ) !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>',
    'dnsmasq' => strpos( shell_exec( 'pgrep dnsmasq' ), "\n" ) !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>',
    'mailpit' => strpos( shell_exec( 'pgrep mailpit' ), "\n" ) !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>'
];

$phpStatusses = [];
foreach ( $phpVersions as $version )
{
    $search                 = str_replace( '.', '\.', $version );
    $search                 = 'ps -ef | grep php | grep ' . $search;
    $phpStatusses[ $version ] = strpos( shell_exec( $search ), "\n" ) !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>';
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apache NginX MariaDB PHP development environment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import "https://www.nerdfonts.com/assets/css/webfont.css";
        pre {
            margin: 0;
            font-size: 1em;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">Apache | NginX | MariaDB | PHP | Xdebug | Mailpit<br>development environment</h1>
        <p class="text-center">Welcome to your local development environment. Here are your tools.</p>

        <div class="m-4 text-center">
            <a href="https://localhost/adminer.php?username=root" target="_blank" class="btn btn-primary me-2"><i
                    class="nf nf-dev-mariadb"></i> Adminer</a>
            <a href="https://localhost/phpinfo.php" target="_blank" class="btn btn-success me-2"><i
                    class="nf nf-seti-php"></i> PHP Info</a>
            <a href="http://localhost:8025" target="_blank" class="btn btn-danger me-2"><i
                    class="nf nf-oct-mail"></i> Mailpit</a>
            <a href="/" class="btn btn-dark"><i class="nf nf-md-refresh"></i> Refresh</a>
        </div>

        <div class="accordion mb-3" id="toolsAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_system">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse_system" aria-expanded="false" aria-controls="collapse_system">
                        System monitor
                    </button>
                </h2>
                <div id="collapse_system" class="accordion-collapse collapse" aria-labelledby="heading_system"
                    data-bs-parent="#toolsAccordion">
                    <div class="accordion-body">
                        <p>Status of the system services:</p>
                        <p><strong>Apache</strong>: <?php echo $status[ 'httpd' ]; ?></p>
                        <p><strong>NginX</strong>: <?php echo $status[ 'nginx' ]; ?></p>
                        <p><strong>MariaDB</strong>: <?php echo $status[ 'mariadb' ]; ?></p>
                        <?php
                        foreach ( $phpStatusses as $version => $phpstatus )
                        {
                            echo "<p><strong>PHP $version</strong>: $phpstatus</p>";
                        }
                        ?>
                        <p><strong>Dnsmasq</strong>: <?php echo $status[ 'dnsmasq' ]; ?></p>
                        <p><strong>Mailpit</strong>: <?php echo $status[ 'mailpit' ]; ?></p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_apache">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse_apache" aria-expanded="false" aria-controls="collapse_apache">
                        Apache
                    </button>
                </h2>
                <div id="collapse_apache" class="accordion-collapse collapse" aria-labelledby="heading_apache"
                    data-bs-parent="#toolsAccordion">
                    <div class="accordion-body">
                        <p><strong>Apache</strong>: <?php echo $status[ 'httpd' ]; ?></p>
                        <p>The following websites are configured in your Apache setup:</p>
                        <ul>
                            <?php foreach ( $apacheWebsites as $website ) : ?>
                                <?php if ($website !== 'localhost') : ?>
                                <?php $phpVersion = getPhpVersionFromConfig( $apacheServersDir . "/$website.conf" ); ?>
                                <li><a href="https://<?php echo $website; ?>.dev.test" target="_blank"><?php echo $website; ?></a> (php
                                    <?php echo $phpVersion; ?>)</li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_nginx">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse_nginx" aria-expanded="false" aria-controls="collapse_nginx">
                        NginX
                    </button>
                </h2>
                <div id="collapse_nginx" class="accordion-collapse collapse" aria-labelledby="heading_nginx"
                    data-bs-parent="#toolsAccordion">
                    <div class="accordion-body">
                        <p><strong>NginX</strong>: <?php echo $status[ 'nginx' ]; ?></p>
                        <p>The following websites are configured in your NginX setup:</p>
                        <ul>
                            <?php foreach ( $nginxWebsites as $website ) : ?>
                                <?php $phpVersion = getPhpVersionFromConfig( $nginxServersDir . "/$website.conf" ); ?>
                                <li><a href="https://<?php echo $website; ?>.dev.test"
                                        target="_blank"><?php echo $website; ?></a> (php <?php echo $phpVersion; ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_cmdline">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse_cmdline" aria-expanded="false" aria-controls="collapse_cmdline">
                        Command line tools
                    </button>
                </h2>
                <div id="collapse_cmdline" class="accordion-collapse collapse" aria-labelledby="heading_cmdline"
                    data-bs-parent="#toolsAccordion">
                    <div class="accordion-body">
                        <table class="table">
                            <tr>
                                <td>
                                    <pre><code><strong>addsite</strong></code></pre>
                                </td>
                                <td><strong>Add a new local website to the NginX configuration.</strong><br><br>
                                    <pre><code>Usage: addsite -n &lt;name&gt; -p &lt;php version&gt; [-d &lt;database name&gt;] [-j] [-o] [-s] [-h]
-n the name for the new website (input without spaces, mandatory).
-p the PHP version for the new website (mandatory).
-d the database name for the new website (optional).
-j download and install the latest Joomla version (optional).
-o open the new website in the browser after creation (optional).
-s silent, no messages will be shown (optional).
-h display this help.</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>delsite</strong></code></pre>
                                </td>
                                <td><strong>Delete a local website from the NginX configuration.</strong><br><br>
                                    <pre><code>Usage: delsite -n &lt;name&gt; [-d] [-b] [-s] [-h]
-n the name for the website (input without spaces, mandatory).
-d also drop the database (optional).
-b backup website and database before deleting (optional).
-s silent, no messages will be shown (optional).
-h display this help.</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>adddb</strong></code></pre>
                                </td>
                                <td><strong>Add a new database.</strong><br><br>
                                    <pre><code>Usage: adddb -d &lt;database name&gt; [-s] [-h]
-d the database name for the new website (input without spaces, mandatory).
-s silent, no messages will be shown (optional).
-h display this help.</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>deldb</strong></code></pre>
                                </td>
                                <td><strong>Delete a database.</strong><br><br>
                                    <pre><code>Usage: deldb -d &lt;database name&gt; [-s] [-h]
-d the name of the database to drop (mandatory).
-s silent, no messages will be shown (optional).
-h display this help.</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>setsitephp</strong></code></pre>
                                </td>
                                <td><strong>Set a local website to a php version.</strong><br><br>
                                    <pre><code>Usage: setsitephp -n &lt;website_name&gt; -p &lt;php_version&gt; [-s] [-h]
 -n the name for the new website (mandatory).
 -p the PHP version for the website (mandatory).
 -s silent, no messages will be shown (optional).
 -h display this help.</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>setrights</strong></code></pre>
                                </td>
                                <td><strong>Set filerights of the current folder and all subfolders.</strong><br>
                                    Folder rights will be set to 755 (rwx-r-xr-x), file rights will be site to 644
                                    (rw-r--r--).<br><br>
                                    <pre><code>Usage: setrights</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>sphp</strong></code></pre>
                                </td>
                                <td><strong>Switch command line PHP version</strong><br><br>
                                    <pre><code>Usage: sphp &lt;php version&gt;</code></pre><br>
                                    Possible PHP versions: <?php echo implode( ', ', $phpVersions ); ?>.
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>xdebug</strong></code></pre>
                                </td>
                                <td><strong>Turn xdebug on or off in all installed PHP versions.</strong><br><br>
                                    <pre><code>Usage: xdebug &lt;on|off&gt;</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startdev</strong></code></pre>
                                </td>
                                <td><strong>Start all services: NgniX, MariaDB, PHP FPM, DNSmasq,
                                        Mailpit.</strong><br><br>
                                    <pre><code>Usage: startdev</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>stopdev</strong></code></pre>
                                </td>
                                <td><strong>Stop all services: NgniX, MariaDB, PHP FPM, DNSmasq,
                                        Mailpit.</strong><br><br>
                                    <pre><code>Usage: stopdev</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>restartdev</strong></code></pre>
                                </td>
                                <td><strong>Restart all services: NgniX, MariaDB, PHP FPM, DNSmasq,
                                        Mailpit.</strong><br><br>
                                    <pre><code>Usage: restartdev</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startnginx</strong></code></pre>
                                </td>
                                <td><strong>Start the Nginx webserver.</strong><br><br>
                                    <pre><code>Usage: startnginx</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>stopnginx</strong></code></pre>
                                </td>
                                <td><strong>Stop the Nginx webserver.</strong><br><br>
                                    <pre><code>Usage: stopnginx</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>restartnginx</strong></code></pre>
                                </td>
                                <td><strong>Restart the Nginx webserver.</strong><br><br>
                                    <pre><code>Usage: restartnginx</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startmariadb</strong></code></pre>
                                </td>
                                <td><strong>Start MariaDB.</strong><br><br>
                                    <pre><code>Usage: startmariadb</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>stopmariadb</strong></code></pre>
                                </td>
                                <td><strong>Stop MariaDB.</strong><br><br>
                                    <pre><code>Usage: stopmariadb</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>restartmariadb</strong></code></pre>
                                </td>
                                <td><strong>Restart MariaDB.</strong><br><br>
                                    <pre><code>Usage: restartmariadb</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startphpfpm</strong></code></pre>
                                </td>
                                <td><strong>Start PHP FPM.</strong><br><br>
                                    <pre><code>Usage: startphpfpm</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>stopphpfpm</strong></code></pre>
                                </td>
                                <td><strong>Stop PHP FPM.</strong><br><br>
                                    <pre><code>Usage: stopphpfpm</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>restartphpfpm</strong></code></pre>
                                </td>
                                <td><strong>Restart PHP FPM.</strong><br><br>
                                    <pre><code>Usage: restartphpfpm</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startdnsmasq</strong></code></pre>
                                </td>
                                <td><strong>Start Dnsmasq.</strong><br><br>
                                    <pre><code>Usage: startdnsmasq</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>stopdnsmasq</strong></code></pre>
                                </td>
                                <td><strong>Stop Dnsmasq.</strong><br><br>
                                    <pre><code>Usage: stopdnsmasq</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>restartdnsmasq</strong></code></pre>
                                </td>
                                <td><strong>Restart Dnsmasq.</strong><br><br>
                                    <pre><code>Usage: restartdnsmasq</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startmailpit</strong></code></pre>
                                </td>
                                <td><strong>Start Mailpit.</strong><br><br>
                                    <pre><code>Usage: startmailpit</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>stopmailpit</strong></code></pre>
                                </td>
                                <td><strong>Stop Mailpit.</strong><br><br>
                                    <pre><code>Usage: stopmailpit</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>restartmailpit</strong></code></pre>
                                </td>
                                <td><strong>Restart Mailpit.</strong><br><br>
                                    <pre><code>Usage: restartmailpit</code></pre>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_jscripts">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse_jscripts" aria-expanded="false" aria-controls="collapse_jscripts">
                        Joomla scripts
                    </button>
                </h2>
                <div id="collapse_jscripts" class="accordion-collapse collapse" aria-labelledby="heading_jscripts"
                    data-bs-parent="#toolsAccordion">
                    <div class="accordion-body">
                        <table class="table">
                            <tr>
                                <td>
                                    <pre><code><strong>jlistjoomlas</strong></code></pre>
                                </td>
                                <td><strong>List all Joomla websites in your environment.</strong><br><br>
                                    <pre><code>Usage: jlistjoomlas [-s] [-c] [-h] [-r release]
-s Short. Only display path and Joomla version.
-r Release version. Only display information about Joomla sites with given release version.
-c CSV. Output values in CSV format.
-h Help. Display this info.</code></pre>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>joomlainfo</strong></code></pre>
                                </td>
                                <td><strong>Display information about a Joomla website.</strong><br><br>
                                    <pre><code>Usage: joomlainfo</code></pre><br>
                                    Run this command in the root of a Joomla website.
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jbackup</strong></code></pre>
                                </td>
                                <td><strong>Backup a Joomla website.</strong><br><br>
                                    <pre><code>Usage: jbackup [-z] [-t] [-o] [-m] [-s] [-h]
Default action is .tgz backup.
-z Zip. Backup to a zipfile instead of a tgzfile.
-t Add a date/time-stamp to the backup file.
-o Overwrite existing backupfile and/or database dump.
-m Move backup to ~/Development/Backup/sites folder.
-s silent, no messages will be shown (optional).
-h Help. Display this info.</code></pre><br>
                                    Run this command in the root of a Joomla website.
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jbackupall</strong></code></pre>
                                </td>
                                <td><strong>Create a backup of all local Joomla websites.</strong><br><br>
                                    <pre><code>Usage: jbackupall [-s] [-h]
-s Silent. Do not display any messages to standard output.
-h Help. Display this info.</code></pre><br>
                                    This scrips searches for all Joomla websites in the ~/Development/Sites folder and
                                    creates a backup for each website found.<br>
                                    The backups are stored in the ~/Development/Backup/sites folder.
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jdbdump</strong></code></pre>
                                </td>
                                <td><strong>Create a database dump of a Joomla website.</strong><br><br>
                                    <pre><code>Usage: jdbdump [-d] [-c] [-o] [-m] [-h]
-d Add a date-time-stamp to the database dump filename.
-c Compress the database dump with gzip.
-o Overwrite existing database dump.
-m Move database dump to ~/Development/Backup/mysql folder.
-h Help. Display this info.</code></pre><br>
                                    Run this command in the root of a Joomla website.
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jdbdumpall</strong></code></pre>
                                </td>
                                <td><strong>Create a database dump of all Joomla website databases.</strong><br><br>
                                    <pre><code>Usage: jdbdumpall [-s] [-h]
-s Silent. Do not display any messages to standard output.
-h Help. Display this info.</code></pre><br>
                                    This scrips searches for all Joomla websites in the ~/Development/Sites folder and
                                    creates a database dump for each website found.<br>
                                    The database dumps are stored in the ~/Development/Backup/mysql folder.
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jdbimp</strong></code></pre>
                                </td>
                                <td><strong>Import a database dump into the database of a Joomla
                                        website.</strong><br><br>
                                    <pre><code>Usage: jdbimp [-s] [-h]
-s silent, no messages will be shown (optional).
-h Help. Display this info.</code></pre><br>
                                    Run this command in the root of a Joomla website.
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jdbdropall</strong></code></pre>
                                </td>
                                <td><strong>Drop all database tables of a Joomla website.</strong><br><br>
                                    <pre><code>Usage: jdbdropall
</code></pre><br>
                                    Run this command in the root of a Joomla website. The script asks your confirmation
                                    before dropping all tables.
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>latestjoomla</strong></code></pre>
                                </td>
                                <td><strong>Download a zipfile of the latest Joomla! version and unzip it in the current
                                        folder.</strong><br><br>
                                    <pre><code>Usage: latestjoomla</code></pre>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_faq">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse_faq" aria-expanded="false" aria-controls="collapse_faq">
                        Frequently Asked Questions
                    </button>
                </h2>
                <div id="collapse_faq" class="accordion-collapse collapse" aria-labelledby="heading_faq"
                    data-bs-parent="#toolsAccordion">
                    <div class="accordion-body faqlist">
                        <div class="accordion" id="faqNginX">
                            <p class="mt-3"><strong>NginX</strong></p>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_nx_1" aria-expanded="true" aria-controls="faq_nx_1">
                                        Where is the NginX configuration file?
                                    </button>
                                </p>
                                <div id="faq_nx_1" class="accordion-collapse collapse" data-bs-parent="#faqNginX">
                                    <div class="accordion-body">
                                        The NginX configuration file is <span class="badge bg-secondary fw-light font-monospace">/opt/homebrew/etc/nginx/nginx.conf</span>.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_nx_2" aria-expanded="true" aria-controls="faq_nx_2">
                                        Where are the NginX local website server configurations?
                                    </button>
                                </p>
                                <div id="faq_nx_2" class="accordion-collapse collapse" data-bs-parent="#faqNginX">
                                    <div class="accordion-body">
                                        The NginX local website server configuration files are in <span class="badge bg-secondary fw-light font-monospace">/opt/homebrew/etc/nginx/servers</span>.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_nx_3" aria-expanded="true" aria-controls="faq_nx_3">
                                        Where are the NginX templates?
                                    </button>
                                </p>
                                <div id="faq_nx_3" class="accordion-collapse collapse" data-bs-parent="#faqNginX">
                                    <div class="accordion-body">
                                        The NginX templates are at <span class="badge bg-secondary fw-light font-monospace">/opt/homebrew/etc/nginx/templates</span>.<br>
                                        The file <span class="badge bg-secondary fw-light font-monospace">template.conf</span> is used to create a new local website server configuration.<br>
                                        The file <span class="badge bg-secondary fw-light font-monospace">index.php</span> is placed in the root of a new local website that you create with the <span class="badge bg-secondary fw-light font-monospace">addsite script.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_nx_4" aria-expanded="true" aria-controls="faq_nx_4">
                                        Where are the local websites located?
                                    </button>
                                </p>
                                <div id="faq_nx_4" class="accordion-collapse collapse" data-bs-parent="#faqNginX">
                                    <div class="accordion-body">
                                        Every website that you create with the a<span class="badge bg-secondary fw-light font-monospace">addsite</span> script is stored in the folder <span class="badge bg-secondary fw-light font-monospace">/Users/<?php echo $username; ?>/Development/Sites/&lt;sitename&gt.</span>.<br>
                                        So the command <span class="badge bg-secondary fw-light font-monospace">addsite -n joomla5</span> will create the folder <span class="badge bg-secondary fw-light font-monospace">/Users/<?php echo $username; ?>/Development/Sites/joomla5</span>. That is the root of the website.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion" id="faqMariaDB">
                            <p class="mt-3"><strong>MariaDB</strong></p>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_mdb_1" aria-expanded="true" aria-controls="faq_mdb_1">
                                        How can I add a new database?
                                    </button>
                                </p>
                                <div id="faq_mdb_1" class="accordion-collapse collapse" data-bs-parent="#faqMariaDB">
                                    <div class="accordion-body">
                                        You can create a new database with the <span class="badge bg-secondary fw-light font-monospace">adddb</span> script. Type <span class="badge bg-secondary fw-light font-monospace">adddb -h</span> in a terminal for syntax and options.<br>
                                        You can also click the blue Adminer button above to open Adminer. Login with password <span class="badge bg-secondary fw-light font-monospace">root</span> to manage databases and the tables inside the databases.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_mdb_2" aria-expanded="true" aria-controls="faq_mdb_2">
                                        How do I update Adminer?
                                    </button>
                                </p>
                                <div id="faq_mdb_2" class="accordion-collapse collapse" data-bs-parent="#faqMariaDB">
                                    <div class="accordion-body">
                                        When Adminer has an update you will see the new version number in red at the top left of the Adminer screen.<br>
                                        To update Adminer, download the latest version from <a href="https://www.adminer.org/latest.php" target="_blank">https://www.adminer.org/latest.php</a>.<br>
                                        Save the file as adminer.php in the folder <span class="badge bg-secondary fw-light font-monospace">/Users/<?php echo $username; ?>/Development/Sites</span>. Overwrite the existing adminer.php file.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion" id="faqPHP">
                            <p class="mt-3"><strong>PHP</strong></p>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_php_1" aria-expanded="true" aria-controls="faq_php_1">
                                        Where are the php.ini files?
                                    </button>
                                </p>
                                <div id="faq_php_1" class="accordion-collapse collapse" data-bs-parent="#faqPHP">
                                    <div class="accordion-body">
                                        The folder <span class="badge bg-secondary fw-light font-monospace">/opt/homebrew/etc/php</span> holds all installed PHP versions. Each PHP version has its own folder with a php.ini file.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_php_2" aria-expanded="true" aria-controls="faq_php_2">
                                        Where are the configuration files for Xdebug?
                                    </button>
                                </p>
                                <div id="faq_php_2" class="accordion-collapse collapse" data-bs-parent="#faqPHP">
                                    <div class="accordion-body">
                                        The folder <span class="badge bg-secondary fw-light font-monospace">/opt/homebrew/etc/php</span> holds all installed PHP versions. Each PHP version has its own folder.<br>
                                        In that folder you will find a <span class="badge bg-secondary fw-light font-monospace">conf.d</span> folder that holds the <span class="badge bg-secondary fw-light font-monospace">ext-xdebug.ini</span> file.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_php_3" aria-expanded="true" aria-controls="faq_php_3">
                                        At which port number is Xdebug running?
                                    </button>
                                </p>
                                <div id="faq_php_3" class="accordion-collapse collapse" data-bs-parent="#faqPHP">
                                    <div class="accordion-body">
                                        For all PHP versions, Xdebug runs at port <strong>9003</strong>.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_php_4" aria-expanded="true" aria-controls="faq_php_4">
                                        I want to temporarily disable Xdebug. How do I do that?
                                    </button>
                                </p>
                                <div id="faq_php_4" class="accordion-collapse collapse" data-bs-parent="#faqPHP">
                                    <div class="accordion-body">
                                        Open a terminal and type the command <span class="badge bg-secondary fw-light font-monospace">xdebug off</span>. That will restart all PHP versions and then Xdebug is disabled.<br>
                                        To re-enable Xdebug type the command <span class="badge bg-secondary fw-light font-monospace">xdebug on</span> in a terminal.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion" id="faqJoomla">
                            <p class="mt-3"><strong>Joomla scripts</strong></p>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_joomla_1" aria-expanded="true" aria-controls="faq_joomla_1">
                                        I want to quickly make a database dump of a Joomla website. How do I do that?
                                    </button>
                                </p>
                                <div id="faq_joomla_1" class="accordion-collapse collapse" data-bs-parent="#faqPHP">
                                    <div class="accordion-body">
                                        Open a terminal and go to the root of the Joomla website you want to make a database dump for.<br>
                                        Type the command <span class="badge bg-secondary fw-light font-monospace">jdbdump</span>.<br>
                                        This will create a database dump <span class="badge bg-secondary fw-light font-monospace">&lt;database_name&gt;.sql</span>. The database name is automatically detected from the configuration.php file.<br>
                                        To see al options for jdbdump, type <span class="badge bg-secondary fw-light font-monospace">jdbdump -h</span>.<br><br>
                                        If you want to import the database dump back into the database, type the command <span class="badge bg-secondary fw-light font-monospace">jdbimp</span>.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_joomla_2" aria-expanded="true" aria-controls="faq_joomla_2">
                                        I want to quickly make a full backup of a Joomla website. How do I do that?
                                    </button>
                                </p>
                                <div id="faq_joomla_2" class="accordion-collapse collapse" data-bs-parent="#faqPHP">
                                    <div class="accordion-body">
                                        Open a terminal and go to the root of the Joomla website you want to backup.<br>
                                        Type the command <span class="badge bg-secondary fw-light font-monospace">jbackup</span>.<br>
                                        This will create a complete backup including a database dump of the website.<br>
                                        To see al options for jbackup, type <span class="badge bg-secondary fw-light font-monospace">jbackup -h</span>.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_about">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse_about" aria-expanded="false" aria-controls="collapse_nginx">
                        About
                    </button>
                </h2>
                <div id="collapse_about" class="accordion-collapse collapse" aria-labelledby="heading_about"
                    data-bs-parent="#toolsAccordion">
                    <div class="accordion-body">
                        <p>The Apache NginX MariaDB PHP Xdebug Mailpit installer and all scripts written by René Kreijveld.</p>
                    </div>
                </div>
            </div>
        </div>
        <p><i class="nf nf-fa-github"></i> <a href="https://github.com/renekreijveld/macOS_NginX_local_development"
                target="_blank">Created by René Kreijveld</a>.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>