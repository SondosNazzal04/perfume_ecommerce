<?php
// File: classes/AIAdvisor.php

class AIAdvisor {
    private $apiKey;

    public function __construct() {
        $config = require __DIR__ . '/../config/ai.php';
        $this->apiKey = $config['gemini_api_key'];
    }

    public function getRecommendations($userQuery, $products) {
        $productList = [];
        foreach ($products as $p) {
            $productList[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'price' => $p['price'],
                'scent' => $p['scent_family'] ?? 'Unknown',
                'desc' => substr($p['description'] ?? '', 0, 100)
            ];
        }
        $jsonProducts = json_encode($productList);

        $prompt = "You are a helpful perfume advisor. 
        User request: '{$userQuery}'.
        Here is the list of available products in our store: {$jsonProducts}.
        You MUST recommend 2 to 3 products from this list ONLY that match the user's request.
        Return ONLY a JSON array of the recommended product IDs. Do not include any markdown, text, or explanation.
        Example output: [1, 5, 8]";

        $data = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ]
        ];

        // نفس النموذج والرابط القياسي
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
            $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
            
            $aiText = str_replace(['```json', '```'], '', $aiText);
            $aiText = trim($aiText);

            $ids = json_decode($aiText, true);
            return is_array($ids) ? $ids : [];
        }
        return [];
    }
}
?>