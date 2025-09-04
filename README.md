# SkyCamp Backend - Professional PHP MVC Architecture

## 🎓 University Curriculum Implementation

This backend implementation follows the PHP concepts learned in your university curriculum:

### **Lecture 1 - Introduction to Server-side Scripting**

- ✅ **Server-side Scripting**: Complete backend API with PHP
- ✅ **PHP Syntax**: Professional coding standards and best practices
- ✅ **Variables & Data Types**: Proper type handling and validation
- ✅ **Operators & Comments**: Comprehensive documentation
- ✅ **Control Structures**: Conditional logic and loops throughout
- ✅ **Arrays**: Extensive array manipulation for data handling
- ✅ **Built-in Functions**: String and array functions for data processing

### **Lecture 2 - My First Web Application**

- ✅ **HTML Forms**: Complete form processing system
- ✅ **GET and POST Methods**: RESTful API with proper HTTP methods
- ✅ **Superglobal Variables**: $\_GET, $\_POST, $\_SESSION, $\_FILES handling
- ✅ **Form Data Processing**: Comprehensive validation and sanitization
- ✅ **Query Strings**: URL parameter handling
- ✅ **Dynamic Content**: Database-driven content generation
- ✅ **File Handling**: Secure file upload system

### **Lecture 3 - Object-Oriented PHP**

- ✅ **OOP Concepts**: Complete OOP implementation
- ✅ **Encapsulation**: Private/protected properties with getters/setters
- ✅ **Inheritance**: BaseModel and BaseRegistrationController hierarchy
- ✅ **Polymorphism**: Interface implementations and method overriding
- ✅ **Classes and Objects**: Models, Controllers, Services architecture
- ✅ **Access Modifiers**: Proper use of public, private, protected
- ✅ **Interfaces**: ModelInterface and ControllerInterface contracts
- ✅ **Abstract Classes**: BaseModel and BaseRegistrationController
- ✅ **Namespaces**: Organized code structure

### **Lecture 4 - Working with Databases**

- ✅ **Database Connections**: Professional PDO implementation
- ✅ **PDO - PHP Data Objects**: Complete PDO usage with prepared statements
- ✅ **ResultSet Handling**: Multiple fetch styles and error handling
- ✅ **Prepared Statements**: SQL injection prevention
- ✅ **Database Errors**: Comprehensive try-catch error handling
- ✅ **Transactions**: Support for database transactions

### **Lecture 5 - Personalizing Web Content**

- ✅ **User Authentication**: Complete login/logout system
- ✅ **Session Management**: Secure session handling with SessionManager
- ✅ **Cookies**: Session cookie security configuration
- ✅ **Dynamic Content**: Role-based content generation

### **Lecture 6 - Web Services**

- ✅ **RESTful API**: Professional REST API implementation
- ✅ **HTTP Methods**: GET, POST, PUT, DELETE support
- ✅ **API Endpoints**: Organized routing system
- ✅ **Data Handling**: JSON request/response handling
- ✅ **Security**: Input validation and authentication

## 🏗️ Architecture Overview

```
skycamp-backend/
├── 📁 config/                  # Configuration management
│   ├── Config.php              # Centralized configuration
│   └── Database.php            # Database connection with PDO
├── 📁 controllers/             # Business Logic (MVC - Controller)
│   ├── BaseRegistrationController.php
│   ├── CustomerRegistrationController.php
│   ├── RenterRegistrationController.php
│   ├── GuideRegistrationController.php
│   ├── LoginController.php
│   └── OTPPasswordResetController.php
├── 📁 core/                    # Core framework files
│   └── Router.php              # RESTful routing system
├── 📁 database/                # Database files
│   └── skycamp.sql             # Database schema
├── 📁 interfaces/              # OOP Contracts
│   └── ModelInterface.php      # Model contract
├── 📁 models/                  # Data Models (MVC - Model)
│   ├── BaseModel.php           # Abstract base model
│   ├── User.php                # User model
│   ├── Customer.php            # Customer model
│   ├── Renter.php              # Renter model
│   └── Guide.php               # Guide model
├── 📁 services/                # Business Logic Layer
│   ├── UserService.php         # User business logic
│   ├── ValidationService.php   # Input validation logic
│   └── EmailService.php        # Email service with PHPMailer
├── 📁 utils/                   # Utility Classes
│   ├── ErrorHandler.php        # Error handling & logging
│   ├── SessionManager.php      # Session management
│   └── FileUpload.php          # File upload handling
├── 📁 uploads/                 # File uploads
│   └── profile_pictures/       # Profile picture uploads
├── 📁 phpmailer/               # PHPMailer library
├── index.php                   # Main API entry point
├── .htaccess                   # Security configuration
├── sample_data.sql             # Sample data for testing
└── README.md                   # Documentation
```

## 🎯 MVC Architecture Implementation

### **Model Layer** (Data & Database)

- **Purpose**: Handle all database operations and data validation
- **Components**: BaseModel, User, Customer, Renter, Guide models
- **Features**:
  - PDO prepared statements for security
  - CRUD operations with interfaces
  - Data validation and sanitization
  - UUID generation for primary keys

### **View Layer** (Frontend)

- **Purpose**: User interface and presentation
- **Implementation**: React.js frontend (separate project)
- **API Communication**: RESTful JSON API

### **Controller Layer** (Business Logic)

- **Purpose**: Handle user input and coordinate between Model and View
- **Components**: Registration controllers, Login controller
- **Features**:
  - Request handling and routing
  - Business logic coordination
  - Response formatting
  - Error handling

## 🔐 Security Features

### **Input Validation & Sanitization**

```php
// Example from ValidationService.php
private function validateEmail($email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->errors['email'] = 'Invalid email format';
    }
    if (preg_match('/[<>"\']/', $email)) {
        $this->errors['email'] = 'Email contains invalid characters';
    }
}
```

### **SQL Injection Prevention**

```php
// Example from BaseModel.php
$query = "SELECT * FROM {$this->table_name} WHERE {$this->primary_key} = :id LIMIT 1";
$stmt = $this->conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
```

### **Session Security**

```php
// From SessionManager.php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
```

## 🚀 Getting Started

### **1. Setup Database**

```sql
-- Import the database schema
mysql -u root -p skycamp < database/skycamp.sql
```

### **2. Configure Environment**

```php
// Update config/Config.php or use environment variables
'database' => [
    'host' => 'localhost',
    'name' => 'skycamp',
    'username' => 'root',
    'password' => ''
]
```

### **3. Start Development Server**

```bash
# Navigate to backend directory
cd skycamp-backend

# Start PHP development server
php -S localhost:8000 -t . bootstrap.php
```

### **4. API Endpoints**

#### **Authentication**

- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout

#### **Registration**

- `POST /api/auth/register/customer` - Customer registration
- `POST /api/auth/register/guide` - Guide registration
- `POST /api/auth/register/renter` - Renter registration

#### **Password Reset**

- `POST /api/auth/password/request-reset` - Request password reset OTP
- `POST /api/auth/password/verify-otp` - Verify OTP code
- `POST /api/auth/password/reset` - Reset password with token

#### **Utility**

- `GET /api/health` - API health check
- `GET /api/test` - API test endpoint

## 📚 Code Examples

### **Creating a New Model**

```php
<?php
require_once __DIR__ . '/BaseModel.php';

class YourModel extends BaseModel implements ModelInterface
{
    protected $table_name = 'your_table';
    protected $primary_key = 'id';
    protected $fillable = ['field1', 'field2', 'field3'];

    public function validate($data)
    {
        // Your validation logic
        return parent::validate($data);
    }
}
```

### **Creating a New Controller**

```php
<?php
require_once __DIR__ . '/../interfaces/ControllerInterface.php';

class YourController implements ControllerInterface
{
    public function index($params = [])
    {
        // List items logic
    }

    public function show($params = [])
    {
        // Show single item logic
    }

    // Implement other required methods...
}
```

### **Adding New Routes**

```php
// In api/index.php
$router->get('/your-endpoint', 'YourController@index');
$router->post('/your-endpoint', 'YourController@store');
```

## 🔧 Development Features

### **Error Handling & Logging**

- Comprehensive error logging system
- Development vs production error display
- Automatic log rotation
- Database error tracking

### **Configuration Management**

- Environment-based configuration
- Centralized settings management
- Security configuration
- Upload and validation rules

### **Validation System**

- Centralized validation service
- Role-specific validation rules
- Sri Lankan specific validations (NIC, phone numbers)
- File upload validation

## 📈 Performance & Best Practices

### **Database Optimization**

- Connection pooling with PDO
- Prepared statements for all queries
- Proper indexing in database schema
- Transaction support

### **Security Best Practices**

- Password hashing with PHP's password_hash()
- CSRF protection ready
- XSS prevention with input sanitization
- SQL injection prevention
- Secure session management

### **Code Organization**

- PSR-4 autoloading ready structure
- Separation of concerns
- Dependency injection ready
- Interface-driven development

## 🎓 Learning Outcomes

This implementation demonstrates mastery of:

1. **Server-side Programming**: Complete PHP backend development
2. **OOP Principles**: Inheritance, encapsulation, polymorphism, abstraction
3. **Database Integration**: Secure PDO implementation with prepared statements
4. **Security Practices**: Input validation, session management, SQL injection prevention
5. **MVC Architecture**: Clear separation of model, view, and controller responsibilities
6. **API Development**: RESTful service creation with proper HTTP methods
7. **Error Handling**: Comprehensive error management and logging
8. **Configuration Management**: Environment-based configuration systems

## 🔄 Future Enhancements

- [ ] JWT authentication system
- [ ] Email notification system
- [ ] File caching system
- [ ] API rate limiting
- [ ] Advanced logging with different levels
- [ ] Unit testing implementation
- [ ] API documentation with Swagger

---

**Built with ❤️ following university PHP curriculum and professional best practices**
