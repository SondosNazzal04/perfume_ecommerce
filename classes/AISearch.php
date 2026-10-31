<?php
// File: classes/AISearch.php

class AISearch {
    private $apiKey;

    public function __construct() {
        $config = require __DIR__ . '/../config/ai.php';
        $this->apiKey = $config['gemini_api_key'];
    }

    public function extractKeywords($query) {
        $prompt = "You are a perfume expert. The user is searching for: '{$query}'. 
                   Extract 3 to 5 English keywords (like Fresh, Citrus, Summer, Woody, Floral) that best match this request. 
                   Return ONLY a comma-separated list of keywords, nothing else. Do not add any extra text.";

        $data = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=" . $this->apiKey;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); 

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            $keywords = array_map('trim', explode(',', $aiText));
            return array_filter($keywords);
        }

        return []; 
    }
}
?>