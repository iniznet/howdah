<?php

/**
 * The only file with WP_Post. Every WordPress object the render sees crosses
 * into a readonly DTO here, and nowhere else: a Component receives data and
 * never a WP_* type, and the field layer is the only reader of a registered
 * field.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content;

use Iniznet\Howdah\Render\Exception\SurfaceDataMissing;
use Iniznet\Howdah\Support\Hooks;

final readonly class PostMapper
{
    /** The full render: content, terms, thumbnail, author. */
    public function post(\WP_Post $post, int $page = 1): PostData
    {
        $splits = \explode('<!--nextpage-->', (string) $post->post_content);
        $pageCount = \max(1, \count($splits));
        $page = \min(\max(1, $page), $pageCount);
        $authorId = (int) $post->post_author;

        return new PostData(
            id: (int) $post->ID,
            title: (string) \get_the_title($post),
            content: $this->body((string) $splits[$page - 1]),
            excerpt: (string) \get_the_excerpt($post),
            permalink: (string) \get_permalink($post),
            publishedAt: $this->publishedAt($post),
            dateDisplay: $this->dateDisplay($post),
            authorName: $this->authorName($authorId),
            authorUrl: (string) \get_author_posts_url($authorId),
            thumbnail: $this->thumbnail($post),
            categories: $this->terms($post, 'category'),
            tags: $this->terms($post, 'post_tag'),
            pageCount: $pageCount,
            page: $page,
        );
    }

    /** The listing card: no content processing, no terms, no thumbnail. */
    public function teaser(\WP_Post $post): PostData
    {
        $authorId = (int) $post->post_author;

        return new PostData(
            id: (int) $post->ID,
            title: (string) \get_the_title($post),
            content: '',
            excerpt: (string) \get_the_excerpt($post),
            permalink: (string) \get_permalink($post),
            publishedAt: $this->publishedAt($post),
            dateDisplay: $this->dateDisplay($post),
            authorName: $this->authorName($authorId),
            authorUrl: (string) \get_author_posts_url($authorId),
            thumbnail: '',
            categories: [],
            tags: [],
            pageCount: 1,
            page: 1,
        );
    }

    /**
     * The_content pipeline is core's own sanitiser for post content: kses
     * runs for unprivileged authors, blocks render, shortcodes resolve. This
     * single boundary is where the value leaves the request's hands, which is
     * what the taint escape below declares.
     *
     * @psalm-taint-escape html
     */
    private function body(string $raw): string
    {
        $filtered = \apply_filters(Hooks::THE_CONTENT, $raw);

        if (!\is_string($filtered)) {
            throw SurfaceDataMissing::forQuery(Hooks::THE_CONTENT);
        }

        return $filtered;
    }

    private function publishedAt(\WP_Post $post): \DateTimeImmutable
    {
        $stamp = \get_post_timestamp($post);

        if (\is_int($stamp)) {
            return new \DateTimeImmutable()->setTimestamp($stamp);
        }

        return new \DateTimeImmutable('@0');
    }

    private function dateDisplay(\WP_Post $post): string
    {
        $stamp = \get_post_timestamp($post);

        if (!\is_int($stamp)) {
            return '';
        }

        $option = \get_option('date_format', 'F j, Y');
        $format = \is_string($option) && '' !== $option ? $option : 'F j, Y';

        return (string) \wp_date($format, $stamp);
    }

    /**
     * The featured image, in core's own sized markup. get_the_post_thumbnail
     * is core's sanitised HTML for the attachment; this single boundary is
     * where the value leaves the request's hands, which is what the taint
     * escape below declares.
     *
     * @psalm-taint-escape html
     */
    private function thumbnail(\WP_Post $post): string
    {
        return (string) \get_the_post_thumbnail($post);
    }

    private function authorName(int $authorId): string
    {
        if ($authorId < 1) {
            return '';
        }

        $user = \get_userdata($authorId);

        return $user instanceof \WP_User ? $user->display_name : '';
    }

    /**
     * @return list<PostTerm>
     */
    private function terms(\WP_Post $post, string $taxonomy): array
    {
        $terms = \get_the_terms($post, $taxonomy);

        if (!\is_array($terms)) {
            return [];
        }

        $mapped = [];

        foreach ($terms as $term) {
            $link = \get_term_link($term);

            if (!\is_string($link)) {
                continue;
            }

            $mapped[] = new PostTerm(
                id: (int) $term->term_id,
                name: (string) $term->name,
                taxonomy: (string) $term->taxonomy,
                link: $link,
            );
        }

        return $mapped;
    }
}
