<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Components\Post\ArchiveHeading;
use Iniznet\Howdah\Components\Post\EmbedBody;
use Iniznet\Howdah\Components\Post\Pagination;
use Iniznet\Howdah\Components\Post\PostBody;
use Iniznet\Howdah\Components\Post\PostCard;
use Iniznet\Howdah\Components\Post\PostTermList;
use Iniznet\Howdah\Features\Content\PostData;
use Iniznet\Howdah\Features\Content\PostTerm;
use Iniznet\Howdah\Support\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * Every Post component's rendered output: typed props in, escaped HTML out,
 * an empty state that renders nothing. A component receives data and renders
 * it — no WordPress object crosses in.
 */
final class PostComponentsTest extends TestCase
{
    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classes = ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json');
    }

    public function testACardRendersTheLinkedTitleTheDateAndTheExcerpt(): void
    {
        $html = $this->card()->render();

        self::assertStringContainsString('<article', $html);
        self::assertStringContainsString('href="https://howdah.test/one"', $html, 'the permalink is the href.');
        self::assertStringContainsString('&lt;script&gt;', $html, 'the title is escaped.');
        self::assertStringContainsString('datetime=', $html, 'the date is machine-readable.');
        self::assertStringContainsString('An excerpt', $html);
    }

    public function testAnExcerptlessCardOmitsTheExcerptElement(): void
    {
        $post = self::post(excerpt: '');

        self::assertStringNotContainsString('post-card-excerpt', (new PostCard($this->classes, $post))->render());
    }

    public function testTheBodyRendersItsContentUnescapedOnce(): void
    {
        $html = (new PostBody($this->classes, '<p>rendered</p>'))->render();

        self::assertSame(1, \substr_count($html, '<p>rendered</p>'), 'the pipeline\'s own bytes reach the page once.');
    }

    public function testATermListRendersNothingWhenEmpty(): void
    {
        self::assertSame('', (new PostTermList($this->classes, [], 'Tags'))->render(), 'an empty term list renders nothing.');
    }

    public function testATermListEscapesEveryTermItRenders(): void
    {
        $terms = [new PostTerm(1, 'S & P', 'category', 'https://howdah.test/cat')];
        $html = (new PostTermList($this->classes, $terms, 'Tags'))->render();

        self::assertStringContainsString('S &amp; P', $html, 'one escape per output.');
        self::assertSame(1, \substr_count($html, '<ul'), 'one list per component.');
    }

    public function testTheArchiveHeadingEscapesItsLabel(): void
    {
        $html = (new ArchiveHeading($this->classes, '<b>March</b>'))->render();

        self::assertStringContainsString('&lt;b&gt;March&lt;/b&gt;', $html);
    }

    public function testPaginationRendersOnlyTheLinksItWasGiven(): void
    {
        self::assertSame('', (new Pagination($this->classes, null, null))->render(), 'a page with neither link renders nothing.');
        self::assertSame(1, \substr_count((new Pagination($this->classes, 'https://howdah.test/1', null))->render(), '<nav'));
    }

    public function testTheEmbedBodyRendersTheThumbnailTheTitleAndTheExcerpt(): void
    {
        $post = self::post(thumbnail: '<img src="cover.jpg">');
        $html = (new EmbedBody($this->classes, $post))->render();

        self::assertStringContainsString('<img src="cover.jpg">', $html);
        self::assertStringContainsString('&lt;script&gt;', $html, 'the title is escaped.');
        self::assertSame(1, \substr_count($html, '<h1'), 'the embed owns its own h1.');
    }

    private function card(): PostCard
    {
        return new PostCard($this->classes, self::post());
    }

    private static function post(string $excerpt = 'An excerpt', string $thumbnail = ''): PostData
    {
        return new PostData(
            id: 1,
            title: '<script>bad</script>',
            content: '',
            excerpt: $excerpt,
            permalink: 'https://howdah.test/one',
            publishedAt: new \DateTimeImmutable('2024-01-01T00:00:00Z'),
            dateDisplay: 'January 1, 2024',
            authorName: 'Ada',
            authorUrl: 'https://howdah.test/author/ada',
            thumbnail: $thumbnail,
            categories: [],
            tags: [],
            pageCount: 1,
            page: 1,
        );
    }
}
