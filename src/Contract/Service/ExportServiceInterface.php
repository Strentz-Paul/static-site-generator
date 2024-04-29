<?php

namespace App\Contract\Service;

interface ExportServiceInterface
{        
    /**
     * extractAllPageToExport
     *
     * @return array
     */
    public function extractAllPageToExport(): array;

    /**
     * defineExportFilePath
     *
     * @param string $twigFilePath
     * @return string
     */
    public function defineExportFilePath(string $twigFilePath): string;
    
    /**
     * convertTwigToHtml
     *
     * @param string $twigFilePath
     * @param string|null $specificTemplateFolder
     * @return string
     */
    public function convertTwigToHtml(string $twigFilePath, ?string $specificTemplateFolder = null): string;
        
    /**
     * createSubFolderIfNeeded
     *
     * @param string $exportFilePath
     * @return void
     */
    public function createSubFolderIfNeeded(string $exportFilePath): void;
        
    /**
     * isPublishedPage
     *
     * @param string $filePath
     * @return bool
     */
    public function isPublishedPage(string $filePath): bool;
    
    /**
     * extractDataPublished
     *
     * @param string $filePath
     * @return string|null
     */
    public function extractDataPublished(string $filePath): ?string;
                
    /**
     * isPartOfBlog
     *
     * @param string $filePath
     * @return bool
     */
    public function isPartOfBlog(string $filePath): bool;
    
    /**
     * compile
     *
     * @param string $twigFilePath
     * @param string|null $specificTemplateFolder
     * @return bool
     */
    public function compile(string $twigFilePath, ?string $specificTemplateFolder = null): bool;
}