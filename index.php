<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Pirate Bay Search</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="main-content">
            <button onclick="window.location.href='index.php'" style="background-color: grey;">back to main site</button>
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

            <div id="loading" class="loading" style="display: none;">Searching...</div>
            <div id="resultsContainer">

                
            <?php
            $MAX_ROWS = 5; // change to whatever you like

            if ($_SERVER["REQUEST_METHOD"] == "GET") {
                $query = htmlspecialchars($_GET['query']);

                if (empty($query)) {
                    echo "<div class='response'><p>Please enter a search query.</p></div>";
                    exit;
                }

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

            <script>
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
                    alert(data.message);
                    if (data.status === 'success') {
                        document.getElementById('searchForm').reset();
                        document.getElementById('resultsContainer').innerHTML = '';
                    }
                })
                .catch(error => console.error('Error:', error));
            }
            </script>
        </div>
    </div>
</body>
</html>
