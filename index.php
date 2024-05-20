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
        <h1>Search The Pirate Bay</h1>
        <form method="POST" action="">
            <input type="text" name="query" placeholder="Enter your search query" required>
            <button type="submit">Search</button>
        </form>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $query = htmlspecialchars($_POST['query']);
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
                        if ($counter >= 5) break;
                        
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

        <script>
        function handleDownload(magnetLink) {
            fetch('download.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ magnetLink: magnetLink }),
            })
            .then(response => response.text())
            .then(data => alert(data))
            .catch(error => console.error('Error:', error));
        }
        </script>
    </div>
</body>
</html>

