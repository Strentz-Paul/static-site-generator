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
    
    /**
     * getTitleData
     *
     * @param string $filePath
     * @return string
     */
    public function getTitleData(string $filePath) :string; 
}