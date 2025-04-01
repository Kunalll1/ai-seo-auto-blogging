<?php
if (!defined('ABSPATH')) exit;

// Generate encryption key using WordPress salts
function ai_seo_get_encryption_key() {
    return hash_hmac('sha256', AUTH_KEY . SECURE_AUTH_KEY, NONCE_SALT);
}

// Encrypt API key
function ai_seo_encrypt($data) {
    $key = ai_seo_get_encryption_key();
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Decrypt API key
function ai_seo_decrypt($encrypted_data) {
    $key = ai_seo_get_encryption_key();
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// Retrieve decrypted API key safely
function ai_seo_get_api_key() {
    $encrypted_key = get_option('ai_seo_api_key');
    if (!$encrypted_key) return ''; // Return empty if no key is stored
    return ai_seo_decrypt($encrypted_key);
}
