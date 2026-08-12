<?php

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Flags `use <Qualified\Name>;` statements written inside class bodies.
 *
 * In PHP, a `use` inside a class body is a *trait use* whose name is resolved
 * RELATIVE to the current namespace — top-of-file `use` imports do not apply.
 * So `use App\Support\Traits\Foo;` inside a class in namespace
 * `Modules\X\Resources` silently looks for `Modules\X\Resources\App\...\Foo`
 * and fails with "Trait ... not found" at class-load time.
 *
 * PHPStan resolves every name in the AST (NameResolver with
 * `preserveOriginalNames`), so the trait name node is already a fully-qualified
 * resolved name. The original source spelling is kept in the `originalName`
 * attribute and is what this rule inspects.
 *
 * @implements Rule<TraitUse>
 */
final class NoQualifiedTraitUseRule implements Rule
{
    public function getNodeType(): string
    {
        return TraitUse::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($node->traits as $traitName) {
            if (! $traitName instanceof Name) {
                continue;
            }

            $originalName = $traitName->getAttribute('originalName');

            // No originalName attribute means the name was already
            // fully-qualified in the source (`\App\Foo`) — fine.
            if (! $originalName instanceof Name) {
                continue;
            }

            // Short names (`Foo`) are resolved via top-of-file imports — fine.
            if ($originalName->isFullyQualified() || $originalName->isUnqualified()) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Trait use "%s" uses a relative qualified name; PHP resolves it against the current namespace (%s), so the trait will almost certainly not be found. Use a fully-qualified name ("\\%s") or import the trait at the top of the file and use its short name.',
                $originalName->toString(),
                $scope->getNamespace() ?? 'global namespace',
                $originalName->toString(),
            ))
                ->identifier('app.traitUse.relativeName')
                ->build();
        }

        return $errors;
    }
}
