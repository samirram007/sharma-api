<?php

/**
 * Lint guard: catches `use <Qualified\Name>;` statements written inside class bodies.
 *
 * In PHP, a `use` inside a class body is a *trait use* whose name is resolved
 * RELATIVE to the current namespace — top-of-file `use` imports do not apply, so
 *
 *     namespace Modules\AccountLedger\Resources;
 *
 *     class LedgerBalanceResource extends SuccessResource
 *     {
 *         use App\Support\Traits\CamelCaseResource; // resolves to
 *     }                                             // Modules\...\Resources\App\Support\...
 *
 * ...silently breaks with "Trait ... not found" at class-load time.
 *
 * This scan covers the whole tree (including directories PHPStan excludes, such
 * as Resources, Requests and Facades) by tokenizing each PHP file and looking
 * for `use` statements inside class bodies whose first name contains a namespace
 * separator but no leading backslash.
 *
 * Usage:
 *   php scripts/lint-qualified-trait-use.php            # scan app/
 *   php scripts/lint-qualified-trait-use.php <path>      # scan a single file or directory
 */

$root = $argv[1] ?? __DIR__ . '/../app';

$errors = [];

if (is_file($root)) {
    lintFile($root, $errors);
} elseif (is_dir($root)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        lintFile($file->getPathname(), $errors);
    }
} else {
    fwrite(STDERR, "Path not found: {$root}\n");
    exit(2);
}

if ($errors === []) {
    echo "OK — no relative qualified trait use statements found.\n";
    exit(0);
}

foreach ($errors as $error) {
    fwrite(STDERR, $error . "\n");
}

fwrite(STDERR, sprintf("\nFound %d relative qualified trait use statement(s).\n", count($errors)));
exit(1);

/**
 * @param  list<string>  $errors
 */
function lintFile(string $path, array &$errors): void
{
    $source = file_get_contents($path);

    if ($source === false) {
        return;
    }

    $tokens = token_get_all($source);
    $count = count($tokens);

    $depth = 0; // current brace depth
    $classDepths = []; // brace depth where an enclosing class/trait/enum body opened
    $expectClassBrace = false;
    $prevSignificant = null; // [id, text, line] of the previous significant token

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        if (is_array($token)) {
            [$id, $text, $line] = $token;

            if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            // `class`/`trait`/`enum` start a body — but not the `class` in `Foo::class`.
            if (in_array($id, [T_CLASS, T_TRAIT, T_ENUM], true)
                && ($prevSignificant === null || $prevSignificant[0] !== T_DOUBLE_COLON)
            ) {
                $expectClassBrace = true;
            }

            if ($id === T_USE) {
                checkTraitUse($tokens, $i, $path, $classDepths, $errors);
            }

            $prevSignificant = [$id, $text, $line];
            continue;
        }

        // Single-character token.
        if ($token === '{') {
            if ($expectClassBrace) {
                $classDepths[] = $depth;
                $expectClassBrace = false;
            }

            $depth++;
        } elseif ($token === '}') {
            $depth--;

            if ($classDepths !== [] && end($classDepths) === $depth) {
                array_pop($classDepths);
            }
        } elseif ($token === ';') {
            // e.g. `abstract class Foo;` — never expect a body after a `;`
            $expectClassBrace = false;
        }

        $prevSignificant = [$token, $token, 0];
    }
}

/**
 * Inspect a `use` statement that appears inside a class body.
 *
 * @param  list<mixed>  $tokens
 * @param  list<int>  $classDepths
 * @param  list<string>  $errors
 */
function checkTraitUse(array $tokens, int $index, string $path, array $classDepths, array &$errors): void
{
    // Top-of-file class imports (`use App\Foo;`) live outside any class body — fine.
    if ($classDepths === []) {
        return;
    }

    $count = count($tokens);
    $i = $index + 1;
    $line = is_array($tokens[$index]) ? $tokens[$index][2] : 0;

    while (true) {
        // Skip whitespace/comments before the next name.
        while ($i < $count) {
            $t = $tokens[$i];

            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i++;
                continue;
            }

            break;
        }

        if ($i >= $count) {
            return;
        }

        $t = $tokens[$i];

        // Closure `use ($var)` — not a trait use.
        if ($t === '(') {
            return;
        }

        if (! is_array($t)) {
            return;
        }

        // On PHP 8+, qualified names arrive as single tokens:
        //   T_NAME_FULLY_QUALIFIED -> `\App\Foo`   (absolute — fine)
        //   T_NAME_QUALIFIED       -> `App\Foo`    (relative — the bug)
        //   T_NAME_RELATIVE        -> `namespace\Foo` (relative — same footgun)
        //   T_STRING               -> `Foo`         (short name — fine)
        if ($t[0] === T_NAME_QUALIFIED || $t[0] === T_NAME_RELATIVE) {
            $name = ltrim($t[1], '\\');

            $errors[] = sprintf(
                '%s:%d: Trait use "%s" uses a relative qualified name; PHP resolves it against the current namespace, so the trait will almost certainly not be found. Use a fully-qualified name ("\\%s") or import the trait at the top of the file and use its short name.',
                $path,
                $line,
                $name,
                $name
            );
        } elseif ($t[0] !== T_NAME_FULLY_QUALIFIED && $t[0] !== T_STRING) {
            return;
        }

        $i++;

        // After a name: expect `,` (another trait), `{` (adaptations) or `;` (end).
        while ($i < $count) {
            $t = $tokens[$i];

            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i++;
                continue;
            }

            break;
        }

        if ($i < $count && $tokens[$i] === ',') {
            $i++;
            continue; // next trait name in the list
        }

        return; // `{` adaptations or `;` — done
    }
}
