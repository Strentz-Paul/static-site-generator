<?php

namespace App\Contract\Service;

interface MenuServiceInterface
{    
    /**
     * getMenuItems
     *
     * @return iterable
     */
    public function getMenuItems(): iterable;
    
    /**
     * getDirectoriesForSlug
     *
     * @return array
     */
    public function getDirectoriesForSlug(): array;
}