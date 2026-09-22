<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Howdah\Features\Content\ContentRepository;

/**
 * The repository's query shapes over core post types, on a real database:
 * one hardened query per read, the peek deciding hasMore without a count
 * query, and labels resolved by id.
 */
final class ContentRepositoryTest extends \WP_UnitTestCase
{
    public function testTheHomeListingPeeksForHasMoreWithoutACountQuery(): void
    {
        self::factory()->post->create_many(12, ['post_date' => '2024-01-01 00:00:00']);

        $page1 = $this->content()->home(1);

        self::assertCount(10, $page1->items, 'one page of the declared per-page.');
        self::assertTrue($page1->hasMore, 'the peek row decides hasMore without a count query.');

        $page2 = $this->content()->home(2);

        self::assertCount(2, $page2->items);
        self::assertFalse($page2->hasMore, 'the last page reports no more.');
    }

    public function testAListingPageBeyondTheGraphIsEmpty(): void
    {
        self::factory()->post->create(['post_date' => '2024-01-01 00:00:00']);

        $list = $this->content()->home(9);

        self::assertSame([], $list->items, 'a page with no rows is out of range and renders the empty state.');
        self::assertFalse($list->hasMore);
    }

    public function testATermArchiveListsOnlyItsOwnTerm(): void
    {
        $categoryId = self::factory()->category->create(['name' => 'Notes']);
        $inside = self::factory()->post->create(['post_category' => [$categoryId], 'post_date' => '2024-01-01 00:00:00']);
        self::factory()->post->create(['post_date' => '2024-01-02 00:00:00']);

        $list = $this->content()->term($categoryId, 1);

        self::assertSame([$inside], array_map(static fn ($p) => $p->id, $list->items));
    }

    public function testAnAuthorArchiveListsOnlyThatAuthor(): void
    {
        $userId = self::factory()->user->create(['role' => 'author']);
        $mine = self::factory()->post->create(['post_author' => $userId, 'post_date' => '2024-01-01 00:00:00']);
        self::factory()->post->create(['post_date' => '2024-01-02 00:00:00']);

        $list = $this->content()->author($userId, 1);

        self::assertSame([$mine], array_map(static fn ($p) => $p->id, $list->items));
    }

    public function testADateArchiveListsItsMonth(): void
    {
        $january = self::factory()->post->create(['post_date' => '2024-01-05 00:00:00']);
        self::factory()->post->create(['post_date' => '2023-01-05 00:00:00']);

        $list = $this->content()->date(2024, 1, null, 1);

        self::assertSame([$january], array_map(static fn ($p) => $p->id, $list->items));
    }

    public function testASingularPostSplitsItsContentByTheQueriedPage(): void
    {
        $postId = (int) self::factory()->post->create([
            'post_content' => 'first page<!--nextpage-->second page',
        ]);

        $post = $this->content()->post($postId, 2);

        self::assertNotNull($post);
        self::assertSame(2, $post->page, 'the requested split page is the page mapped.');
        self::assertSame(2, $post->pageCount);
        self::assertStringContainsString('second page', $post->content);
        self::assertStringNotContainsString('first page', $post->content);
    }

    public function testACardCarriesNoContentAndNoTerms(): void
    {
        self::factory()->post->create([
            'post_content' => 'body text',
            'post_category' => [self::factory()->category->create()],
            'post_date' => '2024-01-01 00:00:00',
        ]);

        $list = $this->content()->home(1);

        self::assertNotSame([], $list->items);

        foreach ($list->items as $item) {
            self::assertSame('', $item->content, 'a card carries no content.');
            self::assertSame([], $item->categories, 'a card carries no terms.');
        }
    }

    public function testThePerRequestCapIsDeclaredAndBounded(): void
    {
        self::assertSame(50, ContentRepository::PER_PAGE_CAP, 'the declared cap: never -1, never unbounded.');
    }

    public function testLabelsAreResolvedByTheirId(): void
    {
        $termId = self::factory()->category->create(['name' => 'Ledger']);
        $userId = self::factory()->user->create(['display_name' => 'Ada Byron']);

        self::assertSame('Ledger', $this->content()->termName($termId));
        self::assertSame('Ada Byron', $this->content()->authorName($userId));
        self::assertNull($this->content()->termName(999999), 'an absent term is expected absence, not an error.');
    }

    private static function content(): ContentRepository
    {
        return Bootstrap::services()->get(ContentRepository::class);
    }
}
