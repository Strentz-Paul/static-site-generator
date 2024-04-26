<?php

namespace App\ViewModel;

readonly class ArticleViewModel
{
    public function __construct(
        private string $url,
        private string $title,
        private string $date,
        private bool $draft
    ) {
    }
    
    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function isDraft(): bool
    {
        return $this->draft;
    }
}
