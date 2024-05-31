<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Pirate Bay Search</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .modal-content h2 {
            margin-top: 0;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }


    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <?php
            function qbittorrent_login($url, $username, $password) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url . "/api/v2/auth/login");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('username' => $username, 'password' => $password)));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
                $response = curl_exec($ch);

                if ($response === false) {
                    return false;
                }

                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpcode != 200) {
                    return false;
                }

                preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
                $cookies = array();
                foreach($matches[1] as $item) {
                    parse_str($item, $cookie);
                    $cookies = array_merge($cookies, $cookie);
                }
                return $cookies;
            }

            function get_downloading_torrents($url, $cookies) {
                $cookie_string = http_build_query($cookies, '', '; ');

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url . "/api/v2/torrents/info?filter=downloading");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: $cookie_string"));
                $response = curl_exec($ch);

                if ($response === false) {
                    return false;
                }

                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpcode != 200) {
                    return false;
                }

                return json_decode($response, true);
            }

            $url = "http://127.0.0.1:8080"; // Replace with your server IP and port or leave if everything is running on this server
            $username = "admin";
            $password = "adminadmin"; // replace with your password

            $cookies = qbittorrent_login($url, $username, $password);
            echo "<h1>Currently Downloading Movies</h1>";
            if (!$cookies) {
                echo "<div class='error'>Failed to log in to qBittorrent.</div>";
            } else {
                $downloading_torrents = get_downloading_torrents($url, $cookies);
                if (!$downloading_torrents) {
                    echo "<div class='error'>Failed to fetch downloading torrents or no torrents are downloading.</div>";
                } else {
                    echo "<ul>";
                    foreach ($downloading_torrents as $torrent) {
                        echo "<li>" . htmlspecialchars($torrent['name']) . " - <br> " . htmlspecialchars(round($torrent['progress'] * 100)) . "%</li>";
                    }
                    echo "</ul>";
                }
            }
            ?>
        </div>
        <div class="main-content">
            <!-- <button onclick="window.location.href='index.php'" style="background-color: grey;">Back to Main Site</button> -->
            <h1>Search The Pirate Bay</h1>

            <div class="tips">
                <!-- // change these to whatever you want -->
                <h3>Things to check before downloading:</h3>
                <ul>
                    <li>Check if the <strong>name matches</strong> and is what you want.</li>
                    <li>Make sure there are <strong>at least 10 seeders</strong> for the torrent.</li>
                    <li>Ensure the <strong>size is not above 10 GB</strong> or something that fits your requirement.</li>
                    <li>There is <strong>no need to download again</strong> after the confirmation message has appeared.</li>
                </ul>
            </div>

            <form id="searchForm" method="GET" action="">
                <input type="text" name="query" placeholder="Enter your search query (e.g., movie title)" value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>" required>
                <button type="submit">Search</button>
            </form>

            <div id="modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2 id="modal-title"></h2>
                    <p id="modal-text"></p>
                    <button id="modal-button" onclick="closeModal()">Ok</button>
                </div>
            </div>

           <div id="loading" class="loading" style="display: none;">Searching...</div>
            <div id="resultsContainer">
                <?php
                $MAX_ROWS = 5; // change to whatever you like

                if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET['query'])) {
                    $query = htmlspecialchars($_GET['query']);

                    $apiUrl = "https://tpb.party/s/?q=" . urlencode($query) . "&cat=201"; // 201 is for video category in tpb.party
                    $response = file_get_contents($apiUrl);

                    if ($response !== FALSE) {
                        $dom = new DOMDocument();
                        libxml_use_internal_errors(true);
                        $dom->loadHTML($response);
                        libxml_clear_errors();

                        $xpath = new DOMXPath($dom);
                        $rows = $xpath->query('//table[@id="searchResult"]/tr');

                        if ($rows->length > 0) {
                            echo "<div class='response'>";
                            echo "<h2>Search Results for '" . htmlspecialchars($query) . "':</h2>";
                            echo "<ul>";
                            $counter = 0;

                            foreach ($rows as $row) {
                                if ($counter >= $MAX_ROWS) break;

                                $name = $xpath->query('.//a[@class="detLink"]', $row)->item(0)->nodeValue;
                                $magnetLink = $xpath->query('.//a[contains(@href, "magnet")]', $row)->item(0)->getAttribute('href');
                                $seeders = $xpath->query('.//td[3]', $row)->item(0)->nodeValue;
                                $leechers = $xpath->query('.//td[4]', $row)->item(0)->nodeValue;
                                $size = $xpath->query('.//font[contains(text(), "Size")]', $row)->item(0)->nodeValue;

                                echo "<li>";
                                echo "<strong>" . htmlspecialchars($name) . "</strong><br>";
                                echo "Seeders: " . htmlspecialchars($seeders) . "<br>";
                                echo "Leechers: " . htmlspecialchars($leechers) . "<br>";
                                echo htmlspecialchars($size) . "<br>";
                                echo "<button onclick='handleDownload(\"" . htmlspecialchars($magnetLink) . "\")'>Download</button>";
                                echo "</li>";
                                $counter++;
                            }
                            echo "</ul>";
                            echo "</div>";
                        } else {
                            echo "<div class='response'><p>No results found.</p></div>";
                        }
                    } else {
                        echo "<div class='error'>An error occurred while fetching the API response.</div>";
                    }
                }
            ?>
        </div>
    </div>
</div>
<script>
    function showModal(title, text) {
        document.getElementById('modal-title').innerText = title;
        document.getElementById('modal-text').innerText = text;
        document.getElementById('modal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('modal').style.display = 'none';
    }

    document.getElementById('searchForm').addEventListener('submit', function(e) {
        document.getElementById('loading').style.display = 'block';
    });

    function handleDownload(magnetLink) {
        fetch('download.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ magnetLink: magnetLink }),
        })
        .then(response => response.json())
        .then(data => {
            showModal(data.status, data.message);
            if (data.status === 'success') {
                document.getElementById('searchForm').reset();
                document.getElementById('resultsContainer').innerHTML = '';
            }
        })
        .catch(error => alert('Error:', error));
    }
</script>
</body>
</html>
