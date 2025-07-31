<?php

namespace App\Interfaces;

interface SearchableContent
{
    /**
     * Search content
     * 
     * @param string $query
     * @return array
     */
    public static function search(string $query): array;
}
