<?php

/**
 * The archive Surface: date, author and every public term taxonomy. The
 * heading is the queried object's own label, resolved by id; the listing is
 * one page of teasers with pagination from the peek. An empty archive
 * renders the site's declared empty state.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content\Surfaces;

use Iniznet\Howdah\Components\SiteChrome;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Mahout\Content\PostList;
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

final readonly class ContentArchive implements Component
{
    /** The per-request query ceiling this Surface is held to (reader budget). */
    public const int QUERY_CEILING = 8;

    public function __construct(
        private QueryContext $ctx,
        private ContentRepository $content,
        private ClassResolver $classes,
        private readonly ?SiteChrome $chrome = null,
    ) {
    }

    public function render(): string
    {
        $page = $this->page();
        $list = $this->list($page);

        $items = [new ArchiveHeading($this->classes, $this->heading())];

        if ([] === $list->items) {
            $items[] = new Message($this->classes, heading: \__('Nothing has been published here yet.', 'howdah'));
        } else {
            $cards = [];

            foreach ($list->items as $post) {
                $cards[] = new PostCard($this->classes, $post)->render();
            }

            $items[] = new Grid($this->classes, $cards);
        }

        $items[] = $this->pagination($list->hasMore, $page);

        return new Document(
            $this->classes,
            main: new Stack($items),
            header: $this->chrome?->header(),
            footer: $this->chrome?->footer(),
        )->render();
    }

    private function list(int $page): PostList
    {
        $objectId = $this->ctx->objectId ?? 0;

        if ('author' === $this->ctx->objectSubtype) {
            return $this->content->author($objectId, $page);
        }

        if (null !== $this->ctx->objectSubtype) {
            return $this->content->term($objectId, $page);
        }

        return $this->content->date(
            year: \max(1, $this->ctx->intVar('year')),
            month: 0 !== $this->ctx->intVar('monthnum') ? \max(1, $this->ctx->intVar('monthnum')) : null,
            day: 0 !== $this->ctx->intVar('day') ? \max(1, $this->ctx->intVar('day')) : null,
            page: $page,
        );
    }

    /** The heading: the queried author, term or date, in the site's own words. */
    private function heading(): string
    {
        $objectId = $this->ctx->objectId;

        if ('author' === $this->ctx->objectSubtype && null !== $objectId) {
            $name = $this->content->authorName($objectId);

            return null !== $name
                ? \sprintf(\__('Posts by %s', 'howdah'), $name)
                : (string) \__('Author archive', 'howdah');
        }

        if (null !== $this->ctx->objectSubtype && null !== $objectId) {
            $label = $this->content->taxonomyLabel($this->ctx->objectSubtype) ?? (string) \__('Archive', 'howdah');
            $name = $this->content->termName($objectId) ?? '';

            return '' !== $name ? $label.': '.$name : $label;
        }

        return $this->dateHeading();
    }

    /** A date archive's heading: the year, the month or the day, site-formatted. */
    private function dateHeading(): string
    {
        $year = \max(1, $this->ctx->intVar('year'));
        $month = \max(1, $this->ctx->intVar('monthnum'));
        $day = \max(1, $this->ctx->intVar('day'));

        $stamp = new \DateTimeImmutable()->setDate($year, $month, $day)->setTime(0, 0);

        $format = 0 === $this->ctx->intVar('day')
            ? (0 === $this->ctx->intVar('monthnum') ? 'Y' : 'F Y')
            : 'F j, Y';

        return (string) \wp_date($format, $stamp->getTimestamp());
    }

    private function pagination(bool $hasMore, int $page): Pagination
    {
        $newer = $page > 1 ? \get_pagenum_link($page - 1) : null;
        $older = $hasMore ? \get_pagenum_link($page + 1) : null;

        return new Pagination($this->classes, $newer, $older);
    }

    private function page(): int
    {
        return $this->ctx->listingPage();
    }
}
