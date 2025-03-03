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
$serversDir = '/opt/homebrew/etc/nginx/servers';

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
$nginxconfigs = glob( $serversDir . '/*.conf' );

// Extract website names by removing the directory path and ".conf" extension
$websites = array_map( function ($file)
{
    return pathinfo( $file, PATHINFO_FILENAME );
}, $nginxconfigs );

$status = [ 
    'nginx'   => strpos( shell_exec( 'pgrep nginx' ), "\n" ) !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>',
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
    <title>NginX MariaDB PHP development environment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        pre {
            margin: 0;
            font-size: 1em;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">NginX MariaDB PHP development environment</h1>
        <p class="text-center">Welcome to your local development environment. Here are your tools.</p>

        <div class="m-4 text-center">
            <a href="https://localhost/adminer.php" target="_blank" class="btn btn-primary me-2"><i
                    class="fa-solid fa-database"></i> Adminer</a>
            <a href="https://localhost/phpinfo.php" target="_blank" class="btn btn-success me-2"><i
                    class="fa-brands fa-php"></i> PHP Info</a>
            <a href="http://localhost:8025" target="_blank" class="btn btn-danger me-2"><i
                    class="fa-solid fa-inbox"></i> Mailpit</a>
            <a href="/" class="btn btn-dark"><i class="fa-solid fa-arrows-rotate"></i> Refresh</a>
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
                <h2 class="accordion-header" id="heading_nginx">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse_nginx" aria-expanded="false" aria-controls="collapse_nginx">
                        NginX
                    </button>
                </h2>
                <div id="collapse_nginx" class="accordion-collapse collapse" aria-labelledby="heading_nginx"
                    data-bs-parent="#toolsAccordion">
                    <div class="accordion-body">
                        <p>The following websites are configured in your NginX setup:</p>
                        <ul>
                            <?php foreach ( $websites as $website ) : ?>
                                <?php $phpVersion = getPhpVersionFromConfig( $serversDir . "/$website.conf" ); ?>
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
        </div>
        <p><i class="fa-brands fa-github"></i> <a href="https://github.com/renekreijveld/macOS_NginX_local_development"
                target="_blank">Created by René Kreijveld</a>.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js"
        integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>

</html>