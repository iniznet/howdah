<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Components\Forms\Checkbox;
use Iniznet\Howdah\Components\Forms\Combobox;
use Iniznet\Howdah\Components\Forms\Fieldset;
use Iniznet\Howdah\Components\Forms\FormSummary;
use Iniznet\Howdah\Components\Forms\Input;
use Iniznet\Howdah\Components\Forms\InputType;
use Iniznet\Howdah\Components\Forms\Option;
use Iniznet\Howdah\Components\Forms\Radio;
use Iniznet\Howdah\Components\Forms\Select;
use Iniznet\Howdah\Components\Forms\Textarea;
use Iniznet\Howdah\Support\ClassResolver;
use PHPUnit\Framework\TestCase;

/**
 * The form primitives. Every control owns its label and its association: a
 * label that is not programmatically associated is decoration, so the id
 * and the for always travel together, and help text reaches the control
 * through aria-describedby.
 */
final class FormComponentsTest extends TestCase
{
    private ClassResolver $classes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classes = ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json');
    }

    public function testAnInputPairsItsLabelAndItsHelp(): void
    {
        $html = (new Input($this->classes, 'howdah-email', 'email', 'Email', InputType::Email, describedBy: 'howdah-email-help'))->render();

        self::assertStringContainsString('for="howdah-email"', $html);
        self::assertStringContainsString('id="howdah-email"', $html);
        self::assertStringContainsString('type="email"', $html);
        self::assertStringContainsString('aria-describedby="howdah-email-help"', $html);
    }

    public function testARequiredInputUsesTheAttribute(): void
    {
        $html = (new Input($this->classes, 'howdah-name', 'name', 'Name', required: true))->render();

        self::assertStringContainsString(' required', $html);
    }

    public function testAnInvalidInputIsAnnounced(): void
    {
        $html = (new Input($this->classes, 'howdah-name', 'name', 'Name', invalid: true))->render();

        self::assertStringContainsString('aria-invalid="true"', $html);
    }

    public function testAValueIsEscapedIntoTheTextarea(): void
    {
        $html = (new Textarea($this->classes, 'howdah-bio', 'bio', 'Bio', 'Line one & <two>'))->render();

        self::assertStringContainsString('Line one &amp; &lt;two&gt;', $html);
        self::assertStringContainsString('rows="5"', $html);
    }

    public function testASelectMarksItsSelectedOption(): void
    {
        $html = (new Select($this->classes, 'howdah-voice', 'voice', 'Voice', [
            new Option('calm', 'Calm'),
            new Option('bright', 'Bright', selected: true),
        ]))->render();

        self::assertSame(1, substr_count($html, 'selected'), 'exactly one option is selected.');
        self::assertStringContainsString('value="bright"', $html);
    }

    public function testACheckboxCarriesItsState(): void
    {
        self::assertStringContainsString(' checked', (new Checkbox($this->classes, 'c1', 'terms', 'I agree', checked: true))->render());
        self::assertStringNotContainsString(' checked', (new Checkbox($this->classes, 'c2', 'terms', 'I agree'))->render());
    }

    public function testARadioCarriesItsValue(): void
    {
        $html = (new Radio($this->classes, 'r1', 'plan', 'monthly', 'Monthly'))->render();

        self::assertStringContainsString('type="radio"', $html);
        self::assertStringContainsString('value="monthly"', $html);
        self::assertStringContainsString('for="r1"', $html);
    }

    public function testAFieldsetGroupsItsControlsUnderALegend(): void
    {
        $controls = (new Radio($this->classes, 'p1', 'plan', 'monthly', 'Monthly'))->render();
        $html = (new Fieldset($this->classes, 'Plan', $controls))->render();

        self::assertStringContainsString('<fieldset', $html);
        self::assertStringContainsString('<legend', $html);
        self::assertStringContainsString('Plan', $html);
        self::assertStringContainsString('type="radio"', $html, 'the controls render inside.');
    }

    public function testAComboboxOwnsItsListbox(): void
    {
        $html = (new Combobox($this->classes, 'howdah-term', 'term', 'Search terms', ['harbor', 'canyon']))->render();

        self::assertStringContainsString('role="combobox"', $html);
        self::assertStringContainsString('aria-controls="howdah-term-list"', $html);
        self::assertStringContainsString('aria-expanded="false"', $html);
        self::assertStringContainsString('role="listbox"', $html);
        self::assertSame(2, substr_count($html, 'role="option"'));
    }

    public function testAFormSummaryAnnouncesEachError(): void
    {
        $html = (new FormSummary($this->classes, ['Enter an email address.', 'Choose a plan.']))->render();

        self::assertStringContainsString('aria-live="polite"', $html);
        self::assertSame(2, substr_count($html, '<li>'), 'each error is its own entry.');
        self::assertStringContainsString('Enter an email address.', $html);
        self::assertStringContainsString('Choose a plan.', $html);
    }
}
