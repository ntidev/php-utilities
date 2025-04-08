# NtiDev PHP Utilities

A collection of PHP utility tools for common development tasks.

## Installation

You can install the package via composer:

```bash
composer require ntidev/php-utilities
```

## Usage

### API Responses

The library provides a convenient way to handle JSON responses using Symfony's HTTP Foundation component.

```php
use ntidev\Utilities\Http\ApiResponse;

// Success response
return ApiResponse::success($data, 'Operation successful');

// Error response
return ApiResponse::error('Something went wrong', $errors);

// Not found response
return ApiResponse::notFound('User not found');

// Unauthorized response
return ApiResponse::unauthorized('Please login to continue');

// Forbidden response
return ApiResponse::forbidden('You do not have permission to access this resource');
```

Example response formats:

Success Response:
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // Your data here
    }
}
```

Error Response:
```json
{
    "success": false,
    "message": "Something went wrong",
    "errors": {
        // Error details here
    }
}
```

### Query Builder

The library provides a QueryBuilder utility to handle pagination, filtering, and sorting in your API requests.

```php
use ntidev\Utilities\Database\QueryBuilder;

// Get pagination parameters
$pagination = QueryBuilder::paginate($request->query->all());

// Get filter and sort parameters
$filterAndSort = QueryBuilder::filterAndSort(
    $request->query->all(),
    ['name', 'email', 'status'], // Allowed filter fields
    ['id', 'name', 'created_at'] // Allowed sort fields
);

// Example usage with a database query
$offset = $pagination['offset'];
$limit = $pagination['limit'];
$sortField = $filterAndSort['sort_field'];
$sortDirection = $filterAndSort['sort_direction'];
$filters = $filterAndSort['filters'];

// Build your query with these parameters
$query = "SELECT * FROM users";
if (!empty($filters)) {
    $query .= " WHERE " . implode(' AND ', array_map(function($field, $value) {
        return "$field = :$field";
    }, array_keys($filters), array_keys($filters)));
}
$query .= " ORDER BY $sortField $sortDirection LIMIT $offset, $limit";
```

Example request format:
```
GET /api/users?page=2&page_size=20&filters[name]=John&sort_field=created_at&sort_direction=desc
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 