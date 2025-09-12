# SkyCamp Backend

A simple, lightweight PHP backend for the SkyCamp camping platform built with plain PHP 8+, PDO (MySQL), and session-based authentication.

## Features

- **Simple Architecture**: Plain PHP with OOP, no frameworks
- **Authentication**: Session-based auth with password hashing (Argon2id/Bcrypt)
- **File Uploads**: Safe multipart form handling with validation
- **CORS Support**: Cross-origin resource sharing for React frontend
- **Database**: PDO with MySQL, matches existing skycamp.sql schema
- **Validation**: Comprehensive input validation and sanitization
- **Logging**: Simple file-based logging system

## Project Structure

```
skycamp-backend/
├─ public/
│  ├─ index.php                # Front controller + router
│  ├─ .htaccess                # URL rewriting and security
├─ app/
│  ├─ Config/
│  │  ├─ env.example.php       # Environment configuration template
│  │  ├─ env.php              # Your actual configuration (copy from example)
│  │  └─ database.php          # Database connection factory
│  ├─ Core/
│  │  ├─ Router.php            # Simple HTTP router with wildcard support
│  │  ├─ Request.php           # HTTP request wrapper (multipart support)
│  │  ├─ Response.php          # HTTP response helpers
│  │  ├─ Controller.php        # Base controller class
│  │  ├─ Session.php           # Session management
│  │  ├─ Validator.php         # Input validation (SL patterns)
│  │  ├─ Uploader.php          # File upload handling
│  │  ├─ Password.php          # Password hashing utilities
│  │  └─ Uuid.php              # UUID generation
│  ├─ Models/                  # Data models (matches existing DB schema)
│  │  ├─ User.php
│  │  ├─ Customer.php
│  │  ├─ Renter.php
│  │  └─ Guide.php
│  ├─ Repositories/            # Database access layer
│  │  ├─ UserRepository.php
│  │  ├─ CustomerRepository.php
│  │  ├─ RenterRepository.php
│  │  └─ GuideRepository.php
│  ├─ Services/                # Business logic
│  │  ├─ AuthService.php       # Authentication service
│  │  └─ FileService.php       # File handling service
│  ├─ Controllers/
│  │  └─ AuthController.php    # Authentication endpoints
│  └─ Middlewares/
│     └─ Cors.php              # CORS handling
├─ storage/
│  └─ uploads/                 # File storage (outside web root)
│     └─ users/
├─ logs/
└─ README.md
```

## Setup Instructions

### 1. Prerequisites

- PHP 8.0 or higher
- MySQL 5.7 or higher (or MariaDB)
- Web server (Apache/Nginx) or PHP built-in server

### 2. Installation

1. **Configure environment**

   ```bash
   # The env.php file is already created from env.example.php
   # Edit the configuration file if needed
   nano app/Config/env.php
   ```

2. **Update configuration**
   Edit `app/Config/env.php` with your settings:

   ```php
   return [
       'database' => [
           'host' => 'localhost',
           'port' => 3306,
           'dbname' => 'skycamp',  // Your existing database
           'username' => 'root',
           'password' => '',
           // ... other settings
       ],
       'cors' => [
           'origin' => 'http://localhost:5173', // Your React frontend URL
           'methods' => ['GET', 'POST', 'OPTIONS'],
           'headers' => ['Content-Type', 'Authorization'],
           'credentials' => true
       ],
       // ... other configurations
   ];
   ```

3. **Database setup**

   - Use your existing `skycamp.sql` database
   - Ensure the database exists and contains all required tables
   - The backend matches the existing schema exactly

4. **Run with PHP built-in server**

   ```bash
   cd skycamp-backend
   php -S localhost:8000 -t public
   ```

5. **Or set up with Apache/Nginx**
   - Point document root to `skycamp-backend/public/`
   - Ensure mod_rewrite is enabled (Apache)

## API Endpoints

### Authentication

- `POST /api/auth/register` - Register new user (multipart/form-data)
- `POST /api/auth/login` - Login user (JSON)
- `POST /api/auth/logout` - Logout user
- `GET /api/auth/me` - Get current user
- `OPTIONS /api/*` - CORS preflight (handled automatically)

### Request/Response Format

**Registration Request (multipart/form-data):**

```
email: user@example.com
password: Password123
confirmPassword: Password123
firstName: John
lastName: Doe
dob: 1990-01-01
phoneNumber: 0712345678
homeAddress: 123 Main Street, Colombo
nicNumber: 901234567V
gender: Male
userRole: customer
district: Colombo (for renter/guide)
campingDestinations: Horton Plains, Yala (for renter/guide)
stargazingSpots: Horton Plains (for renter/guide)
description: Experienced guide (for guide)
pricePerDay: 5000.00 (for guide)
currency: LKR (for guide)
languages: English, Sinhala (for guide)
profilePicture: [file]
nicFrontImage: [file]
nicBackImage: [file]
```

**Login Request (JSON):**

```json
{
  "email": "user@example.com",
  "password": "Password123"
}
```

**Success Response:**

```json
{
  "success": true,
  "message": "Registration successful",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "role": "Customer",
    "is_active": true,
    "created_at": "2025-01-08 10:41:00"
  },
  "data": {
    "redirect_url": "/dashboard/customer/overview"
  }
}
```

**Error Response:**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": "Email is required",
    "password": "Password must contain at least one uppercase letter",
    "phoneNumber": "Invalid phone number format"
  }
}
```

**Me Response:**

```json
{
  "success": true,
  "message": "User retrieved successfully",
  "data": {
    "authenticated": true,
    "user": {
      "id": "uuid",
      "email": "user@example.com",
      "role": "Customer",
      "is_active": true,
      "created_at": "2025-01-08 10:41:00"
    }
  }
}
```

## Validation Rules

### Common Fields

- **email**: Valid email format, must be unique
- **password**: Min 8 chars, must include uppercase, lowercase, number
- **confirmPassword**: Must match password
- **firstName, lastName**: Required, non-empty
- **dob**: Date format (YYYY-MM-DD)
- **phoneNumber**: SL format `^0[1-9][0-9]{8}$`
- **homeAddress**: Required
- **nicNumber**: SL format `^[0-9]{9}[vVxX]$|^[0-9]{12}$`, must be unique
- **gender**: Male, Female, or Other
- **userRole**: customer, renter, or guide

### Role-Specific Fields

- **Renter**: district (required), campingDestinations, stargazingSpots, latitude, longitude
- **Guide**: district (required), description, specialNote, pricePerDay (positive decimal), currency, languages, campingDestinations, stargazingSpots

### File Uploads

- **Types**: image/jpeg, image/png, image/webp only
- **Size**: Max 5MB each
- **Storage**: `storage/uploads/users/{user_uuid}/`
- **Names**: profile.{ext}, nic_front.{ext}, nic_back.{ext}

## Testing the API

### 1. Test Registration (Customer)

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -F "email=customer@test.com" \
  -F "password=Password123" \
  -F "confirmPassword=Password123" \
  -F "firstName=John" \
  -F "lastName=Doe" \
  -F "dob=1990-01-01" \
  -F "phoneNumber=0712345678" \
  -F "homeAddress=123 Main Street" \
  -F "nicNumber=901234567V" \
  -F "gender=Male" \
  -F "userRole=customer" \
  -H "Cookie: SKYCAMP_SESSION=session_id"
```

### 2. Test Registration (Renter)

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -F "email=renter@test.com" \
  -F "password=Password123" \
  -F "confirmPassword=Password123" \
  -F "firstName=Jane" \
  -F "lastName=Smith" \
  -F "dob=1985-05-15" \
  -F "phoneNumber=0776543210" \
  -F "homeAddress=456 Business Ave" \
  -F "nicNumber=851234567V" \
  -F "gender=Female" \
  -F "userRole=renter" \
  -F "district=Colombo" \
  -F "campingDestinations=Horton Plains, Yala" \
  -F "stargazingSpots=Horton Plains" \
  -F "latitude=6.9271" \
  -F "longitude=79.8612"
```

### 3. Test Registration (Guide)

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -F "email=guide@test.com" \
  -F "password=Password123" \
  -F "confirmPassword=Password123" \
  -F "firstName=Mike" \
  -F "lastName=Johnson" \
  -F "dob=1988-12-20" \
  -F "phoneNumber=0755555555" \
  -F "homeAddress=789 Guide Road" \
  -F "nicNumber=881234567V" \
  -F "gender=Male" \
  -F "userRole=guide" \
  -F "district=Kandy" \
  -F "description=Experienced camping guide" \
  -F "pricePerDay=5000.00" \
  -F "currency=LKR" \
  -F "languages=English, Sinhala" \
  -F "campingDestinations=Horton Plains, Knuckles" \
  -F "stargazingSpots=Horton Plains"
```

### 4. Test Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"customer@test.com","password":"Password123"}' \
  -H "Cookie: SKYCAMP_SESSION=session_id"
```

### 5. Test Me Endpoint

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Cookie: SKYCAMP_SESSION=session_id"
```

### 6. Test Logout

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Cookie: SKYCAMP_SESSION=session_id"
```

## Security Features

- **Password Hashing**: Argon2id (preferred) or Bcrypt fallback
- **SQL Injection Protection**: PDO prepared statements only
- **File Upload Security**: MIME type verification, size limits, secure storage
- **CORS Configuration**: Configurable cross-origin policies
- **Session Security**: HttpOnly, SameSite=Lax cookies
- **Input Validation**: Comprehensive server-side validation
- **Database Transactions**: Atomic multi-table operations

## Troubleshooting

### Common Issues

1. **Database Connection Failed**

   - Check database credentials in `app/Config/env.php`
   - Ensure MySQL service is running
   - Verify `skycamp` database exists

2. **CORS Issues**

   - Update `origin` setting in `app/Config/env.php`
   - Ensure frontend URL matches configured origin
   - Check browser developer tools for CORS errors

3. **File Upload Issues**

   - Check `storage/uploads/` directory permissions
   - Verify PHP upload settings (`upload_max_filesize`, `post_max_size`)
   - Check file type restrictions

4. **Session Issues**
   - Check session configuration in `app/Config/env.php`
   - Verify session directory permissions
   - Ensure cookies are enabled in browser

### Debug Mode

Enable debug mode in `app/Config/env.php`:

```php
'app' => [
    'debug' => true,
    // ...
]
```

## Acceptance Tests

After setup, verify these work:

1. ✅ Register as Customer → receive 201 and redirect_url
2. ✅ Register as Renter → receive 201 and redirect_url
3. ✅ Register as Guide → receive 201 and redirect_url
4. ✅ Confirm DB rows: one in users, one in role table
5. ✅ Password hash is not plain text
6. ✅ Uploaded files exist in storage/uploads/users/{user_uuid}/
7. ✅ GET /api/auth/me returns correct user after registration/login
8. ✅ Re-register with same email returns 409
9. ✅ Login with correct credentials works
10. ✅ Login with wrong credentials returns 401
11. ✅ Logout destroys session
12. ✅ CORS preflight returns 204

## License

This project is for educational purposes as part of a first-year university project.
