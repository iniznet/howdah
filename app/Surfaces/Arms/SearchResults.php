<?php

/**
 * The search arm. The key space of a free-text term is not enumerable, so
 * the arm is Uncacheable at both the HTTP layer and the fragment layer.
 *
 * A term with no usable token renders the empty state and issues no query —
 * the LIKE fallback for a too-short search would be the attack the search
 * rules exist to prevent. The tokeniser's caps are `mahout-db`'s, and so is
 * the loud record of the LIKE fallback: the swap reports an absent index
 * once per request, on the path that takes it. This arm records nothing.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Surfaces\Arms;

use Iniznet\Howdah\Components\SearchForm;
use Iniznet\Howdah\Components\SiteChrome;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Mahout\Db\Search\SearchTerms;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\Document;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\Stack;
use Iniznet\Mahout\Ui\ClassResolver;
use Iniznet\Mahout\Ui\Components\Layout\Grid;
use Iniznet\Mahout\Ui\Components\Message\Message;
use Iniznet\Mahout\Ui\Components\Post\ArchiveHeading;
use Iniznet\Mahout\Ui\Components\Post\Pagination;
use Iniznet\Mahout\Ui\Components\Post\PostCard;

final readonly class SearchResults implements Component
{
    /** The per-request query ceiling this Surface is held to (reader budget). */
    public const int QUERY_CEILING = 10;

    public function __construct(
        private QueryContext $ctx,
        private ClassResolver $classes,
        private ContentRepository $content,
        private readonly ?SiteChrome $chrome = null,
    ) {
    }

    public function render(): string
    {
        $terms = SearchTerms::fromString($this->term());

        if (!$terms->hasTokens()) {
            return new Document(
                $this->classes,
                main: new Message(
                    $this->classes,
                    heading: \__('Nothing matched this search.', 'howdah'),
                    detail: \sprintf(
                        \__('Every word needs at least %d letters or numbers.', 'howdah'),
                        SearchTerms::MIN_TOKEN_SIZE,
                    ),
                ),
            )->render();
        }

        $list = $this->content->search($terms, $this->ctx->listingPage());

        if ([] === $list->items) {
            return new Document(
                $this->classes,
                main: new Stack([
                    new Message(
                        $this->classes,
                        heading: \__('Nothing matched this search.', 'howdah'),
                        detail: $terms->raw,
                    ),
                    $this->form(),
                ]),
                header: $this->chrome?->header(),
                footer: $this->chrome?->footer(),
            )->render();
        }

        $cards = [];

        foreach ($list->items as $post) {
            $cards[] = new PostCard($this->classes, $post)->render();
        }

        $items = [
            new ArchiveHeading(
                $this->classes,
                \sprintf(\__('Search results for: %s', 'howdah'), $terms->raw),
            ),
            new Grid($this->classes, $cards),
            $this->pagination($list->hasMore, $this->ctx->listingPage()),
        ];

        return new Document(
            $this->classes,
            main: new Stack($items),
            header: $this->chrome?->header(),
            footer: $this->chrome?->footer(),
        )->render();
    }

    /** The raw term, from the query vars — never from a superglobal. */
    private function term(): string
    {
        $s = $this->ctx->queryVars['s'] ?? '';

        return \is_string($s) ? $s : '';
    }

    private function pagination(bool $hasMore, int $page): Pagination
    {
        $newer = $page > 1 ? \get_pagenum_link($page - 1) : null;
        $older = $hasMore ? \get_pagenum_link($page + 1) : null;

        return new Pagination($this->classes, $newer, $older);
    }

    /** The search form re-runs the search from the results page. */
    private function form(): SearchForm
    {
        return new SearchForm(
            $this->classes,
            (string) \home_url('/'),
            $this->term(),
        );
    }
}
