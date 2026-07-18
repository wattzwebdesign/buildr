<?php

namespace Buildr\Tests;

use Buildr\DynamicTags\TagRegistry;
use Buildr\Models\SiteSetting;
use Illuminate\Support\Carbon;

class DynamicTagTest extends TestCase
{
    private function registry(): TagRegistry
    {
        return app(TagRegistry::class);
    }

    public function test_date_tag_accepts_php_format_strings(): void
    {
        Carbon::setTestNow('2026-07-18 10:00:00');

        $this->assertSame('2026', $this->registry()->resolve('{{date:Y}}'));
        $this->assertSame('July 18, 2026', $this->registry()->resolve('{{date:F j, Y}}'));
        $this->assertSame('Saturday', $this->registry()->resolve('{{date:l}}'));
        $this->assertSame('© 2026', $this->registry()->resolve('© {{year}}'));

        Carbon::setTestNow();
    }

    public function test_site_tags_resolve_from_settings(): void
    {
        SiteSetting::set('phone', '(410) 555-0114');

        $this->assertSame(
            'Call (410) 555-0114 now',
            $this->registry()->resolve('Call {{site.phone}} now')
        );
        $this->assertSame('tel:4105550114', $this->registry()->resolve('{{site.phone_link}}'));
    }

    public function test_unknown_tags_pass_through_untouched(): void
    {
        $this->assertSame('{{nope.missing}}', $this->registry()->resolve('{{nope.missing}}'));
    }

    public function test_custom_tags_register_via_config(): void
    {
        $registry = $this->registry();
        $registry->register('review_count', fn () => 42);

        $this->assertSame('42 reviews', $registry->resolve('{{review_count}} reviews'));
    }
}
