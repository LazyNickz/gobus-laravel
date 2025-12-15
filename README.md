🚌 GoBus – Laravel Bus Booking System

GoBus is a Laravel-based bus booking system with route management, schedules, admin panel, and future support for ML-based demand prediction.

⸻

📌 Requirements

Make sure you have the following installed:
	•	PHP ≥ 8.1
	•	Composer
	•	MySQL / MariaDB
	•	Node.js & npm (for frontend assets)
	•	Git

Recommended local stacks:
	•	XAMPP / Laragon / MAMP (Windows & macOS)

⸻

📂 Project Setup

1️⃣ Clone the Repository

git clone https://github.com/LazyNickz/gobus-laravel.git
cd GoBus


⸻

2️⃣ Install PHP Dependencies

composer install


⸻

3️⃣ Install Frontend Dependencies

npm install
npm run build


⸻

4️⃣ Environment Configuration

Create .env file:

cp .env.example .env

Edit .env and update your database credentials:

APP_NAME=GoBus
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gobus
DB_USERNAME=root
DB_PASSWORD=

Generate app key:

php artisan key:generate


⸻

🗄️ Database Setup

5️⃣ Create Database

Create a MySQL database named:

gobus

6️⃣ Run Migrations

php artisan migrate

(Optional – with seeders)

php artisan migrate --seed


⸻

🔐 Admin Account (Manual Insert)

If admin login is not seeded, insert manually:

INSERT INTO users (name, email, password, role, created_at, updated_at)
VALUES (
  'Admin',
  'admin@gobus.local',
  '$2y$12$QwQxF7cJ7gkFhC5p0Zy0mOXx5GJcX2ZkG9z4R2QnZp0KzZqHc5Z6y',
  'admin',
  NOW(), NOW()
);

Login Credentials
	•	Email: admin@gobus.local
	•	Password: admin123

⸻

▶️ Run the Application

php artisan serve

Open browser:

http://127.0.0.1:8000


⸻

🧭 Important Routes

Feature	URL
User Home	/
Login	/login
Register	/register
Admin Dashboard	/admin/dashboard
Admin Schedules	/admin/schedules


⸻

🧠 Machine Learning (Planned Feature)

Future integration:
	•	Route demand prediction
	•	Peak booking detection
	•	Travel time estimation

Planned tech:
	•	Python (Scikit-learn)
	•	Flask API
	•	Laravel API consumption

⸻

🛠 Common Issues & Fixes

❌ Target class [gobus.admin] does not exist

✔ Fix:
	•	Check routes/web.php
	•	Ensure controller namespace exists
	•	Clear cache:

php artisan route:clear
php artisan cache:clear
php artisan config:clear


⸻

❌ Migration or DB Errors

✔ Fix:
	•	Check .env DB credentials
	•	Make sure MySQL is running

⸻

📁 Folder Structure Overview

app/
 ├── Http/Controllers
resources/views
 ├── admin
 │   └── admin-schedules.blade.php
routes/web.php
public/


⸻

🚀 Deployment Notes

Before deploying:

php artisan optimize
php artisan config:cache
php artisan route:cache

Set APP_DEBUG=false in production.

⸻

👨‍💻 Author

Developed by GoBus Team

⸻

📄 License

This project is for educational and academic use.

LIBRARY WE USE

. Backend (PHP / Laravel)
Found in composer.json

Core Dependencies:

laravel/framework (^12.0): The main web application framework.
guzzlehttp/guzzle (^7.10): A PHP HTTP client used for making requests (likely to communicate with your ML API).
bref/bref (^2.4): Tools for running PHP on serverless platforms (like AWS Lambda).
laravel/tinker (^2.10.1): Interactive REPL for exploring the application.
php (^8.2): The programming language version required.
Development & Testing Dependencies:

phpunit/phpunit (^11.5.3): Testing framework.
fakerphp/faker (^1.23): Generates fake data for seeding.
laravel/sail (^1.41): Docker interface for local development.
laravel/pint (^1.24): Code style fixer.
mockery/mockery (^1.6): Mock object framework for testing.
nunomaduro/collision (^8.6): Error handling and reporting.
laravel/pail (^1.2.2): Tailing application logs.
2. Frontend (JavaScript / CSS)
Found in package.json

Build Tools & Libraries:

tailwindcss (^4.0.0): A utility-first CSS framework for styling.
vite (^7.0.7): Modern frontend build tool and development server.
laravel-vite-plugin (^2.0.0): Integration between Laravel and Vite.
@tailwindcss/vite (^4.0.0): Vite plugin for Tailwind CSS.
axios (^1.11.0): Promise-based HTTP client for the browser (used for AJAX requests).
concurrently (^9.0.1): Utility to run multiple commands simultaneously (used in dev scripts).
3. Machine Learning API (Python)
Found in ml-api/requirements.txt

Web API Framework:

fastapi (==0.95.2): High-performance web framework for building the ML API.
uvicorn[standard] (==0.22.0): ASGI web server implementation for FastAPI.
python-multipart (==0.0.6): Required for form parsing in FastAPI.
Data Science & Machine Learning:

scikit-learn (==1.2.2): The machine learning library used for the Random Forest model.
pandas (==1.5.3): Data manipulation and analysis library.
numpy (==1.23.5): Fundamental package for scientific computing.
joblib (==1.2.0): Used for saving and loading the trained models (.pkl files).
Database:

sqlalchemy (==1.4.46): SQL toolkit and Object-Relational Mapper (ORM).
pymysql (==1.0.2): MySQL driver for Python (allowing the ML API to connect to your database).


SCREENSHOTS
<img width="3420" height="2224" alt="598102526_1586539855687872_3548381869389143553_n" src="https://github.com/user-attachments/assets/7a49ccd7-e8a4-41c5-9a37-40ef91e2768f" />
<img width="3644" height="2194" alt="597725191_844288208459212_2378977302344966233_n" src="https://github.com/user-attachments/assets/06cba2a5-ab60-497e-a9e8-29672ae977d3" />
<img width="3644" height="2194" alt="597419487_1615610639811830_591249092631831108_n" src="https://github.com/user-attachments/assets/fdf3ef14-49fd-4f40-9a63-552678785cf7" />

DEPLOYED SYSTEM

https://bus-laravel.free.nf/

VIDEO PRESENTATION

https://drive.google.com/file/d/1i9j74en7d2WqVHgFuerg0RdUAKebCJnM/view?usp=sharing

CANVA POWERPOINT

https://www.canva.com/design/DAG7dq7tz78/xG7RMcl_Amn9IfN6wQBRLw/edit?fbclid=IwY2xjawOrm3tleHRuA2FlbQIxMABicmlkETE2QUNIZktvMDdQZVA2OVR0c3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHkaRvD9EMQ7NhB9pU243w37-wVuo_NTVuRjtf3zWsa8RIR7GCusZ9oUJ1eF0_aem_rLVZwFO-IY_iuFQKiqjatA




