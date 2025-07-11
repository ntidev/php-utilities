<?php

namespace ntidev\Utilities\Pagination;

use ntidev\Utilities\Http\ApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class PaginationService
{
    private const DEFAULT_PAGE_SIZE = 10;
    private const MAX_PAGE_SIZE = 100;

    /**
     * Create a paginated response with standardized metadata
     *
     * @param array $items
     * @param int $totalRecords
     * @param int $currentPage
     * @param int $limit
     * @param string $message
     * @param SerializerInterface $serializer
     * @param array $serializationGroups
     * @return ApiResponse
     */
    public static function createPaginatedResponse(
        array $items,
        int $totalRecords,
        int $currentPage,
        int $limit,
        string $message,
        SerializerInterface $serializer,
        array $serializationGroups = ['basic']
    ): ApiResponse {
        $data = json_decode(
            $serializer->serialize($items, 'json', ['groups' => $serializationGroups]), 
            true
        );
        
        $totalPages = (int) ceil($totalRecords / $limit);

        return ApiResponse::success($message, $data, [
            'totalRecords' => $totalRecords,
            'page' => $currentPage ?? 1,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'hasNextPage' => $currentPage < $totalPages,
            'hasPreviousPage' => $currentPage > 1,
            'nextPage' => $currentPage < $totalPages ? $currentPage + 1 : null,
            'previousPage' => $currentPage > 1 ? $currentPage - 1 : null,
            'firstPage' => 1,
            'lastPage' => $totalPages
            
        ], ApiResponse::HTTP_OK);
    }

    /**
     * Get pagination parameters from request with validation
     * Returns 1-based page numbers for API consistency
     *
     * @param Request $request
     * @return array
     */
    public static function getPaginationParams(Request $request): array
    {
        $search = $request->get('search') ?? '';
        $limit = $request->get('limit') ?? 10;
        $page = $request->get('page') ?? 1;
        $sortBy = $request->get('sort') ?? [];
        $filters = $request->get('filters') ?? [];
        $start = 0;

        // Page
        if ($page > 0) {
            $start = $page - 1;
        }

        if ($start > 0) {
            $start = $start * $limit;
        }
        
        return [
            'start' => $start,
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
            'sort' => $sortBy,
            'filters' => $filters,
        ];
    }

    /**
     * Get pagination parameters for Doctrine queries (0-based offset)
     * Use this when working with Doctrine's setFirstResult method
     *
     * @param Request $request
     * @return array
     */
    public static function getDoctrinePaginationParams(Request $request): array
    {
        $paginationParams = self::getPaginationParams($request);
        
        return [
            'page' => $paginationParams['start'],
            'limit' => $paginationParams['limit'],
            'offset' => ($paginationParams['start'] - 1) * $paginationParams['limit']
        ];
    }

    /**
     * Simple pagination helper for arrays
     *
     * @param array $items
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function paginateArray(array $items, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        return array_slice($items, $offset, $limit);
    }

    /**
     * Helper method to create a paginated response from a service method
     * 
     * @param Request $request
     * @param callable $serviceMethod
     * @param string $message
     * @param SerializerInterface $serializer
     * @param array $serializationGroups
     * @return ApiResponse
     */
    public static function createPaginatedResponseFromService(
        Request $request,
        callable $serviceMethod,
        string $message,
        SerializerInterface $serializer,
        array $serializationGroups = ['basic']
    ): ApiResponse {
        $paginationParams = self::getPaginationParams($request);
        $result = $serviceMethod();
        $allItems = $result['data'];
        $pagination = $result['pagination'];
        
        if (empty($allItems)) {
            return ApiResponse::error('No items found', [], ApiResponse::HTTP_NOT_FOUND);
        }
        
        $totalRecords = $pagination['totalRecords'];
        $paginatedItems = self::paginateArray($allItems, $pagination['page'], $pagination['limit']);
        
        return self::createPaginatedResponse(
            $paginatedItems,
            $totalRecords,
            $paginationParams['page'],
            $paginationParams['limit'],
            $message,
            $serializer,
            $serializationGroups
        );
    }

    /**
     * Helper method to create a paginated response from a service method that accepts pagination params
     * Use this when your service method can handle pagination internally
     * 
     * @param Request $request
     * @param callable $serviceMethod
     * @param string $message
     * @param SerializerInterface $serializer
     * @param array $serializationGroups
     * @return ApiResponse
     */
    public static function createPaginatedResponseFromServiceWithParams(
        Request $request,
        callable $serviceMethod,
        string $message,
        SerializerInterface $serializer,
        array $serializationGroups = ['basic']
    ): ApiResponse {
        $paginationParams = self::getPaginationParams($request);
        $result = $serviceMethod($paginationParams);
        $allItems = $result['data'];
        $pagination = $result['pagination'];
        
        // Handle different return formats from service
        if (is_array($result) && isset($result['data']) && isset($result['pagination'])) {
                
            // Service returns paginated result with metadata
            return self::createPaginatedResponse(
                $allItems,
                $pagination['totalRecords'],
                $paginationParams['page'],
                $paginationParams['limit'],
                $message,
                $serializer,
                $serializationGroups
            );
        } else {
            // Service returns all items (fallback to array pagination)
            if (empty($result)) {
                return ApiResponse::error('No items found', [], ApiResponse::HTTP_NOT_FOUND);
            }
            
            $totalRecords = $pagination['totalRecords'];
            $paginatedItems = self::paginateArray($allItems, $paginationParams['page'], $paginationParams['limit']);
            
            return self::createPaginatedResponse(
                $paginatedItems,
                $totalRecords,
                $paginationParams['page'],
                $paginationParams['limit'],
                $message,
                $serializer,
                $serializationGroups
            );
        }
    }

    /**
     * Get default page size
     *
     * @return int
     */
    public static function getDefaultPageSize(): int
    {
        return self::DEFAULT_PAGE_SIZE;
    }

    /**
     * Get maximum page size
     *
     * @return int
     */
    public static function getMaxPageSize(): int
    {
        return self::MAX_PAGE_SIZE;
    }
} 