<?php

namespace App\Contract\Service;

interface ArticleServiceInterface
{    
    /**
     * getAllFormatedArticles
     *
     * @return iterable
     */
    public function getAllFormatedArticles(): iterable;
}