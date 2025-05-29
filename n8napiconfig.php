<?php
// Security Advisory:
// This configuration file contains sensitive information. It is crucial to protect it.
// 1. Prevent Direct Web Access: Ensure your web server (e.g., Apache via .htaccess, Nginx) is configured
//    to deny direct access to this file. While an .htaccess rule can help, it's not foolproof
//    against all server misconfigurations or other vulnerabilities.
// 2. File Permissions: Set restrictive file permissions (e.g., 600 or 640) so that only the
//    web server user and necessary system administrators can read this file.
// 3. Production Environments - AVOID PLAIN TEXT SECRETS IN VERSION CONTROL:
//    For production, it is strongly recommended NOT to store plain text secrets (like database passwords or API keys)
//    directly in version-controlled files like this one. Instead, use more secure methods such as:
//    a) Environment Variables: Load secrets from environment variables (e.g., using `getenv('DB_PASSWORD')`).
//       These can be set in your server configuration or .env files (which should be in .gitignore).
//    b) Secrets Management Systems: Utilize dedicated systems like HashiCorp Vault, AWS Secrets Manager,
//       Google Cloud Secret Manager, or Azure Key Vault.
//    c) Encrypted Configuration Files: If config files must be used, consider encrypting them and decrypting
//       at runtime, though this adds complexity.
//
// The existing comment "Asigură-te că acest fișier nu este accesibil public, eventual blocheaza accesul din .htaccess si modifica permisiunile"
// is a good starting point, but the above provides more comprehensive advice for robust security.

return [
    'host'     => 'localhost', // Previously DB_HOST
    'username' => 'utilizaotr_baza_de_date', // Previously DB_USER
    'password' => 'parola_baza_de_date', // Previously DB_PASSWORD
    'database' => 'nume_baza_de_date', // Previously DB_NAME
    'api_key'  => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // Previously API_KEY
    'port'     => '3306' // Previously PORT
];
?>
