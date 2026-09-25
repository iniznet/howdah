<?php

/**
 * The series archive Surface: the declared post type's label, one page of
 * cards and the archive's pagination. The repository arrives through the
 * constructor; the label is the post type object's own, read at the
 * composition lane — a component never reaches for core.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Series\Surfaces;

use Iniznet\Howdah\Components\SiteChrome;
use Iniznet\Howdah\Features\Series\Components\SeriesCard;
use Iniznet\Howdah\Features\Series\SeriesRepository;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\Document;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\Stack;
use Iniznet\Mahout\Ui\ClassResolver;
use Iniznet\Mahout\Ui\Components\Layout\Grid;
use Iniznet\Mahout\Ui\Components\Message\Message;
use Iniznet\Mahout\Ui\Components\Post\ArchiveHeading;
use Iniznet\Mahout\Ui\Components\Post\Pagination;

final readonly class SeriesArchive implements Component
{
    /** The per-request query ceiling this Surface is held to (reader budget). */
    public const int QUERY_CEILING = 8;

    private const int PER_PAGE = 10;

    public function __construct(
        private QueryContext $ctx,
        private SeriesRepository $series,
        private ClassResolver $classes,
        private readonly ?SiteChrome $chrome = null,
    ) {
    }

    public function render(): string
    {
        $page = $this->ctx->listingPage();
        $list = $this->series->page($page, self::PER_PAGE);

        $parts = [new ArchiveHeading($this->classes, $this->label())];

        if ([] === $list->items) {
            $parts[] = new Message($this->classes, heading: \__('No series has been published yet.', 'howdah'));
        } else {
            $cards = [];

            foreach ($list->items as $item) {
                $cards[] = new SeriesCard($this->classes, $item)->render();
            }

            $parts[] = new Grid($this->classes, $cards);
            $parts[] = $this->pagination($page, $list->hasMore);
        }

        return new Document(
            $this->classes,
            main: new Stack($parts),
            header: $this->chrome?->header(),
            footer: $this->chrome?->footer(),
        )->render();
    }

    /** The declared post type's own label, resolved once per render. */
    private function label(): string
    {
        $object = \get_post_type_object('howdah_series');
        $name = null === $object ? null : $object->labels->name;

        return \is_string($name) ? $name : 'Series';
    }

    private function pagination(int $page, bool $hasMore): Pagination
    {
        $base = (string) \get_post_type_archive_link('howdah_series');

        return new Pagination(
            $this->classes,
            $page > 1 ? \add_query_arg('page', (string) ($page - 1), $base) : null,
            $hasMore ? \add_query_arg('page', (string) ($page + 1), $base) : null,
        );
    }
}
