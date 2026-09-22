<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Exception\InvalidFieldDeclaration;
use Iniznet\Howdah\Features\Fields\FieldPanel;
use Iniznet\Howdah\Features\Fields\FieldPanels;
use Iniznet\Mahout\Fields\Contracts\FieldRegistry;
use Iniznet\Mahout\Fields\Hooks as FieldHooks;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * The editor seam. A feature's schema declares its field groups in
 * config/fields.php, one FieldPanel per (post type, group) pair; this
 * provider loads that declaration, registers each group on the field
 * package's registry, and shares the collection with AdminProvider, which
 * derives every metabox, save entry and REST binding from it. The empty
 * theme declares no panels.
 */
final class EditorProvider implements ServiceProvider
{
    /** @var list<FieldPanel> */
    private array $panels = [];

    public function register(Container $container): void
    {
        $declarations = require dirname(__DIR__, 2).'/config/fields.php';

        if (!\is_array($declarations)) {
            throw InvalidFieldDeclaration::forType(\get_debug_type($declarations));
        }

        $panels = [];

        foreach ($declarations as $declaration) {
            if (!$declaration instanceof FieldPanel) {
                throw InvalidFieldDeclaration::forType(\get_debug_type($declaration));
            }

            $panels[] = $declaration;
        }

        $this->panels = $panels;
        $collection = new FieldPanels($panels);

        $container->set($collection, FieldPanels::class);

        // register() of every provider runs before any boot(), so this
        // listener is in place when the field package's boot fires the hook.
        \add_action(
            FieldHooks::REGISTRY_LOADED,
            function (FieldRegistry $registry): void {
                foreach ($this->panels as $panel) {
                    $registry->register($panel->group);
                }
            },
            priority: 10,
            accepted_args: 1,
        );
    }

    public function boot(Container $container): void
    {
        // The groups are registered; the editor surfaces belong to the
        // panels' metabox composition in AdminProvider.
    }
}
