<?php

namespace Gantry\Tests\PHP83\Component\Admin;

use Gantry\Component\Admin\ModuleEditorAccess;
use PHPUnit\Framework\TestCase;

/**
 * @package Gantry\Tests\PHP83\Component\Admin
 */
class ModuleEditorAccessTest extends TestCase
{
    public function testAllowedViews()
    {
        $cases = [
            ['particle', 'json', 'GET'],
            ['particle/foo', 'json', 'POST'],
            ['particle/foo/validate', 'json', 'POST'],
            ['fontpicker', 'json', 'GET'],
            ['icons', 'json', 'GET'],
            ['filepicker', 'json', 'POST'],
            ['filepicker/upload/abc', 'json', 'POST'],
            ['filepicker/display/images/a.png', 'json', 'GET'],
        ];

        foreach ($cases as $case) {
            $this->assertTrue(
                ModuleEditorAccess::isAllowed($case[0], $case[1], $case[2]),
                sprintf('Expected allowed: %s %s %s', $case[2], $case[0], $case[1])
            );
        }
    }

    public function testDeniedViews()
    {
        $cases = [
            ['particle', 'html', 'GET'],
            ['configurations', 'json', 'GET'],
            ['layouts', 'json', 'GET'],
            ['menu', 'json', 'GET'],
            ['themes', 'json', 'GET'],
            ['particle/foo', 'json', 'DELETE'],
            ['particle/foo', 'json', 'PUT'],
            ['particle/a/b/c', 'json', 'POST'],
            ['filepicker', 'json', 'DELETE'],
            ['particle/../x', 'json', 'GET'],
            ['particle\\foo', 'json', 'GET'],
            ['', 'json', 'GET'],
        ];

        foreach ($cases as $case) {
            $this->assertFalse(
                ModuleEditorAccess::isAllowed($case[0], $case[1], $case[2]),
                sprintf('Expected denied: %s %s %s', $case[2], $case[0], $case[1])
            );
        }
    }

    public function testEffectiveMethodPostBodyWinsOverHeader()
    {
        $method = ModuleEditorAccess::effectiveMethod('POST', 'PUT', 'DELETE');
        $this->assertSame('DELETE', $method);

        $this->assertFalse(
            ModuleEditorAccess::isAllowed('filepicker', 'json', $method)
        );
    }

    public function testEffectiveMethodHeaderOverrideWhenNoPostMethod()
    {
        $method = ModuleEditorAccess::effectiveMethod('POST', 'PUT', '');
        $this->assertSame('PUT', $method);

        $this->assertFalse(
            ModuleEditorAccess::isAllowed('particle/foo', 'json', $method)
        );
    }

    public function testEffectiveMethodNoOverrideOnGet()
    {
        $method = ModuleEditorAccess::effectiveMethod('GET', 'DELETE', 'PUT');
        $this->assertSame('GET', $method);
    }

    public function testEffectiveMethodPlainPost()
    {
        $method = ModuleEditorAccess::effectiveMethod('POST', '', '');
        $this->assertSame('POST', $method);
    }

    public function testFormatCaseInsensitive()
    {
        $this->assertTrue(ModuleEditorAccess::isAllowed('icons', 'JSON', 'GET'));
        $this->assertTrue(ModuleEditorAccess::isAllowed('icons', 'Json', 'POST'));
    }

    public function testFilepickerAllowsGetAndPostOnly()
    {
        $this->assertTrue(ModuleEditorAccess::isAllowed('filepicker/subfolder', 'json', 'GET'));
        $this->assertTrue(ModuleEditorAccess::isAllowed('filepicker/download/x', 'json', 'POST'));
        $this->assertFalse(ModuleEditorAccess::isAllowed('filepicker/x', 'json', 'PATCH'));
    }

    public function testFilepickerTraversalViewDeniedByGate()
    {
        foreach (['filepicker/display/images/../configuration.php', 'filepicker/display/....//configuration.php'] as $view) {
            $this->assertFalse(ModuleEditorAccess::isAllowed($view, 'json', 'GET'));
        }
    }

    public function testGateDenialIsHttp403()
    {
        $e = ModuleEditorAccess::forbidden('nope');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertSame(403, $e->getCode());
        $this->assertSame('nope', $e->getMessage());
    }
}
