# Campus Lost & Found Management System (LaFMS)

A web-based Lost and Found Management System updated and verified for **PHP 8.4** (latest secure PHP release) and MySQL.

## Features
- User registration and authentication with secure password hashing (\PASSWORD_DEFAULT\ / Bcrypt)
- Secure sessions with \samesite\, \httponly\, and HTTPS awareness
- CSRF protection across forms and actions
- Item reporting (found/lost), claims management, and notification system
- Admin dashboard for reviewing and managing posts and claims
- Robust database connection handling hardened for PHP 8.1+ exception handling

## Database Setup
1. Import the schema and seed data into your MySQL server:
   \\\ash
   mysql -u root -p < Dump20261122.sql
   \\\
2. Configure credentials in \config.php\ or set environment variables:
   - \DB_HOST\ (default: \localhost\)
   - \DB_USER\ (default: \oot\)
   - \DB_PASS\
   - \DB_NAME\ (default: \campus_lost_found\)

## Requirements
- PHP 8.1+ (Recommended: PHP 8.4) with extensions: \mysqli\, \pdo_mysql\, \mbstring\, \openssl\, \curl\
- MySQL 8.0+ or MariaDB 10.4+
- Web server (Apache with mod_rewrite or Nginx)
