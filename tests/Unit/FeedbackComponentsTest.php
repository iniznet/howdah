<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Components\Actions\Link;
use Iniznet\Howdah\Components\Feedback\EmptyState;
use Iniznet\Howdah\Components\Feedback\ErrorState;
use Iniznet\Howdah\Components\Feedback\LoadingState;
use Iniznet\Howdah\Components\Feedback\ProgressBar;
use Iniznet\Howdah\Components\Feedback\Spinner;
use Iniznet\Howdah\Support\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * The non-happy states. Each state is a component with its own contract: an
 * empty state invites the next action, an error state is announced, a
 * loading state carries a text alternative so it survives reduced motion.
 */
final class FeedbackComponentsTest extends TestCase
{
    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classes = ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json');
    }

    public function testAnEmptyStateInvitesTheNextAction(): void
    {
        $action = (new Link($this->classes, '/search', 'Browse the archive'))->render();
        $html = (new EmptyState($this->classes, 'Nothing here yet', 'No chapters are published.', $action))->render();

        self::assertStringContainsString('Nothing here yet', $html);
        self::assertStringContainsString('No chapters are published.', $html);
        self::assertStringContainsString('href="/search"', $html, 'the next action renders.');
    }

    public function testAnEmptyStateOmitsAnAbsentAction(): void
    {
        $html = (new EmptyState($this->classes, 'Nothing here yet', 'Nothing to show.'))->render();

        self::assertStringNotContainsString('empty-state-actions', $html);
    }

    public function testAnErrorStateIsAnnouncedAndCarriesAReference(): void
    {
        $html = (new ErrorState($this->classes, 'Something went wrong', 'The chapter could not be loaded.', 'howdah-render-404'))->render();

        self::assertStringContainsString('role="alert"', $html);
        self::assertStringContainsString('howdah-render-404', $html);
    }

    public function testAnErrorStateWithoutAReferenceOmitsTheLine(): void
    {
        $html = (new ErrorState($this->classes, 'Something went wrong', 'The page is unavailable.'))->render();

        self::assertStringNotContainsString('error-state-reference', $html);
    }

    public function testALoadingStateCarriesATextAlternative(): void
    {
        $html = (new LoadingState($this->classes, 'Loading chapters'))->render();

        self::assertStringContainsString('role="status"', $html);
        self::assertStringContainsString('aria-hidden="true"', $html, 'the spinner is decorative.');
        self::assertStringContainsString('Loading chapters', $html);
    }

    public function testAProgressBarAnnouncesItsValue(): void
    {
        $html = (new ProgressBar($this->classes, 'Reading progress', 42))->render();

        self::assertStringContainsString('role="progressbar"', $html);
        self::assertStringContainsString('aria-valuenow="42"', $html);
        self::assertStringContainsString('aria-label="Reading progress"', $html);
    }

    public function testAProgressBarClampsItsValue(): void
    {
        $html = (new ProgressBar($this->classes, 'Progress', 150))->render();

        self::assertStringContainsString('aria-valuenow="100"', $html);
    }

    public function testASpinnerIsDecorative(): void
    {
        $html = (new Spinner($this->classes))->render();

        self::assertStringContainsString('aria-hidden="true"', $html);
    }
}
