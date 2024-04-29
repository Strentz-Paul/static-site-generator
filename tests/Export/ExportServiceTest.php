<?php

namespace App\Tests\Export;

use App\Contract\Service\ExportServiceInterface;
use App\Service\ExportService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class ExportServiceTest extends KernelTestCase
{
    /**
     * Here we have:
     *  3 index pages
     *  4 articles pages published
     *  1 article page in draft
     */
    private const COUNT_OF_FILE_NORMALY_EXPORTED = 7;
    private const PAGE_FOLDER_PATH = __DIR__ . '/../assets/Pages/';
    private const EXPORT_FOLDER_PATH = __DIR__ . '/../assets/export/';
    private const EXPORT_FOLDER_PATH_STRING = '/app/tests/assets/export/';
    private const FILE_NAME_PUBLISHED = 'Articles/comment-evoluer-en-tant-que-junior.html.twig';
    private const FILE_NAME_DRAFT = 'Articles/symfony-c-est-le-feu.html.twig';
    private const FILE_NAME_NOT_EXIST = 'Articles/cette-page-n-existe-pas.html.twig';
    private const FILE_NAME_NULL_PUBLISHED = 'Articles/null-published.html.twig';
    private const FILE_NAME_ARTICLES_INDEX = 'Articles/index.html.twig';
    private const FILE_NAME_ABOUT_INDEX = 'About/index.html.twig';
    private const FILE_NAME_BASE_INDEX = 'index.html.twig';
    private const FILE_NAME_NOT_EXIST_IN_ABOUT = 'About/cette-page-n-existe-pas.html.twig';
    private const ARRAY_OF_FILE_FOR_COMPILE = [
        'Pages/' . self::FILE_NAME_PUBLISHED,
        'Pages/' . self::FILE_NAME_ARTICLES_INDEX,
        'Pages/' . self::FILE_NAME_BASE_INDEX,
        'Pages/' . self::FILE_NAME_ABOUT_INDEX
    ];
    private const EXPECTED_ABOUT_FOLDER = 'About/';
    private const EXPECTED_ARTICLE_FOLDER = 'Articles/';

    private ExportServiceInterface $service;

    public function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->service = self::getContainer()->get(ExportService::class);
    }

    public function testIsPublishedPageException(): void
    {
        $filePathNotExist = self::PAGE_FOLDER_PATH . self::FILE_NAME_NOT_EXIST;
        $this->expectException(FileNotFoundException::class);
        $this->service->isPublishedPage($filePathNotExist);
    }

    public function testExtractDataPublishedNull(): void
    {
        $filePathNullPublished = self::PAGE_FOLDER_PATH . self::FILE_NAME_NULL_PUBLISHED;
        $resultNull = $this->service->extractDataPublished($filePathNullPublished);
        self::assertNull($resultNull);
    }

    public function testExtractDataPublishedTrue(): void
    {
        $filePathPublished = self::PAGE_FOLDER_PATH . self::FILE_NAME_PUBLISHED; 
        $resultTrue = $this->service->extractDataPublished($filePathPublished);
        self::assertSame('true', $resultTrue);
    }

    public function testExtractDataPublishedFalse(): void
    {
        $filePathDraft = self::PAGE_FOLDER_PATH . self::FILE_NAME_DRAFT;
        $resultFalse = $this->service->extractDataPublished($filePathDraft);
        self::assertSame('false', $resultFalse);
    }

    public function testIsPublishedPageTrue(): void
    {
        $filePathPublished = self::PAGE_FOLDER_PATH . self::FILE_NAME_PUBLISHED; 
        $resultPublished = $this->service->isPublishedPage($filePathPublished);
        self::assertTrue($resultPublished);
    }

    public function testIsPublishedPageFalse(): void
    {
        $filePathDraft = self::PAGE_FOLDER_PATH . self::FILE_NAME_DRAFT;
        $resultDraft = $this->service->isPublishedPage($filePathDraft);
        self::assertFalse($resultDraft);
    }

    public function testExtractAllPageToExport(): void
    {
        $result = $this->service->extractAllPageToExport();
        self::assertIsArray($result);
        self::assertCount(self::COUNT_OF_FILE_NORMALY_EXPORTED, $result);
    }

    public function testIsPartOfBlog(): void
    {
        $filePathIndex = self::PAGE_FOLDER_PATH . self::FILE_NAME_BASE_INDEX;
        $filePathAboutIndex = self::PAGE_FOLDER_PATH . self::FILE_NAME_ABOUT_INDEX;
        $filePathArticleIndex = self::PAGE_FOLDER_PATH . self::FILE_NAME_ARTICLES_INDEX;
        $filePathArticleFile = self::PAGE_FOLDER_PATH . self::FILE_NAME_DRAFT;
        $filePathAboutFile = self::PAGE_FOLDER_PATH . self::FILE_NAME_NOT_EXIST_IN_ABOUT;
        $resultIndex = $this->service->isPartOfBlog($filePathIndex);
        self::assertFalse($resultIndex);
        $resultAboutIndex = $this->service->isPartOfBlog($filePathAboutIndex);
        self::assertFalse($resultAboutIndex);
        $resultArticleIndex = $this->service->isPartOfBlog($filePathArticleIndex);
        self::assertFalse($resultArticleIndex);
        $resultArticleFile = $this->service->isPartOfBlog($filePathArticleFile);
        self::assertTrue($resultArticleFile);
        $resultAboutFile = $this->service->isPartOfBlog($filePathAboutFile);
        self::assertFalse($resultAboutFile);
    }

    public function testDefineExportFilePath(): void
    {
        $filePathIndex = self::PAGE_FOLDER_PATH . self::FILE_NAME_BASE_INDEX;
        $expectedPathIndex = self::EXPORT_FOLDER_PATH_STRING . str_replace('.twig', '', self::FILE_NAME_BASE_INDEX);
        $resultBaseIndex = $this->service->defineExportFilePath($filePathIndex);
        self::assertSame($expectedPathIndex, $resultBaseIndex);

        $filePathAboutIndex = self::PAGE_FOLDER_PATH . self::FILE_NAME_ABOUT_INDEX;
        $expectedPathAboutIndex = self::EXPORT_FOLDER_PATH_STRING . str_replace('.twig', '', self::FILE_NAME_ABOUT_INDEX);
        $resultAboutIndex = $this->service->defineExportFilePath($filePathAboutIndex);
        self::assertSame($expectedPathAboutIndex, $resultAboutIndex);

        $filePathArticleIndex = self::PAGE_FOLDER_PATH . self::FILE_NAME_ARTICLES_INDEX;
        $expectedPathArticleIndex = self::EXPORT_FOLDER_PATH_STRING . str_replace('.twig', '', self::FILE_NAME_ARTICLES_INDEX);
        $resultArticleIndex = $this->service->defineExportFilePath($filePathArticleIndex);
        self::assertSame($expectedPathArticleIndex, $resultArticleIndex);

        $filePathPublishedFile = self::PAGE_FOLDER_PATH . self::FILE_NAME_PUBLISHED;
        $exportPathPublishedFile = self::EXPORT_FOLDER_PATH_STRING . str_replace('.twig', '', self::FILE_NAME_PUBLISHED);
        $resultArticlePublished = $this->service->defineExportFilePath($filePathPublishedFile);

        self::assertSame($exportPathPublishedFile, $resultArticlePublished);
    }

    public function testCreateSubFolderIfNeeded(): void
    {
        $filesystem = new Filesystem();
        $filePathAbout = self::EXPORT_FOLDER_PATH_STRING . str_replace('.twig', '', self::FILE_NAME_ABOUT_INDEX);
        $expectedAboutFolder = self::EXPORT_FOLDER_PATH_STRING . self::EXPECTED_ABOUT_FOLDER;
        $this->service->createSubFolderIfNeeded($filePathAbout);
        self::assertTrue($filesystem->exists($expectedAboutFolder));
        self::assertTrue(is_dir($expectedAboutFolder));

        $filePathArticle = self::EXPORT_FOLDER_PATH_STRING . str_replace('.twig', '', self::FILE_NAME_ARTICLES_INDEX);
        $expectedArticleFolder = self::EXPORT_FOLDER_PATH_STRING . self::EXPECTED_ARTICLE_FOLDER;
        $this->service->createSubFolderIfNeeded($filePathArticle);
        self::assertTrue($filesystem->exists($expectedArticleFolder));
        self::assertTrue(is_dir($expectedArticleFolder));
    }

    public function testConvertTwigToHtml(): void
    {
        $filePathAbout = '@tests/Pages/' . self::FILE_NAME_ABOUT_INDEX;
        $result = $this->service->convertTwigToHtml($filePathAbout);
        self::assertIsString($result); 
        self::assertStringContainsString('<head', $result);
        self::assertStringContainsString('<body', $result);
        self::assertStringContainsString('<html', $result);
        self::assertStringContainsString('<!DOCTYPE', $result);

        $filePathIndex = '@tests/Pages/' . self::FILE_NAME_BASE_INDEX;
        $result = $this->service->convertTwigToHtml($filePathIndex);
        self::assertIsString($result);
        self::assertStringContainsString('<head', $result);
        self::assertStringContainsString('<body', $result);
        self::assertStringContainsString('<html', $result);
        self::assertStringContainsString('<!DOCTYPE', $result);

        $filePathArticle = '@tests/Pages/' . self::FILE_NAME_PUBLISHED;
        $result = $this->service->convertTwigToHtml($filePathArticle);
        self::assertIsString($result);
        self::assertStringContainsString('<head', $result);
        self::assertStringContainsString('<body', $result);
        self::assertStringContainsString('<html', $result);
        self::assertStringContainsString('<!DOCTYPE', $result);
        self::assertStringContainsString('<h1>Voici Comment evoluer en tant que junior</h1>', $result);
    }

    public function testCompile(): void
    {
        $hitCount = 0;
        $failCount = 0;
        foreach (self::ARRAY_OF_FILE_FOR_COMPILE as $file) {
            $hit = $this->service->compile($file, '@tests');
            self::assertIsBool($hit);
            if ($hit) {
                $hitCount++;
                continue;
            }
            $failCount++;
        }
        self::assertEquals(4, $hitCount);
        self::assertEquals(0, $failCount);
        // TODO: ici controller que l'on a bien le dossier export
    }

}