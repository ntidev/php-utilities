# NtiDev PHP Utilities

A comprehensive collection of PHP utility tools for Symfony-based API development, providing standardized response handling, pagination management, and advanced query building capabilities.

## Installation

You can install the package via composer:

```bash
composer require ntidev/php-utilities
```

## Requirements

- PHP 8.1 or higher
- Symfony HTTP Foundation 7.0 or higher

## Features

- **Standardized API Responses**: Consistent JSON response structure across your application
- **Advanced Pagination**: Comprehensive pagination management with metadata
- **Query Building**: Advanced filtering and sorting for database queries
- **Doctrine Integration**: Seamless integration with Doctrine ORM
- **Error Handling**: Proper HTTP status codes and error management

## Usage

### API Responses (`ntidev\Utilities\Http\ApiResponse`)

The library provides a standardized way to handle JSON responses using Symfony's HTTP Foundation component.

```php
use ntidev\Utilities\Http\ApiResponse;

// Success response with data and pagination
return ApiResponse::success('Operation successful', $data, $pagination);

// Error response with additional errors
return ApiResponse::error('Something went wrong', $additionalErrors);

// Redirect response
return ApiResponse::redirect('Redirecting...', $redirectUrl, $data);
```

**Response Format:**

Success Response:
```json
{
    "hasError": false,
    "additionalErrors": [],
    "message": "Operation successful",
    "result": {
        "data": {
            // Your data here
        },
        "pagination": {
            // Pagination metadata here
        }
    },
    "redirect": ""
}
```

Error Response:
```json
{
    "hasError": true,
    "additionalErrors": [
        // Additional error details here
    ],
    "message": "Something went wrong",
    "result": null,
    "redirect": ""
}
```

### Pagination Service (`ntidev\Utilities\Pagination\PaginationService`)

Comprehensive pagination management with multiple helper methods for different use cases.

```php
use ntidev\Utilities\Pagination\PaginationService;

// Create paginated response from service method
return PaginationService::createPaginatedResponseFromServiceWithParams(
    $request,
    fn($params) => $this->service->getAllItems($params),
    'Items fetched successfully',
    $this->serializer
);

// Get pagination parameters from request
$paginationParams = PaginationService::getPaginationParams($request);

// Get Doctrine-specific pagination parameters (0-based offset)
$doctrineParams = PaginationService::getDoctrinePaginationParams($request);

// Simple array pagination
$paginatedItems = PaginationService::paginateArray($items, $page, $limit);

// Create paginated response manually
return PaginationService::createPaginatedResponse(
    $items,
    $totalRecords,
    $currentPage,
    $limit,
    'Items fetched successfully',
    $serializer
);
```

**Pagination Metadata:**
```json
{
    "totalRecords": 150,
    "page": 2,
    "limit": 20,
    "totalPages": 8,
    "hasNextPage": true,
    "hasPreviousPage": true,
    "nextPage": 3,
    "previousPage": 1,
    "firstPage": 1,
    "lastPage": 8
}
```

**Request Parameters:**
```
GET /api/items?page=2&limit=20&search=keyword&filters[status]=active&sort[field]=created_at&sort[direction]=desc
```

### Database Query Builder (`ntidev\Utilities\Database\QueryBuilder`)

Advanced query building with filtering, sorting, and pagination for Doctrine ORM.

```php
use ntidev\Utilities\Database\QueryBuilder;

// Get pagination parameters
$pagination = QueryBuilder::paginate($request);

// Apply filters and sorting to Doctrine query
QueryBuilder::filterAndSort(
    $allowedFields,  // Array of allowed field names
    $query,          // Doctrine QueryBuilder instance
    $filters,        // Filter array from request
    $sorts           // Sort array from request
);

// Handle pagination for Doctrine queries
$paginationData = QueryBuilder::handlePagination($query, $data);
```

**Supported Filter Operators:**

- **`equal`**: Exact match filtering
  ```php
  $filters = ['status' => ['data' => 'active', 'operator' => 'equal']];
  ```

- **`like`**: Pattern matching with wildcards
  ```php
  $filters = ['name' => ['data' => 'john', 'operator' => 'like']];
  ```

- **`or`**: OR condition filtering for multiple fields
  ```php
  $filters = ['search' => ['data' => 'john', 'operator' => 'or']];
  ```

- **`gt`**: Greater than comparison
  ```php
  $filters = ['age' => ['data' => 18, 'operator' => 'gt']];
  ```

- **`between`**: Date range filtering
  ```php
  $filters = ['created_at' => [
      'data' => ['first' => '2023-01-01', 'second' => '2023-12-31'],
      'operator' => 'between'
  ]];
  ```

**Example Usage with Doctrine:**

```php
use ntidev\Utilities\Database\QueryBuilder;

// In your repository or service
public function getAllUsers($request)
{
    $qb = $this->createQueryBuilder('u');
    
    // Get pagination parameters
    $pagination = QueryBuilder::paginate($request);
    
    // Apply filters and sorting
    QueryBuilder::filterAndSort(
        ['id', 'name', 'email', 'status', 'created_at'],
        $qb,
        $pagination['filters'],
        $pagination['sort']
    );
    
    // Handle pagination
    $paginationData = QueryBuilder::handlePagination($qb, $pagination);
    
    // Execute query
    $users = $qb->getQuery()->getResult();
    
    return [
        'data' => $users,
        'pagination' => $paginationData
    ];
}
```

## Integration Examples

### Controller Integration

```php
use ntidev\Utilities\Http\ApiResponse;
use ntidev\Utilities\Pagination\PaginationService;

class UserController extends AbstractController
{
    #[Route('/users', methods: ['GET'])]
    public function getUsers(Request $request): ApiResponse
    {
        return PaginationService::createPaginatedResponseFromServiceWithParams(
            $request,
            fn($params) => $this->userService->getAllUsers($params),
            'Users fetched successfully',
            $this->serializer
        );
    }
    
    #[Route('/users/{id}', methods: ['GET'])]
    public function getUser(int $id): ApiResponse
    {
        $user = $this->userService->getUserById($id);
        
        if (!$user) {
            return ApiResponse::error('User not found', [], ApiResponse::HTTP_NOT_FOUND);
        }
        
        return ApiResponse::success('User fetched successfully', $user);
    }
}
```

### Repository Integration

```php
use ntidev\Utilities\Database\QueryBuilder;

class UserRepository extends ServiceEntityRepository
{
    public function getAllUsers(array $params): array
    {
        $qb = $this->createQueryBuilder('u');
        
        $pagination = QueryBuilder::paginate($params);
        
        QueryBuilder::filterAndSort(
            ['id', 'name', 'email', 'status', 'created_at'],
            $qb,
            $pagination['filters'],
            $pagination['sort']
        );
        
        $paginationData = QueryBuilder::handlePagination($qb, $pagination);
        
        $users = $qb->getQuery()->getResult();
        
        return [
            'data' => $users,
            'pagination' => $paginationData
        ];
    }
}
```

## Configuration

### Default Settings

- **Default Page Size**: 10 items per page
- **Maximum Page Size**: 100 items per page
- **Default Page**: 1 (1-based pagination)

### Customization

You can customize pagination settings by modifying the constants in the respective classes:

```php
// In PaginationService
private const DEFAULT_PAGE_SIZE = 10;
private const MAX_PAGE_SIZE = 100;

// In QueryBuilder
private const DEFAULT_PAGE_SIZE = 10;
private const DEFAULT_PAGE = 1;
```

## Error Handling

The package provides consistent error handling with proper HTTP status codes:

```php
// Common HTTP status codes
ApiResponse::HTTP_OK = 200;
ApiResponse::HTTP_CREATED = 201;
ApiResponse::HTTP_NO_CONTENT = 204;
ApiResponse::HTTP_BAD_REQUEST = 400;
ApiResponse::HTTP_UNAUTHORIZED = 401;
ApiResponse::HTTP_FORBIDDEN = 403;
ApiResponse::HTTP_NOT_FOUND = 404;
ApiResponse::HTTP_INTERNAL_SERVER_ERROR = 500;
```

## Best Practices

1. **Consistent Response Format**: Always use `ApiResponse` for all API endpoints
2. **Pagination**: Use `PaginationService` for list endpoints to ensure consistent pagination
3. **Filtering**: Define allowed filter fields to prevent security issues
4. **Error Handling**: Provide meaningful error messages and appropriate HTTP status codes
5. **Doctrine Integration**: Use `QueryBuilder` for complex database queries with filtering and sorting

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Authors

- **Angel Bencosme** - *Initial work* - [abencosme@syneteksolutions.com]
- **Felix Valerio** - *Contributor* - [fvalerio@syneteksolutions.com]

## Support

For support and questions, please create an issue in the repository or contact the development team. 
