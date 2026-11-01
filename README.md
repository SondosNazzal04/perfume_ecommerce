# Velora - Luxury Perfume E-Commerce Platform
Velora is a fully custom, premium e-commerce web application designed for a luxury perfume brand. It features an elegant, dark-themed user interface backed by a robust, object-oriented PHP architecture. The platform supports a complete shopping experience, from browsing signature collections to secure checkout, complete with a RESTful API and an administrative dashboard.   

## This Project was done by:
| Name | Role | Github |
| ---- | ---- | ------ |
| Sondos Nazzal | Scrum Master | [@SondosNazzal04](https://github.com/SondosNazzal04) |
| Ahmad AlHawwari | Product Owner | [@Ahmad-alhwari0](https://github.com/Ahmad-alhwari0) |
| Ahmad Meshal | Developer | [@ahmadmeshaal](https://github.com/ahmadmeshaal) |
| Naser Bader | Developer | [@Naser-devops] (https://github.com/Naser-devops) |
| Rahaf AlKayyali | Developer | [@alkayyalirahaf](https://github.com/alkayyalirahaf) |

## Relative Links:
| | |
|---|---|
| **Github** | https://github.com/SondosNazzal04/perfume_ecommerce.git |
| **Trello** | https://trello.com/b/RehN2awk/e-commerce-project |
| **Figma** | https:// |
| **Live Url**| https:// |

## Features
### User Experience (Frontend)
- Luxury UI/UX: A sophisticated, dark-themed responsive design highlighting product imagery and brand identity ("More Than a Fragrance, It's a Presence").
- Product Discovery: Browse by collections, view fragrance notes, and explore the brand's story.
- Shopping Features: Integrated shopping cart, wishlist functionality, and seamless checkout process.
- Account Management: User profiles, order history tracking, and personalized dashboards.
- Product Reviews: Customers can leave and read product reviews.
### Backend & Architecture
- Custom PHP Framework: Built with an Object-Oriented PHP architecture using dedicated classes for core entities (Product, Cart, User, Order, etc.).
- RESTful API: Dedicated /api/ endpoints for dynamic frontend data fetching (Cart, Products, Coupons, Wishlist, Reviews).
- Advanced Authentication:
  - Standard email/password registration with OTP email verification.
  - Google OAuth integration (google-login.php, GoogleAuth.php).
  - Secure password reset workflows.
- Order & Inventory Management:
  - Admin dashboard for managing orders and the product catalog.
  - Support for product variants (e.g., sizes like 50ml, 100ml).
  - Coupon code and discount system.
- Security & Optimization: Integrated Rate Limiting (RateLimiter.php) to prevent API abuse, and secure mail services for transactional emails.

## Tech Stack
- Frontend: HTML5, CSS3, Vanilla JavaScript (`assets/js/api.js`, `cart.js`, `main.js`).
- Backend: PHP 8+ (Object-Oriented).
- Database: MySQL / MariaDB (Relational schema).
- Package Management: Composer (for managing dependencies like PHPMailer or Google API Client).
- API Testing: Postman (`postman/perfume-api.json` included in repository).

## Project Structure
```
.
├── actions/                # Form submission handlers (login, register, reset-password, google-auth)
├── admin/              # Administrative interface and order management
├── api/                # RESTful API endpoints for dynamic data loading
├── assets/             # Static assets (CSS, JS, product images, banners)
├── classes/            # Core business logic (Cart.php, Product.php, User.php, MailService.php, etc.)
├── config/             # Database connection and environment configurations
├── core/               # Base application classes (Response handling)
├── database/           # Database setup files (schema.sql)
├── includes/           # Reusable components (session management, init scripts)
├── postman/            # API collections for testing
└── uploads/            # User-uploaded content and dynamic product imagery
```
## Installation & Setup
1. Clone the Repository
 ```Bash
   git clone https://github.com/yourusername/perfume_ecommerce.git
   cd velora-perfumes
 ```
2. Install Dependencies
Ensure Composer is installed on your system, then run
```Bash
composer install
```
3. Database Configuration
  - Create a new MySQL database named perfume_ecommerce
  - Import the provided database schema:
    ```Bash
    mysql -u root -p perfume_ecommerce < database/schema.sql
    ```
Duplicate or update `config/Database.php` with your local database credentials.
4. Environment Setup
  - Configure your SMTP settings in the MailService or environment configuration to enable OTP and transactional emails.
  - Add your Google OAuth Client ID and Secret to enable GoogleAuth functionality.
5. Run the Application (via XAMPP)
- Move the project: Move your entire project folder into XAMPP's htdocs directory.
  - Windows: C:\xampp\htdocs\
  - Mac: /Applications/XAMPP/xamppfiles/htdocs/
  - Linux: /opt/lampp/htdocs/
- Start the servers: Open the XAMPP Control Panel and start both the Apache and MySQL modules.
- Access the site: Open your web browser and navigate to:

```
http://localhost/perfume_ecommerce
```
(Note: Replace `perfume_ecommerce` with the exact name of your project folder inside htdocs.)

## API Documentation
A complete Postman collection is included in the repository. Import `postman/perfume-api.json` into Postman to test and view all available API endpoints, payloads, and response structures. 
