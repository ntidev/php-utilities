<?php

namespace ntidev\Utilities\Database;

use DateTimeImmutable;

class QueryBuilder
{
    /**
     * Default page size for pagination
     */
    private const DEFAULT_PAGE_SIZE = 10;

    /**
     * Default page number for pagination
     */
    private const DEFAULT_PAGE = 1;

    /**
     * Apply pagination to a query
     *
     * @param array $query The query array from request
     * @param int $defaultPageSize Default page size if not specified in query
     * @param int $maxPageSize Maximum allowed page size
     * @return array Pagination parameters
     */
    public static function paginate($request, int $defaultPageSize = self::DEFAULT_PAGE_SIZE): array
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

    public static function filterAndSort(
        array $fields,
        object $query,
        array $filters = [],
        array $sorts = []
    ): void {
        
        // Filtering Ppocess
        foreach ($filters as $field => $search) {
            if ('' == $field || '' == $search) {
                continue;
            }
            
            // validate that filter exists
            $fieldExists = array_search($field, $fields);
            if(!$fieldExists){
                continue;
            }
            $data = $search['data'];
            $opeator = $search['operator'];
            
            if($opeator === 'equal'){
                $fieldToBeFiltered = array_search($field, $fields);
                if($data === 'true' || $data === 'false'){
                    $query->andWhere($query->expr()->eq($fieldToBeFiltered, $data));
                } else {
                    $query->andWhere($query->expr()->eq($fieldToBeFiltered, $query->expr()->literal($data)));
                }
                
            } else if($opeator === 'like'){
                $fieldToBeFiltered = array_search($field, $fields);
                $valor = explode(',', $fieldToBeFiltered);
                if(is_array($valor) && count($valor) > 1 && is_array($data)){
                    $parts = explode(' ', $data['data']);
                    foreach($valor as $value){
                        foreach($parts as $value2){
                            $ors2[] = $query->expr()->orx(trim($value).' LIKE '.$query->expr()->literal('%'.trim($value2).'%'));
                        }
                    }
                    $query->andWhere(join(' OR ', $ors2));
                } else {
                    $query->andWhere($query->expr()->like($fieldToBeFiltered, $query->expr()->literal('%'.$data.'%')));
                }
            } else if($opeator === 'or'){
                $fieldToBeFiltered = array_search($field, $fields);
                $values = explode(",", $fieldToBeFiltered);
                foreach($values as $value){
                    $ors[] = $query->expr()->orx(trim($value).' LIKE '.$query->expr()->literal('%'.$data['data'].'%'));
                }
                $query->andWhere(join(' OR ', $ors));
            // gt = greater than
            } else if($opeator === 'gt'){
                $fieldToBeFiltered = array_search($field, $fields);
                $valor = explode('.', $fieldToBeFiltered);
                $query->setParameter($valor[1], $data); 
                $query->andWhere($fieldToBeFiltered.' > :'.$valor[1]);
                
            } else if($opeator === 'between'){
                $fieldToBeFiltered = array_search($field, $fields);
                $valor = explode('.', $fieldToBeFiltered);
                $date1 = new DateTimeImmutable($data['first']);
                $date2 = new DateTimeImmutable($data['second']);
                $query->setParameter($valor[1], $date1->format('Y-m-d H:i:s'));
                $query->setParameter($valor[1].'2', $date2->format('Y-m-d H:i:s'));
                $query->andWhere($fieldToBeFiltered.' >= :'.$valor[1]);
                $query->andWhere($fieldToBeFiltered.' <= :'.$valor[1].'2');
            }
        }
        
        
        // Sorting Process
        if(is_array($sorts)){
            if (count($sorts) > 0) {
                foreach ($sorts as $sort => $order) {
                    $fieldToBeFiltered = array_search($sort, $fields);
                    $query->addOrderBy($fieldToBeFiltered, $order);      
                }
            }
        } else if(empty($sorts)){
            $query->addOrderBy('d.id', 'DESC');
        }

    }
} 