<?php
/**
 * Copyright 2026 Matthew C. Murphy https://MatthewxMurphy.com
 * Product/support https://builtnotbegged.com | SPDX-License-Identifier: MIT
 * Targeted synthetic PHP 8.4 parser/runtime checks; no CMS bootstrap or source rewriting.
 */
declare(strict_types=1);

namespace GantryPhp84Native;

if (PHP_SAPI !== 'cli' || PHP_VERSION_ID < 80400 || PHP_VERSION_ID >= 80500) {
    fwrite(STDERR, "This test requires actual PHP 8.4 CLI.\n");
    exit(1);
}

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message): never {
    throw new \ErrorException($message, 0, $severity);
});
require dirname(__DIR__, 2) . '/bin/apply-php84-nullable-patches';

interface A {}
interface B {}
interface C {}
final class Both implements A, B {}
final class OnlyA implements A {}
final class OnlyC implements C {}
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class First {}
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Second {}

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new \RuntimeException($label);
    }
    $checks++;
}

$cases = [
    'intersection' => [
        'function probe(A&B $value = null) { return $value; }',
        'function probe((A&B)|null $value = null) { return $value; }',
        1, 'intersection', false,
    ],
    'intersection reference' => [
        'function probe(A&B &$value = null) { $value = null; return $value; }',
        'function probe((A&B)|null &$value = null) { $value = null; return $value; }',
        1, 'intersection', true,
    ],
    'DNF union' => [
        'function probe((A&B)|C $value = null) { return $value; }',
        'function probe((A&B)|C|null $value = null) { return $value; }',
        1, 'dnf', false,
    ],
    'explicit nullable intersection' => [
        'function probe((A&B)|null $value = null) { return $value; }',
        'function probe((A&B)|null $value = null) { return $value; }',
        0, 'intersection', false,
    ],
    'explicit nullable DNF' => [
        'function probe((A&B)|C|null $value = null) { return $value; }',
        'function probe((A&B)|C|null $value = null) { return $value; }',
        0, 'dnf', false,
    ],
    'grouped attributes and reference' => [
        'function probe(#[First, Second] A /* keep */ & B &$value = null) { $value = null; return $value; }',
        'function probe(#[First, Second] (A /* keep */ & B)|null &$value = null) { $value = null; return $value; }',
        1, 'attributes', true,
    ],
    'grouped attributes and scalar' => [
        'function probe(#[First, Second] array $value = null) { return $value; }',
        'function probe(#[First, Second] ?array $value = null) { return $value; }',
        1, 'array', false,
    ],
    'scalar union' => [
        'function probe(int|string $value = null) { return $value; }',
        'function probe(int|string|null $value = null) { return $value; }',
        1, 'scalar', false,
    ],
];

try {
    \gantry_php84_self_test();
    check(true, 'Existing golden self-tests');
    $index = 0;
    foreach ($cases as $label => [$input, $expected, $edits, $kind, $reference]) {
        [$output, $count] = \gantry_php84_fix_code("<?php\n" . $input);
        check($output === "<?php\n" . $expected && $count === $edits, $label . ': preserved source and edit count');
        token_get_all($output, TOKEN_PARSE);
        check(true, $label . ': actual PHP 8.4 compilation');
        [$again, $secondCount] = \gantry_php84_fix_code($output);
        check($again === $output && $secondCount === 0, $label . ': idempotence');
        $namespace = 'GantryPhp84Case' . ++$index;
        $imports = 'use GantryPhp84Native\A; use GantryPhp84Native\B; use GantryPhp84Native\C; '
            . 'use GantryPhp84Native\First; use GantryPhp84Native\Second; ';
        eval('namespace ' . $namespace . '; ' . $imports . substr($output, 6));
        $function = $namespace . '\probe';
        $reflection = new \ReflectionFunction($function);
        $parameter = $reflection->getParameters()[0];
        check($parameter->allowsNull() && $parameter->isOptional() && $parameter->getDefaultValue() === null, $label . ': real null contract');
        check($parameter->isPassedByReference() === $reference, $label . ': reference semantics');
        check($function() === null, $label . ': omitted argument');
        $value = $kind === 'array' ? ['kept' => true] : ($kind === 'scalar' ? 42 : new Both());
        $original = $value;
        $returned = $function($value);
        check($reference ? ($value === null && $returned === null) : ($returned === $original && $value === $original), $label . ': accepted value/body semantics');
        if ($kind === 'dnf') {
            $c = new OnlyC();
            check($function($c) === $c, $label . ': DNF alternative');
        }
        if ($kind === 'attributes' || $kind === 'array') {
            check(count($parameter->getAttributes()) === 2, $label . ': both attributes preserved');
        }
        try {
            $invalid = new OnlyA();
            $function($invalid);
            check(false, $label . ': rejected type accepted');
        } catch (\TypeError) {
            check(true, $label . ': actual type rejection');
        }
    }

    $promotion = '<?php class Box { public function __construct(public array $value = null) {} }';
    [$promotion, $count] = \gantry_php84_fix_code($promotion);
    token_get_all($promotion, TOKEN_PARSE);
    eval('namespace GantryPhp84Promotion; ' . substr($promotion, 6));
    check($count === 1 && (new \GantryPhp84Promotion\Box())->value === null, 'Promoted nullable property compiles/defaults');
    check((new \GantryPhp84Promotion\Box(['preserved']))->value === ['preserved'], 'Promoted property retains data');

    echo json_encode(['ok' => true, 'php' => PHP_VERSION, 'cases' => count($cases) + 1, 'checks' => $checks], JSON_THROW_ON_ERROR) . "\n";
} catch (\Throwable $error) {
    fwrite(STDERR, get_class($error) . ': ' . $error->getMessage() . "\n");
    exit(1);
} finally {
    restore_error_handler();
}
