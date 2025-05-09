<?php
/**
 * Localhost index.php
 *
 * Landing for the local development environment.
 * Witten by: René Kreijveld
 *
 * Version history
 *
 * 1.0 Initial version
 * 1.1 Added state of services
 * 1.2 Added display of joomla info in sites list
 * 1.3 Fixes some issues with the display of the table infos
 * 1.4 Improve htaccess check
 * 1.5 Added checkupdates documentation
 * 1.6 Updated jdbdumpall documentation
 * 1.7 Added donatiopn link
 * 1.8 Moved Joomla scripts to Command line tools
 * 1.9 Added version number in header
 * 1.10 Added documentation jrestore
 * 1.11 Fix wording jrestore
 *
 * THISVERSION=1.11
 */

# Determine oath of local etc folder
if (is_dir('/usr/local/Homebrew')) {
    $etcDir = '/usr/local/etc';
}
if (is_dir('/opt/homebrew')) {
    $etcDir = '/opt/homebrew/etc';
}

$thisversion = '1.8';
$phpDir = $etcDir . '/php';
$apacheServersDir = $etcDir . '/httpd/vhosts';
$nginxServersDir = $etcDir . '/nginx/servers';
$username = getenv('USER');

$configFile = getenv('HOME') . '/.config/phpdev/config';

// Read the contents of the configuration file
$configContents = file_get_contents($configFile);

// Parse the configuration file contents
$configLines = explode("\n", $configContents);
$config = [];
foreach ($configLines as $line) {
    // Skip empty lines and comments
    if (empty($line) || strpos(trim($line), '#') === 0) {
        continue;
    }

    // Split the line into key and value
    list($key, $value) = explode('=', $line, 2);
    $config[trim($key)] = trim($value);
}

// Assign the value of ROOTFOLDER to a PHP variable
$rootFolder = $config['ROOTFOLDER'] ?? null;
$mariadbbackupFolder = $config['MARIADBBACKUP'] ?? null;
$sitesbackupFolder = $config['SITESBACKUP'] ?? null;

function getPhpVersionFromNginXConfig($configFile)
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
    $content = file_get_contents($configFile);
    if ($content === false) {
        return "";
    }

    // Search for the PHP-FPM port pattern
    if (preg_match('/127\.0\.0\.1:(90\d{2})/', $content, $matches)) {
        $port = $matches[1]; // Extract the port number
        return $phpVersions[$port] ?? "Unknown PHP version for port $port";
    }

    return "No PHP version found in $configFile";
}

function getJoomlaInfo($sitename): array
{
    $siteInfo = shell_exec('/usr/local/bin/joomlainfo -n ' . $sitename . ' -c');

    // Parse the configuration file contents
    $infoLines = explode("\n", $siteInfo);
    $siteConfig = [];
    foreach ($infoLines as $line) {
        // Split the line into key and value
        list($key, $value) = explode('=', $line, 2);
        $siteConfig[trim($key)] = trim($value);
    }

    if (isset($siteConfig['PATH']) && file_exists($siteConfig['PATH'] . '/.htaccess')) {
        $htaccessContent = trim(file_get_contents($siteConfig['PATH'] . '/.htaccess'));

        // Checks that the file is not empty
        if (!empty($htaccessContent)) {
            // Check important instructions
            if (preg_match('/RewriteEngine\s+On|Options\s+-Indexes|Deny\s+from\s+all|Require\s+all\s+denied/i', $htaccessContent)) {
                $siteConfig['HTACCESS'] = 'Yes';
            } else {
                $siteConfig['HTACCESS'] = 'Minimal';
            }
        } else {
            $siteConfig['HTACCESS'] = 'Empty';
        }
    } else {
        $siteConfig['HTACCESS'] = 'No';
    }

    return $siteConfig;
}

function getJoomlaDatabase($sitename): string
{
    $database = shell_exec('/usr/local/bin/joomlainfo -n ' . $sitename . ' -d');
    return $database;
}

// Get all entries in the PHPdirectory
$entries = scandir($phpDir);

// Filter only valid PHP version directories (ignoring '.' and '..')
$phpVersions = array_filter($entries, function ($entry) use ($phpDir) {
    return is_dir($phpDir . DIRECTORY_SEPARATOR . $entry) && preg_match('/^\d+\.\d+$/', $entry);
});

// Reset array keys
$phpVersions = array_values($phpVersions);

// Get all .conf files in the directory
$apacheconfigs = glob($apacheServersDir . '/*.conf');
$nginxconfigs = glob($nginxServersDir . '/*.conf');

// Extract Apache website names by removing the directory path and ".conf" extension
$apacheWebsites = array_map(function ($file) {
    return pathinfo($file, PATHINFO_FILENAME);
}, $apacheconfigs);

// Extract NginX website names by removing the directory path and ".conf" extension
$nginxWebsites = array_map(function ($file) {
    return pathinfo($file, PATHINFO_FILENAME);
}, $nginxconfigs);

$status = [
    'nginx' => strpos(shell_exec('pgrep nginx'), "\n") !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>',
    'httpd' => strpos(shell_exec('pgrep httpd'), "\n") !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>',
    'mariadb' => strpos(shell_exec('pgrep mariadbd'), "\n") !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>',
    'dnsmasq' => strpos(shell_exec('pgrep dnsmasq'), "\n") !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>',
    'mailpit' => strpos(shell_exec('pgrep mailpit'), "\n") !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>'
];

$phpStatusses = [];
foreach ($phpVersions as $version) {
    $search = str_replace('.', '\.', $version);
    $search = 'ps -ef | grep php | grep ' . $search;
    $phpStatusses[$version] = strpos(shell_exec($search), "\n") !== false ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-danger">Not running</span>';
}

?>
<!DOCTYPE html>
<html lang="en">

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
        <h1 class="text-center mb-4">Apache | NginX | MariaDB | PHP | Xdebug | Mailpit<br>development environment <?php echo $thisversion; ?></h1>

        <div class="m-4 text-center">
            <a href="https://localhost/adminer.php?username=root" target="_blank" class="btn btn-primary me-2"><i
                    class="nf nf-dev-mariadb"></i> Adminer</a>
            <a href="https://localhost/phpinfo.php" target="_blank" class="btn btn-success me-2"><i
                    class="nf nf-seti-php"></i> PHP Info</a>
            <a href="http://localhost:8025" target="_blank" class="btn btn-danger me-2"><i class="nf nf-oct-mail"></i>
                Mailpit</a>
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
                        <p><strong>Apache</strong>: <?php echo $status['httpd']; ?></p>
                        <p><strong>NginX</strong>: <?php echo $status['nginx']; ?></p>
                        <p><strong>MariaDB</strong>: <?php echo $status['mariadb']; ?></p>
                        <?php
                        foreach ($phpStatusses as $version => $phpstatus) {
                            echo "<p><strong>PHP $version</strong>: $phpstatus</p>";
                        }
                        ?>
                        <p><strong>Dnsmasq</strong>: <?php echo $status['dnsmasq']; ?></p>
                        <p><strong>Mailpit</strong>: <?php echo $status['mailpit']; ?></p>
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
                        <p><strong>Apache</strong>: <?php echo $status['httpd']; ?></p>
                        <p>The following websites are configured in your Apache setup:</p>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Website name</th>
                                    <th>PHP version</th>
                                    <th>Joomla version</th>
                                    <th>Database</th>
                                    <th>DB User</th>
                                    <th>DB Password</th>
                                    <th>DB Prefix</th>
                                    <th>.htaccess</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apacheWebsites as $website): ?>
                                    <?php if ($website !== 'localhost'): ?>
                                        <?php $phpVersion = getPhpVersionFromNginXConfig($apacheServersDir . "/$website.conf"); ?>
                                        <?php $joomlaInfo = getJoomlaInfo($website); ?>
                                        <tr>
                                            <td><a href="https://<?php echo $website; ?>.dev.test"
                                                    target="_blank"><?php echo $website; ?></a></td>
                                            <td><?php echo $phpVersion; ?></td>
                                            <td><?php echo $joomlaInfo['VERSION']; ?></td>
                                            <td><?php echo $joomlaInfo['DATABASE']; ?></td>
                                            <td><?php echo $joomlaInfo['DBUSER']; ?></td>
                                            <td><?php echo $joomlaInfo['PASSWORD']; ?></td>
                                            <td><?php echo $joomlaInfo['PREFIX']; ?></td>
                                            <td><?php echo $joomlaInfo['HTACCESS']; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                        <p><strong>NginX</strong>: <?php echo $status['nginx']; ?></p>
                        <p>The following websites are configured in your NginX setup:</p>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Website name</th>
                                    <th>PHP version</th>
                                    <th>Joomla version</th>
                                    <th>Database</th>
                                    <th>DB User</th>
                                    <th>DB Password</th>
                                    <th>DB Prefix</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apacheWebsites as $website): ?>
                                    <?php if ($website !== 'localhost'): ?>
                                        <?php $phpVersion = getPhpVersionFromNginXConfig($apacheServersDir . "/$website.conf"); ?>
                                        <?php $joomlaInfo = getJoomlaInfo($website); ?>
                                        <tr>
                                            <td><a href="https://<?php echo $website; ?>.dev.test"
                                                    target="_blank"><?php echo $website; ?></a></td>
                                            <td><?php echo $phpVersion; ?></td>
                                            <td><?php echo $joomlaInfo['VERSION']; ?></td>
                                            <td><?php echo $joomlaInfo['DATABASE']; ?></td>
                                            <td><?php echo $joomlaInfo['DBUSER']; ?></td>
                                            <td><?php echo $joomlaInfo['PASSWORD']; ?></td>
                                            <td><?php echo $joomlaInfo['PREFIX']; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                                <td colspan="2">
                                    <h4>Creating and deleting sites and databases</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>addsite</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#addsite_modal">Add a new local
                                        website</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>delsite</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#delsite_modal">Delete a local
                                        website</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>adddb</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#adddb_modal">Add a new
                                        database</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>deldb</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#deldb_modal">Delete a
                                        database</a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <h4>PHP</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>setsitephp</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#setsitephp_modal">Set the PHP
                                        version of a local website</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>sphp</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#sphp_modal">Set the PHP CLI
                                        version</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>xdebug</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#xdebug_modal">Turn xdebug on or
                                        off in the installed PHP versions or show status</a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <h4>Backup & Restore</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jbackup</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#jbackup_modal">Create a tar gzip
                                        or zip backup a Joomla website</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jbackupall</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#jbackupall_modal">Create a
                                        backup of all local Joomla websites</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jrestore</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#jrestore_modal">Restore a website backup</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jdbdump</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#jdbdump_modal">Create a database
                                        dump of a Joomla website</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jdbdumpall</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#jdbdumpall_modal">Create a
                                        database dump of all Joomla website databases</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jdbimp</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#jdbimp_modal">Import a database
                                        dump into the database of a Joomla website</a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <h4>Joomla site(s) information</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jlistjoomlas</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#jlistjoomlas_modal">List all
                                        Joomla websites in your development environment</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>joomlainfo</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#joomlainfo_modal">Display
                                        information about a Joomla website</a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <h4>Starting and stopping services</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startdev | stopdev | restartdev</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#startdev_modal">Start | Stop |
                                        Restart all services: Apache/NgniX, MariaDB, PHP FPM, DNSmasq, Mailpit</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startapache | stopapache | restartapache</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#startapache_modal">Start | Stop
                                        | Restart Apache service</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startnginx | stopnginx | restartnginx</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#startnginx_modal">Start | Stop |
                                        Restart NginX service</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startmariadb | stopmariadb | restartmariadb</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#startmariadb_modal">Start | Stop
                                        | Restart MariaDB service</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startphpfpm | stopphpfpm | restartphpfpm</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#startphpfpm_modal">Start | Stop
                                        | Restart PHP-FPM services</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startdnsmasq | stopdnsmasq | restartdnsmasq</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#startdnsmasq_modal">Start | Stop
                                        | Restart DNSMasq service</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>startmailpit | stopmailpit | restartmailpit</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#startmailpit_modal">Start | Stop
                                        | Restart Mailpit service</a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <h4>Miscellaneous</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>jdbdropall</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#jdbdropall_modal">Drop all
                                        database tables of a Joomla website</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>latestjoomla</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#latestjoomla_modal">Download a
                                        zipfile of Joomla! and unzip it in the current folder</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>setserver</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#setserver_modal">Switch between
                                        Apache and NginX webserver</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>setrights</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#setrights_modal">Set filerights
                                        of the current folder and all subfolders</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre><code><strong>checkupdates</strong></code></pre>
                                </td>
                                <td>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#checkupdates_modal">Checks and
                                        optionally installs updates for local scripts and the landingpage.</a>
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
                        <div class="accordion" id="faqList">
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faq_list_apache" aria-expanded="true"
                                        aria-controls="faq_list_apache">
                                        Apache
                                    </button>
                                </p>
                                <div id="faq_list_apache" class="accordion-collapse collapse" data-bs-parent="#faqList">
                                    <div class="accordion-body">
                                        <div class="accordion" id="faqApache">
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_ap_1"
                                                        aria-expanded="true" aria-controls="faq_ap_1">
                                                        Where is the Apache configuration file?
                                                    </button>
                                                </p>
                                                <div id="faq_ap_1" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqApache">
                                                    <div class="accordion-body">
                                                        The Apache configuration file is <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $etcDir; ?>/httpd/httpd.conf</span>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_ap_2"
                                                        aria-expanded="true" aria-controls="faq_ap_2">
                                                        Where are the Apache local website server configurations?
                                                    </button>
                                                </p>
                                                <div id="faq_ap_2" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqApache">
                                                    <div class="accordion-body">
                                                        The Apache local website server configuration files are in <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $etcDir; ?>/httpd/vhosts</span>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_ap_3"
                                                        aria-expanded="true" aria-controls="faq_ap_3">
                                                        Where are the Apache templates?
                                                    </button>
                                                </p>
                                                <div id="faq_ap_3" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqApache">
                                                    <div class="accordion-body">
                                                        The Apache templates are at <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $etcDir; ?>/httpd/templates</span>.<br>
                                                        The file <span
                                                            class="badge bg-secondary fw-light font-monospace">template.conf</span>
                                                        is used to create
                                                        a new local website configuration.<br>
                                                        The file <span
                                                            class="badge bg-secondary fw-light font-monospace">index.php</span>
                                                        is placed in the root
                                                        of a new local website that you create with the <span
                                                            class="badge bg-secondary fw-light font-monospace">addsite
                                                            script.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_ap_4"
                                                        aria-expanded="true" aria-controls="faq_a[_4">
                                                        Where are the local websites located?
                                                    </button>
                                                </p>
                                                <div id="faq_ap_4" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqApache">
                                                    <div class="accordion-body">
                                                        Every website that you create with the <span
                                                            class="badge bg-secondary fw-light font-monospace">addsite</span>
                                                        script is stored in the folder you set at installation.<br>
                                                        On your system that is <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $rootFolder; ?></span>.<br>
                                                        So the root folder for website <span
                                                            class="badge bg-secondary fw-light font-monospace">joomla5</span>
                                                        is then <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $rootFolder; ?>/joomla5</span>.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faq_list_nginx" aria-expanded="true"
                                        aria-controls="faq_list_nginx">
                                        NginX
                                    </button>
                                </p>
                                <div id="faq_list_nginx" class="accordion-collapse collapse" data-bs-parent="#faqList">
                                    <div class="accordion-body">
                                        <div class="accordion" id="faqNginX">
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_nx_1"
                                                        aria-expanded="true" aria-controls="faq_nx_1">
                                                        Where is the NginX configuration file?
                                                    </button>
                                                </p>
                                                <div id="faq_nx_1" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqNginX">
                                                    <div class="accordion-body">
                                                        The NginX configuration file is <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $etcDir; ?>/nginx/nginx.conf</span>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_nx_2"
                                                        aria-expanded="true" aria-controls="faq_nx_2">
                                                        Where are the NginX local website server configurations?
                                                    </button>
                                                </p>
                                                <div id="faq_nx_2" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqNginX">
                                                    <div class="accordion-body">
                                                        The NginX local website server configuration files are in <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $etcDir; ?>/nginx/servers</span>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_nx_3"
                                                        aria-expanded="true" aria-controls="faq_nx_3">
                                                        Where are the NginX templates?
                                                    </button>
                                                </p>
                                                <div id="faq_nx_3" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqNginX">
                                                    <div class="accordion-body">
                                                        The NginX templates are at <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $etcDir; ?>/nginx/templates</span>.<br>
                                                        The file <span
                                                            class="badge bg-secondary fw-light font-monospace">template.conf</span>
                                                        is used to create
                                                        a new local website server configuration.<br>
                                                        The file <span
                                                            class="badge bg-secondary fw-light font-monospace">index.php</span>
                                                        is placed in the root
                                                        of a new local website that you create with the <span
                                                            class="badge bg-secondary fw-light font-monospace">addsite
                                                            script.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_nx_4"
                                                        aria-expanded="true" aria-controls="faq_nx_4">
                                                        Where are the local websites located?
                                                    </button>
                                                </p>
                                                <div id="faq_nx_4" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqNginX">
                                                    <div class="accordion-body">
                                                        Every website that you create with the <span
                                                            class="badge bg-secondary fw-light font-monospace">addsite</span>
                                                        script is stored in the folder you set at installation.<br>
                                                        On your system that is <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $rootFolder; ?></span>.<br>
                                                        So the root folder for website <span
                                                            class="badge bg-secondary fw-light font-monospace">joomla5</span>
                                                        is then <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $rootFolder; ?>/joomla5</span>.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faq_list_mdb" aria-expanded="true"
                                        aria-controls="faq_list_mdb">
                                        MariaDB
                                    </button>
                                </p>
                                <div id="faq_list_mdb" class="accordion-collapse collapse" data-bs-parent="#faqList">
                                    <div class="accordion-body">
                                        <div class="accordion" id="faqMariaDB">
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_mdb_1"
                                                        aria-expanded="true" aria-controls="faq_mdb_1">
                                                        I want to manage databases and tables in the browser. How can I
                                                        do that?
                                                    </button>
                                                </p>
                                                <div id="faq_mdb_1" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqMariaDB">
                                                    <div class="accordion-body">
                                                        Simply click the blue Adminer button at the top of the screen.
                                                        Login with the password you set at the installation to manage
                                                        databases and the tables.<br>
                                                        The default password is <span
                                                            class="badge bg-secondary fw-light font-monospace">root</span>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_mdb_2"
                                                        aria-expanded="true" aria-controls="faq_mdb_2">
                                                        How can I add a new database?
                                                    </button>
                                                </p>
                                                <div id="faq_mdb_2" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqMariaDB">
                                                    <div class="accordion-body">
                                                        You can create a new database with the <span
                                                            class="badge bg-secondary fw-light font-monospace">adddb</span>
                                                        script. Type <span
                                                            class="badge bg-secondary fw-light font-monospace">adddb
                                                            -h</span> in a terminal for syntax and options.<br>
                                                        You can also click the blue Adminer button above to open
                                                        Adminer. Login with password <span
                                                            class="badge bg-secondary fw-light font-monospace">root</span>
                                                        to manage databases and the tables inside the databases.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_mdb_3"
                                                        aria-expanded="true" aria-controls="faq_mdb_3">
                                                        How do I update Adminer?
                                                    </button>
                                                </p>
                                                <div id="faq_mdb_3" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqMariaDB">
                                                    <div class="accordion-body">
                                                        When Adminer has an update you will see the new version number
                                                        in red at the top left of the Adminer screen.<br>
                                                        To update Adminer, download the latest version from <a
                                                            href="https://www.adminer.org/latest.php"
                                                            target="_blank">https://www.adminer.org/latest.php</a>.<br>
                                                        Save the file as adminer.php in the folder you set as the folder
                                                        path where your websites will be stored. By default this is
                                                        <span
                                                            class="badge bg-secondary fw-light font-monospace">/Users/<?php echo $username; ?>/Development/Sites</span>.
                                                        Overwrite the existing adminer.php file.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_mdb_4"
                                                        aria-expanded="true" aria-controls="faq_mdb_4">
                                                        Where are the database backups located that I create with the
                                                        &nbsp;<span
                                                            class="badge bg-secondary fw-light font-monospace">jdbdumpall</span>&nbsp;
                                                        script?
                                                    </button>
                                                </p>
                                                <div id="faq_mdb_4" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqMariaDB">
                                                    <div class="accordion-body">
                                                        On your system these are saved in the folder <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $mariadbbackupFolder; ?></span>.<br>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faq_list_php" aria-expanded="true"
                                        aria-controls="faq_list_php">
                                        PHP
                                    </button>
                                </p>
                                <div id="faq_list_php" class="accordion-collapse collapse" data-bs-parent="#faqList">
                                    <div class="accordion-body">
                                        <div class="accordion" id="faqPHP">
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_php_1"
                                                        aria-expanded="true" aria-controls="faq_php_1">
                                                        Where are the php.ini files?
                                                    </button>
                                                </p>
                                                <div id="faq_php_1" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqPHP">
                                                    <div class="accordion-body">
                                                        The folder <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $etcDir; ?>/php</span>
                                                        holds all installed PHP versions. Each PHP version has its own
                                                        folder with a php.ini file.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_php_2"
                                                        aria-expanded="true" aria-controls="faq_php_2">
                                                        Where are the configuration files for Xdebug?
                                                    </button>
                                                </p>
                                                <div id="faq_php_2" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqPHP">
                                                    <div class="accordion-body">
                                                        The folder <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $etcDir; ?>/php</span>
                                                        holds all installed PHP versions. Each PHP version has its own
                                                        folder.<br>
                                                        In each PHP version folder you will find the subfolder <span
                                                            class="badge bg-secondary fw-light font-monospace">conf.d</span>
                                                        that has the file <span
                                                            class="badge bg-secondary fw-light font-monospace">ext-xdebug.ini</span>
                                                        which is the configuration
                                                        file for Xdebug.<br>
                                                        So for PHP 8.3 the Xdebug ini file is at <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $etcDir; ?>/php/8.3/conf.d/ext-xdebug.ini</span>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_php_3"
                                                        aria-expanded="true" aria-controls="faq_php_3">
                                                        At which port number is Xdebug running?
                                                    </button>
                                                </p>
                                                <div id="faq_php_3" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqPHP">
                                                    <div class="accordion-body">
                                                        For all PHP versions, Xdebug runs at port <strong>9003</strong>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_php_4"
                                                        aria-expanded="true" aria-controls="faq_php_4">
                                                        I want to temporarily disable Xdebug. How do I do that?
                                                    </button>
                                                </p>
                                                <div id="faq_php_4" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqPHP">
                                                    <div class="accordion-body">
                                                        Open a terminal and type the command <span
                                                            class="badge bg-secondary fw-light font-monospace">xdebug
                                                            off</span>. That will restart all PHP versions and then
                                                        Xdebug is disabled.<br>
                                                        To re-enable Xdebug type the command <span
                                                            class="badge bg-secondary fw-light font-monospace">xdebug
                                                            on</span> in a terminal.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_php_5"
                                                        aria-expanded="true" aria-controls="faq_php_5">
                                                        How do I switch to another PHP CLI version?
                                                    </button>
                                                </p>
                                                <div id="faq_php_5" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqPHP">
                                                    <div class="accordion-body">
                                                        You can switch to another PHP CLI version with the <span
                                                            class="badge bg-secondary fw-light font-monospace">sphp
                                                            &lt;PHP version&gt;</span> command.<br>
                                                        For example <span
                                                            class="badge bg-secondary fw-light font-monospace">sphp
                                                            7.4</span> switches the CLI
                                                        version to PHP 7.4.<br>
                                                        The xdebug command described above also has effect for the CLI
                                                        version.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faq_list_cli" aria-expanded="true"
                                        aria-controls="faq_list_cli">
                                        Command line tools
                                    </button>
                                </p>
                                <div id="faq_list_cli" class="accordion-collapse collapse" data-bs-parent="#faqList">
                                    <div class="accordion-body">
                                        <div class="accordion" id="faqCLI">
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_cli_0"
                                                        aria-expanded="true" aria-controls="faq_cli_0">
                                                        How do I install updates for the installed Command line tools?
                                                    </button>
                                                </p>
                                                <div id="faq_cli_0" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqCLI">
                                                    <div class="accordion-body">
                                                        Check the update instructions at <a
                                                            href="https://github.com/renekreijveld/macOS_PHP_local_development/blob/main/update.md"
                                                            target="_blank">Github.</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_joomla_1"
                                                        aria-expanded="true" aria-controls="faq_joomla_1">
                                                        I want to make a database dump of a Joomla website. How do I do
                                                        that?
                                                    </button>
                                                </p>
                                                <div id="faq_joomla_1" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqCLI">
                                                    <div class="accordion-body">
                                                        Open a terminal and go to the root of the Joomla website you
                                                        want to make a database dump for.<br>
                                                        Type the command <span
                                                            class="badge bg-secondary fw-light font-monospace">jdbdump</span>.<br>
                                                        This will create a database dump <span
                                                            class="badge bg-secondary fw-light font-monospace">&lt;database_name&gt;.sql</span>.
                                                        The database name is automatically detected from the
                                                        configuration.php file.<br>
                                                        To see al options for jdbdump, type <span
                                                            class="badge bg-secondary fw-light font-monospace">jdbdump
                                                            -h</span>.<br><br>
                                                        If you want to import the database dump back into the database,
                                                        type the command <span
                                                            class="badge bg-secondary fw-light font-monospace">jdbimp</span>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_joomla_2"
                                                        aria-expanded="true" aria-controls="faq_joomla_2">
                                                        I want to make a full backup of a Joomla website. How do I do
                                                        that?
                                                    </button>
                                                </p>
                                                <div id="faq_joomla_2" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqCLI">
                                                    <div class="accordion-body">
                                                        Open a terminal and go to the root of the Joomla website you
                                                        want to backup.<br>
                                                        Type the command <span
                                                            class="badge bg-secondary fw-light font-monospace">jbackup</span>.<br>
                                                        This will create a backup including database dump of the
                                                        website.<br>
                                                        The backup is save in the folder <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $sitesbackupFolder; ?></span>.<br>
                                                        To see al options for jbackup, type <span
                                                            class="badge bg-secondary fw-light font-monospace">jbackup
                                                            -h</span>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_joomla_3"
                                                        aria-expanded="true" aria-controls="faq_joomla_3">
                                                        How do I restore a Joomla website from a backup that I created
                                                        with the &nbsp;<span
                                                            class="badge bg-secondary fw-light font-monospace">jbackup</span>&nbsp;
                                                        script?
                                                    </button>
                                                </p>
                                                <div id="faq_joomla_3" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqCLI">
                                                    <div class="accordion-body">
                                                        Find the filename of the backup. Backups are stored in the
                                                        folder <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $sitesbackupFolder; ?></span>.<br>
                                                        Open a terminal and go to the root folder of your local
                                                        websites: <span
                                                            class="badge bg-secondary fw-light font-monospace"><?php echo $rootFolder; ?></span>.<br><br>
                                                        Is the backup file a ZIP file (filename ends with .zip)?<br>
                                                        Then type the command <span
                                                            class="badge bg-secondary fw-light font-monospace">unzip -q
                                                            -o
                                                            <?php echo $sitesbackupFolder; ?>/&lt;backup_filename&gt;</span>.<br><br>
                                                        Is the backup file a Tar Gzip file (filename ends with
                                                        .tgz)?<br>
                                                        Then type the command <span
                                                            class="badge bg-secondary fw-light font-monospace">tar xzf
                                                            <?php echo $sitesbackupFolder; ?>/&lt;backup_filename&gt;</span>.<br><br>
                                                        When the backup extraction is ready, go into the folder of the
                                                        website with the cd command.<br>
                                                        Then import the database backup with the <span
                                                            class="badge bg-secondary fw-light font-monospace">jdbimp</span>
                                                        command.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faq_list_mail" aria-expanded="true"
                                        aria-controls="faq_list_mail">
                                        Mailpit
                                    </button>
                                </p>
                                <div id="faq_list_mail" class="accordion-collapse collapse" data-bs-parent="#faqList">
                                    <div class="accordion-body">
                                        <div class="accordion" id="faqMailpit">
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_mp_1"
                                                        aria-expanded="true" aria-controls="faq_mp_1">
                                                        I want to test mail sending with Mailpit. How do I do that?
                                                    </button>
                                                </p>
                                                <div id="faq_mp_1" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqMailpit">
                                                    <div class="accordion-body">
                                                        Using Mailpit is very easy.<br>
                                                        <ol>
                                                            <li>Login into the backend of a local Joomla website.</li>
                                                            <li>Go to System > Global Configuration.</>
                                                            <li>Go to the tab Server.</li>
                                                            <li>In the Mail section, make sure Mailer is set to
                                                                <strong>PHP Mail</strong>.
                                                            </li>
                                                            <li>Click Save & Close.</li>
                                                            <li>Go once more to System > Global Configuration > tab
                                                                Server.</li>
                                                            <li>At the bottom of the page, click the Send Test Mail
                                                                button.</li>
                                                            <li>Click Close.</li>
                                                            <li>Click the Mailpit button at the top of this page.</li>
                                                            <li>You will then see the mail sent from Joomla.</li>
                                                        </ol>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faq_list_homebrew" aria-expanded="true"
                                        aria-controls="faq_list_homebrew">
                                        Homebrew
                                    </button>
                                </p>
                                <div id="faq_list_homebrew" class="accordion-collapse collapse"
                                    data-bs-parent="#faqList">
                                    <div class="accordion-body">
                                        <div class="accordion" id="faqHomebrew">
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_hb_1"
                                                        aria-expanded="true" aria-controls="faq_hb_1">
                                                        How do I check for Homebrew updates?
                                                    </button>
                                                </p>
                                                <div id="faq_hb_1" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqHomebrew">
                                                    <div class="accordion-body">
                                                        Open a terminal and type the command <span
                                                            class="badge bg-secondary fw-light font-monospace">brew
                                                            update</span> and press Enter.<br>
                                                        Homebrew then lists the number of outdated formulae.<br>
                                                        To update all outdated formulae, type the command <span
                                                            class="badge bg-secondary fw-light font-monospace">brew
                                                            upgrade</span> and press Enter.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_hb_2"
                                                        aria-expanded="true" aria-controls="faq_hb_2">
                                                        How do I uninstall a formula or cask from Homebrew?
                                                    </button>
                                                </p>
                                                <div id="faq_hb_2" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqHomebrew">
                                                    <div class="accordion-body">
                                                        You can list all installed formulae and casks with the command
                                                        <span class="badge bg-secondary fw-light font-monospace">brew
                                                            list</span>.<br>
                                                        You can uninstall a formula or casks with the command <span
                                                            class="badge bg-secondary fw-light font-monospace">brew
                                                            uninstall FORMULA|CASK...</span>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <p class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#faq_hb_3"
                                                        aria-expanded="true" aria-controls="faq_hb_3">
                                                        How do I uninstall Homebrew?
                                                    </button>
                                                </p>
                                                <div id="faq_hb_3" class="accordion-collapse collapse"
                                                    data-bs-parent="#faqHomebrew">
                                                    <div class="accordion-body">
                                                        Run the official uninstall script. You can find that here: <a
                                                            href="https://github.com/homebrew/install#uninstall-homebrew">Uninstall
                                                            Homebrew</a>.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
                        <p>This a command-line driven local PHP development environment for macOS. It allows you to run
                            PHP based websites on your macOS machine, for local PHP webdevelopment.</p>
                        <p>This setup has the following elements:</p>
                        <ul>
                            <li>Although this setup is command-line driven, a set of scripts is provided to do the heavy
                                lifting.</li>
                            <li>Both Apache and NginX are installed as webservers. You can easyily switch between the
                                two.</li>
                            <li>MariaDB database server.</li>
                            <li>PHP 7.4/8.1/8.2/8.3/8.4, multiple local sites can run concurrently with different PHP
                                versions.</li>
                            <li>Xdebug installed and enabled in all PHP versions.</li>
                            <li>Option to disable/enable Xdebug.</li>
                            <li>SSL certificates on all local websites installed automatically.</li>
                            <li>Mailpit for easy email testing.</li>
                            <li>Scripts to add and delete local websites and databases and to start, stop and restart
                                the development stack.</li>
                            <li>A comprehensive set of bash scripts tweaked for local Joomla! CMS website development.
                            </li>
                        </ul>
                        <p>Is this better or easier than tools like MAMP, Laravel Valet, Laravel Herd and other tools
                            like this?<br>
                            Probably not :-).<br>But it is very open and flexible and if you're not afraid to use the
                            macOS terminal you can modify it to your liking. Just checkout the Github Repo.</p>
                        <p><i class="nf nf-fa-github"></i> <a
                                href="https://github.com/renekreijveld/macOS_NginX_local_development"
                                target="_blank">Created by René Kreijveld</a>.</p>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-center" style="font-size: 0.9rem;">Like this tool? Please consider <a
                href="https://renekreijveld.nl/donate" target="_blank">a donation</a> to support further development.
        </p>
    </div>


    <div class="modal fade" id="jbackup_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">jbackup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Create a tar gzip or zip backup a Joomla website</strong><br><br>
                    <pre><code>Usage: jbackup [-n &lt;name&gt;] [-z] [-t] [-o] [-s] [-h]

Default action is .tgz backup.
-n the name for the website to backup.
-z Zip. Backup to a zipfile instead of a tgzfile.
-t Add a date/time-stamp to the backup file.
-o Overwrite existing backupfile and/or database dump.
-s silent, no messages will be shown (optional).
-h Help. Display this info.</code></pre><br>
                    To restore a backup, extract the backup file one folder up of the root of the Joomla website.<br>
                    This usually is the folder /Users/&lt;your username&gt;/Development/Sites.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="jbackupall_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">jbackupall</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Create a backup of all local Joomla websites</strong><br><br>
                    <pre><code>Usage: jbackupall [-s] [-h]

-s Silent. Do not display any messages to standard output.
-h Help. Display this info.</code></pre><br>
                    This scrips searches for all Joomla websites in your development environment and
                    creates a backup of each website found.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="jrestore_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">jrestore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Restore a website backup to the website folder</strong><br><br>
                    <pre><code>Usage: jrestore [-c] [-s] [-h] backupfile

-c cleanup the existing website files before restoring the backup.
-s Silent mode, do not display any messages to standard output.
-h Help, display this info.

The jrestore script restores a website backup to the website folder.
The backupfile must be in the current folder or in the sites backup folder.</code></pre><br>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="jdbdump_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">jdbdump</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Create a database dump of a Joomla website</strong><br><br>
                    <pre><code>Usage: jdbdump [-n &lt;name&gt;] [-t] [-c] [-o] [-h]

-n the name for the website to make the database dump for.
-t Add a date-time-stamp to the database dump filename.
-c Compress the database dump with gzip.
-o Overwrite existing database dump.
-h Help. Display this info.</code></pre><br>
                    Run this command in the root of a Joomla website.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="jdbdumpall_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">jdbdumpall</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Create a database dump of all Joomla website databases</strong><br><br>
                    <pre><code>Usage: jdbdumpall [-t] [-c] [-f] [-s] [-h]

-t add timestamp to database dump.
-c compress database dump .sql file with gzip.
-f force: always overwrite existing backup files.
-s silent, do not display any messages to standard output.
-h help, display this info.</code></pre><br>
                    This scrips creates a database dump for all existing MariaDB databases.<br>
                    The database dumps are save in the Databases backup folder.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="jdbimp_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">jdbimp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Import a database dump into the database of a Joomla website</strong><br><br>
                    <pre><code>Usage: jdbimp [-s] [-h]
-s silent, no messages will be shown (optional).
-h Help. Display this info.</code></pre><br>
                    Run this command in the root of a Joomla website.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="jlistjoomlas_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">jlistjoomlas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>List all Joomla websites in your development environment</strong><br><br>
                    <pre><code>Usage: jlistjoomlas [-s] [-h] [-r release]

-s Short. Only display path and Joomla version.
-r Release version. Only display information about Joomla sites with given release version (e.g., 1.5, 2.5, 3.4).
-h Help. Display this info.</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="joomlainfo_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">joomlainfo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Display information about a Joomla website</strong><br><br>
                    <pre><code>Usage: joomlainfo</code></pre><br>
                    Run this command in the root of a Joomla website.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="jdbdropall_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">jdbdropall</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Drop all database tables of a Joomla website</strong><br><br>
                    <pre><code>Usage: jdbdropall [-f] [-s] [-h]

-f force, do not ask confirmation before dropping tables.
-s silent, no messages will be shown (optional).
-h Help. Display this info.</code></pre><br>
                    Run this command in the root of a Joomla website.<br>
                    The script asks your confirmation before dropping all tables of you do not specify the -f option.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="latestjoomla_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">latestjoomla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Download Joomla and unzip in current folder</strong><br><br>
                    <pre><code>Usage: Usage: latestjoomla [-v <joomla version>] [-u] [-s] [-h]

-v download a specific Joomla version (3.10.0 and up).
-u download from a specific URL.
-s silent, no messages will be shown.
-h Help. Display this info.

If you do not specify a version, the latest version will be downloaded.</code></pre><br>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addsite_modal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">addsite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Add a new local website</strong><br><br>
                    <pre><code>Usage: addsite -n &lt;sitename&gt; -p &lt;php_version&gt; [-d &lt;db_name&gt;] [-i] [-j] [-v &lt;version&gt;] [-u] [-o] [-s] [-h]

-n the website name for the new website (without spaces).
-p the PHP version for the new website.
-d the database name for the new website.
-i ignore existing folder: if the folder for the new site already exists, it will use that as the rootfolder.
-j download and install Joomla.
-v specify a joomla version to download and install, you can use 3.10.0 and up.
-u download from a specific URL.
-o open the new website in the browser after creation.
-s silent, no messages will be shown.
-h display this help.

If you do not use the -v option, the latest version will be downloaded.</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delsite_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">delsite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Delete a local website</strong><br><br>
                    <pre><code>Usage: delsite -n &lt;website_name&gt; [-d] [-f] [-s] [-h]

-n the name for the website (without spaces).
-d also drop the database.
-f force, do not ask confirmation before deleting website and database.
-s silent, no messages will be shown.
-h display this help.</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adddb_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">adddb</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Add a new database</strong><br><br>
                    <pre><code>Usage: adddb -d &lt;database name&gt; [-s] [-h]

-d the database name for the new website (input without spaces, mandatory).
-s silent, no messages will be shown (optional).
-h display this help.</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deldb_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">deldb</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Delete a database</strong><br><br>
                    <pre><code>Usage: deldb -d <database_name> [-f] [-s] [-h]

-d the name of the database to delete.
-f force, do not ask confirmation before deleting the database.
-s silent, no messages will be shown.
-h display this help.</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="setsitephp_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">setsitephp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Set the PHP version of a local website</strong><br><br>
                    <pre><code>Usage: setsitephp -n &lt;website_name&gt; -p &lt;php_version&gt; [-s] [-h]

-n the name for the new website (without spaces).
-p the PHP version for the website.
-s silent, no messages will be shown.
-h display this help.</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sphp_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">sphp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Set the PHP CLI version</strong><br><br>
                    <pre><code>Usage: sphp &lt;php version&gt;</code></pre><br>
                    Possible PHP versions: <?php echo implode(', ', $phpVersions); ?>.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="xdebug_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">xdebug</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Turn xdebug on or off in the installed PHP versions</strong><br><br>
                    <pre><code>Usage: xdebug &lt;on|off|status&gt;</code></pre><br>
                    On: enables Xdebug for all PHP versions.<br>
                    Off: disables Xdebug for all PHP versions.<br>
                    Status: show the disables Xdebug status for all PHP versions.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="setrights_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">setrights</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Set filerights of the current folder and all subfolders</strong><br>
                    Folder rights will be set to 755 (rwx-r-xr-x), file rights will be site to 644 (rw-r--r--).<br><br>
                    <pre><code>Usage: setrights</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="checkupdates_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">checkupdates</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Checks and optionally installs updates for local scripts and the
                        landingpage.</strong><br><br>
                    <pre><code>checkupdates [-u] [-i] [-h]

-u show only updates.
-i install updates.
-h Help. Display this info.</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="startdev_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">startdev | stopdev | restartdev</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Start | Stop | Restart all services: Apache/NgniX, MariaDB, PHP FPM, DNSmasq,
                        Mailpit</strong><br><br>
                    <pre><code>Usage: startdev | stopdev | restartdev</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="startapache_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">startapache | stopapache | restartapache</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Start | Stop | Restart Apache service</strong><br><br>
                    <pre><code>Usage: startapache | stopapache | restartapache</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="startnginx_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">startnginx | stopnginx | restartnginx</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Start | Stop | Restart NginX service</strong><br><br>
                    <pre><code>Usage: startnginx | stopnginx | restartnginx</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="startmariadb_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">startmariadb | stopmariadb | restartmariadb</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Start | Stop | Restart MariaDB service</strong><br><br>
                    <pre><code>Usage: startmariadb | stopmariadb | restartmariadb</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="startphpfpm_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">startphpfpm | stopphpfpm | restartphpfpm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Start | Stop | Restart PHP FPM services</strong><br><br>
                    <pre><code>Usage: startphpfpm | stopphpfpm | restartphpfpm</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="startdnsmasq_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">startdnsmasq | stopdnsmasq | restartdnsmasq</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Start | Stop | Restart DNSMasq service</strong><br><br>
                    <pre><code>Usage: startdnsmasq | stopdnsmasq | restartdnsmasq</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="startmailpit_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">startmailpit | stopmailpit | restartmailpit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Start | Stop | Restart Mailpit service</strong><br><br>
                    <pre><code>Usage: startmailpit | stopmailpit | restartmailpit</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="setserver_modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">setserver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <strong>Switch between Apache and NginX webserver</strong><br><br>
                    <pre><code>Usage: setserver -n | -a [-s] [-h]

-n set webserver to NginX.
-a set webserver to Apache.

You must specify either -n or -a.

The other options are:
-s silent, no messages will be shown (optional).
-h display this help.</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>