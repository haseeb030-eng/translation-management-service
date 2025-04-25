# Translation Management Service

A Laravel-based service for managing translations across multiple languages. This service provides a robust API for handling language translations, supporting multiple languages and translation tags.

## Features

- Multi-language support
- Translation management with tags
- RESTful API
- Docker support
- Database migrations and seeders
- API documentation with Swagger/OpenAPI
- CDN support with proper CORS configuration
- Optimized asset caching and compression

## Requirements

- PHP 8.0 or higher
- Composer
- MySQL 8.0
- Docker and Docker Compose (for Docker setup)

## Setup Instructions

### Using Docker

1. Clone the repository:
```bash
git clone <repository-url>
cd translation-management-service
```

2. Copy the environment file:
```bash
cp .env.example .env
```

3. Configure your .env file with appropriate values:
```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=your_password

# CDN Configuration (if using)
ASSET_URL=your_cdn_url
CORS_ALLOWED_ORIGINS=https://your-cdn-domain.com,https://your-app-domain.com
```

4. Start the Docker containers:
```bash
docker-compose up -d
```

5. Install dependencies and set up the application:
```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan l5-swagger:generate
```

The application will be available at http://localhost:80

### Manual Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd translation-management-service
```

2. Copy the environment file:
```bash
cp .env.example .env
```

3. Configure your .env file with your local database settings:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# CDN Configuration (if using)
ASSET_URL=your_cdn_url
CORS_ALLOWED_ORIGINS=https://your-cdn-domain.com,https://your-app-domain.com
```

4. Install dependencies:
```bash
composer install
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Run migrations and seeders:
```bash
php artisan migrate
php artisan db:seed
```

7. Generate API documentation:
```bash
php artisan l5-swagger:generate
```

8. Start the development server:
```bash
php artisan serve
```

The application will be available at http://localhost:8000

## Design Choices

### Database Structure
- **Languages**: Stores supported languages with code (e.g., 'en', 'es'), name, and active status
- **Translations**: Contains the actual translations with relationships to languages and tags
- **Tags**: Allows grouping and categorizing translations for better organization

### Architecture
- Built on Laravel 9+ following MVC pattern
- RESTful API design for easy integration
- Docker containerization for consistent development and deployment environments
- MySQL for robust relational data storage
- Swagger/OpenAPI documentation for API endpoints
- Nginx with optimized configuration for static assets and API documentation
- CDN-ready with proper CORS headers and cache configuration

### Security
- Laravel Sanctum for API authentication
- Input validation using Form Requests
- Database foreign key constraints
- Environment-based configuration
- Properly configured CORS headers

## API Documentation

The API documentation is available at `/api/documentation` when the application is running. It provides detailed information about all available endpoints, request/response formats, and authentication requirements.

### Accessing the Documentation
- Docker setup: http://localhost/api/documentation
- Manual setup: http://localhost:8000/api/documentation

## Performance Optimizations

- Gzip compression enabled for API responses and static assets
- Static asset caching configured in Nginx
- CDN-ready with proper cache headers
- Optimized Nginx configuration for serving API documentation

## Testing

Run the test suite with:
```bash
php artisan test
```

Or using Docker:
```bash
docker-compose exec app php artisan test
```

## Troubleshooting

### Common Issues

1. Permission Issues
```bash
docker-compose exec app chown -R www-data:www-data /var/www
```

2. Cache Issues
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
```

3. API Documentation Not Showing
```bash
docker-compose exec app php artisan l5-swagger:generate
```

4. Database Connection Issues
- Ensure the database container is running: `docker-compose ps`
- Check database credentials in .env file
- Wait a few seconds after starting containers for MySQL to initialize

### Container Management

- Restart all containers: `docker-compose restart`
- View logs: `docker-compose logs -f`
- Rebuild containers: `docker-compose up -d --build`
