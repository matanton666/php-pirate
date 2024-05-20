<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $CONFIG_DIR = "/var/www/.config"; // change to whatever you want

    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['magnetLink'])) {
        $magnetLink = escapeshellarg($data['magnetLink']);


        // Set the HOME environment variable to point to the config directory
        $env = "HOME=$CONFIG_DIR";
        $output = [];
        $return_var = 0;

        // Run the qBittorrent-nox command with the magnet link
        exec("$env qbittorrent-nox $magnetLink 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            // Command failed, output the error message
            echo "Failed to download the torrent. Error: " . implode("\n", $output);
        } else {
            echo "Torrent downloaded starting in background, will be ready soon.";
        }

    } else {
        echo "Invalid request.";
    }
} else {
    echo "Invalid request method.";
}
?>

