function getExchangeRate($currency) {
    $apiKey = '13a9f960b6af54fb05f501237db83012';
    $url = "https://api.exchangerate-api.com/v4/latest/PHP";

    // Fetch the data
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Check if the currency exists in the response
    if (isset($data['rates'][$currency])) {
        return $data['rates'][$currency];
    } else {
        return 1; // Return 1 if no exchange rate is found (no conversion)
    }
}

$exchange_rate = getExchangeRate($_POST['currency']);  // Fetch the rate for the selected currency
