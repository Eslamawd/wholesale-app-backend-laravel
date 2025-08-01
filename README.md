
📦 Laravel Backend API — README
This Laravel backend provides a complete RESTful API for managing a services platform, including authentication, categories, services, orders, wallet transactions, and user roles.

🔧 Requirements
PHP >= 8.1

Composer

MySQL

Laravel Sanctum (for API token authentication)

Laravel CORS configured for frontend

🚀 Installation
bash

git clone https://github.com/yourrepo/project.git
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
Update the .env with your database credentials and app settings.

🔑 Authentication
Uses Laravel Sanctum to secure API routes.

POST /register — Register new users

POST /login — Login to receive token

POST /logout — Revoke token

GET /api/user — Get authenticated user info

🔒 Middleware
Most routes are protected via auth:sanctum

Admin routes require the user to have an admin role

📘 API Endpoints
🧑‍💼 Admin Endpoints
Method	Endpoint	Description
GET	/api/admin/users	List all users
GET	/api/admin/users/{id}	Show specific user
PUT	/api/admin/users/{id}	Update user
DELETE	/api/admin/users/{id}	Delete user
POST	/api/admin/users/{id}/change-role	Promote/demote role
GET	/api/admin/user/count	Count users
POST	/api/admin/wallet/deposit/{id}	Deposit to wallet
POST	/api/admin/wallet/withdraw/{id}	Withdraw from wallet
GET	/api/admin/revnue/count	Revenue statistics

📂 Category Management
Method	Endpoint
GET	/api/admin/categories
POST	/api/admin/categories
GET	/api/admin/categories/{id}
PUT	/api/admin/categories/{id}
DELETE	/api/admin/categories/{id}

🛎️ Service Management
Method	Endpoint
GET	/api/admin/services
POST	/api/admin/services
GET	/api/admin/services/{id}
PUT	/api/admin/services/{id}
DELETE	/api/admin/services/{id}

📦 Order Management
Method	Endpoint
GET	/api/admin/orders
POST	/api/admin/orders
GET	/api/admin/orders/{id}
PUT	/api/admin/orders/{id}
DELETE	/api/admin/orders/{id}
GET	/api/admin/order/count

💳 Wallet
GET /api/wallet/balance — Get user balance

POST /api/admin/wallet/deposit/{id} — Admin deposits money

POST /api/admin/wallet/withdraw/{id} — Admin withdraws money

🌍 Public Endpoints
Method	Endpoint	Description
GET	/api/services	List all services
GET	/api/services/{id}	Show service details
GET	/api/categories	Public category listing
POST	/api/orders	Create an order
GET	/api/orders	Get authenticated user's orders

🔐 CORS Settings
Make sure to update config/cors.php:

php

'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['https://your-frontend-domain.com'],
📂 Storage
To serve uploaded files, make sure you’ve run:

bash

php artisan storage:link
php artisan serve#   w h o l e s a l e - a p p - b a c k e n d - l a r a v e l 
 
    0rS9Amjq35E1dPa5fftv