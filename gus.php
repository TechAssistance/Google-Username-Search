<?php
header('Content-Type: text/html');

function getPageSource($url) {
    // Read the list of proxies from the file.
    $proxies = file('proxies.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Keep trying with a different proxy until a connection is established or the maximum number of attempts is reached.
    $maxAttempts = count($proxies);
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        // Pick a random proxy from the list.
        $proxy = $proxies[array_rand($proxies)];

        $options = [
            'http' => [
                'method' => "GET",
                'header' => "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; .NET CLR 1.0.3705;)\r\n",
                'proxy' => "tcp://$proxy",
                'request_fulluri' => true,
            ]
        ];
        $context = stream_context_create($options);

        // Suppress errors and try to get the content.
        $content = @file_get_contents($url, false, $context);

        // If the connection was successful, return the content.
        if ($content !== false) {
            return $content;
        }
    }

    // If all proxies have been tried and none of them worked, return an empty string.
    return '';
}

function getMatches($toSearch, $regexPattern) {
    preg_match_all($regexPattern, $toSearch, $matches, PREG_PATTERN_ORDER);
    return $matches[1];
}

function isUri($source) {
    return !empty($source) && filter_var($source, FILTER_VALIDATE_URL);
}

function searchGoogle($query) {
  $pageSource = getPageSource("http://www.google.com/search?num=100&q=\"" . urlencode($query) . "\"");
  $matches = getMatches($pageSource, '/url\\?q=(.*?)&/');

  // Define the keywords you want to exclude from the results.
  $excludedKeywords = ['accounts.google.com/ServiceLogin', 'support.google.com/websearch', 'm.facebook.com'];

  $result = [];
  foreach ($matches as $match) {
      // Check if any of the excluded keywords appear in the match.
      $containsExcludedKeyword = false;
      foreach ($excludedKeywords as $keyword) {
          if (strpos($match, $keyword) !== false) {
              $containsExcludedKeyword = true;
              break;
          }
      }

      if ($containsExcludedKeyword) {
          // Skip over this match if it contains an excluded keyword.
          continue;
      }

      if (!in_array($match, $result) && !strpos($match, "googleusercontent") && !strpos($match, "/settings/ads")) {
          $result[] = "<a href=\"" . htmlspecialchars_decode($match) . "\">" . htmlspecialchars_decode($match) . "</a><br>";
      }
  }
  return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['username'])) {
    $username = $_GET['username'];
    $results = searchGoogle($username);
    foreach ($results as $result) {
        echo $result;
    }
} else {
    http_response_code(400);
    echo 'Invalid request.';
}
