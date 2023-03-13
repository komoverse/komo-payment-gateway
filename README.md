# System Requirements
- PHP 8.1

# Setup Instructions
1. Copy .env.example and save as .env
2. Update DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env
3. Update KOMO WALLET SETTINGS and PAYMENT GATEWAY CONFIG in .env
4. Run composer install
5. Run php artisan key:generate
6. Run php artisan migrate
7. Run php artisan serve
