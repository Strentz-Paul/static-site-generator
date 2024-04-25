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
}