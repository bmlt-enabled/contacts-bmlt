<?php

use ContactsBmlt\Helpers;
use ContactsBmlt\Settings;
use ContactsBmlt\Shortcode;

/**
 * Tests for the Contacts BMLT plugin.
 *
 * Root server responses are mocked with the pre_http_request filter, so no network access is needed.
 */
class TestContactsBmlt extends WP_UnitTestCase
{
    const ROOT_SERVER = 'https://bmlt.example.org/main_server';

    /** @var array Requested switchers, in order. */
    private $requests = [];

    /** @var array Service bodies returned by GetServiceBodies. */
    private $serviceBodies = [
        ['id' => '1', 'parent_id' => '0', 'name' => 'Test Region', 'url' => 'https://region.example.org', 'helpline' => '800-555-0100', 'description' => ''],
        ['id' => '2', 'parent_id' => '1', 'name' => 'Beta Area', 'url' => 'https://beta.example.org', 'helpline' => '800-555-0102', 'description' => ''],
        ['id' => '3', 'parent_id' => '1', 'name' => 'Alpha Area', 'url' => '', 'helpline' => '800-555-0103', 'description' => ''],
        ['id' => '4', 'parent_id' => '1', 'name' => 'No Contact Area', 'url' => '', 'helpline' => '', 'description' => ''],
        ['id' => '5', 'parent_id' => '0', 'name' => 'Other Region', 'url' => 'https://other.example.org', 'helpline' => '', 'description' => ''],
        ['id' => '6', 'parent_id' => '5', 'name' => 'Gamma Area', 'url' => '', 'helpline' => '800-555-0106', 'description' => ''],
    ];

    /** @var array Meetings returned by GetSearchResults. */
    private $meetings = [
        ['service_body_bigint' => '2', 'location_municipality' => 'North Augusta', 'location_province' => 'SC'],
        ['service_body_bigint' => '2', 'location_municipality' => 'Augusta', 'location_province' => 'GA'],
        ['service_body_bigint' => '2', 'location_municipality' => 'augusta,', 'location_province' => 'GA'],
        ['service_body_bigint' => '2', 'location_municipality' => 'Aiken', 'location_province' => 'S.C.'],
        ['service_body_bigint' => '2', 'location_municipality' => 'Online', 'location_province' => ''],
        ['service_body_bigint' => '2', 'location_municipality' => 'Toronto', 'location_province' => 'ontario'],
        ['service_body_bigint' => '2', 'location_municipality' => '', 'location_province' => 'GA'],
        ['service_body_bigint' => '3', 'location_municipality' => 'Evans', 'location_province' => 'GA'],
        ['service_body_bigint' => '3', 'location_municipality' => '<b>Bold</b>', 'location_province' => 'GA'],
    ];

    public function set_up()
    {
        parent::set_up();
        $this->requests = [];
        update_option('contacts_bmlt_options', $this->options());
        add_filter('pre_http_request', [$this, 'mockRootServer'], 10, 3);
    }

    public function tear_down()
    {
        remove_filter('pre_http_request', [$this, 'mockRootServer'], 10);
        remove_all_filters('contacts_bmlt_cache_ttl');
        $_POST = [];
        parent::tear_down();
    }

    /**
     * Mock root server, answering by switcher.
     */
    public function mockRootServer($preempt, $args, $url)
    {
        if (strpos($url, self::ROOT_SERVER) !== 0) {
            return $preempt;
        }
        parse_str((string) wp_parse_url($url, PHP_URL_QUERY), $query);
        $this->requests[] = $query['switcher'];
        switch ($query['switcher']) {
            case 'GetServiceBodies':
                $body = $this->serviceBodies;
                break;
            case 'GetSearchResults':
                $ids = Helpers::parseIds($query['services'] ?? '');
                $body = array_values(array_filter($this->meetings, function ($meeting) use ($ids) {
                    return in_array((int) $meeting['service_body_bigint'], $ids, true);
                }));
                break;
            case 'GetServerInfo':
                $body = [['version' => '3.1.0']];
                break;
            default:
                $body = [];
        }
        return [
            'headers' => [],
            'body' => wp_json_encode($body),
            'response' => ['code' => 200, 'message' => 'OK'],
            'cookies' => [],
            'filename' => null,
        ];
    }

    private function options(array $overrides = []): array
    {
        return array_merge([
            'root_server'                => self::ROOT_SERVER,
            'service_body_dropdown'      => 'All Service Bodies,000',
            'display_type_dropdown'      => 'table',
            'show_url_in_name_checkbox'  => '1',
            'show_tel_url_checkbox'      => '0',
            'show_full_url_checkbox'     => '0',
            'show_description_checkbox'  => '0',
            'show_email_checkbox'        => '0',
            'show_all_services_checkbox' => '0',
            'show_locations_dropdown'    => '0',
            'services_select'            => '',
            'group_by_state_checkbox'    => '0',
        ], $overrides);
    }

    private function render(array $atts = []): string
    {
        return (new Shortcode())->render($atts);
    }

    /**
     * Service body names in the rendered output, in order.
     */
    private function renderedNames(string $html): array
    {
        preg_match_all('/bmlt_simple_list_service_body_name_text">(?:<a [^>]*>)?([^<]+)/', $html, $matches);
        return $matches[1];
    }

    // -------------------------------------------------------------------------
    // Registration and errors
    // -------------------------------------------------------------------------

    public function test_shortcode_is_registered(): void
    {
        $this->assertTrue(shortcode_exists('contacts_bmlt'));
    }

    public function test_missing_root_server_returns_error(): void
    {
        update_option('contacts_bmlt_options', $this->options(['root_server' => '']));
        $this->assertStringContainsString('Root Server missing', $this->render());
    }

    public function test_missing_parent_and_services_returns_error(): void
    {
        update_option('contacts_bmlt_options', $this->options(['service_body_dropdown' => '']));
        $this->assertStringContainsString('Service Body missing', $this->render());
    }

    // -------------------------------------------------------------------------
    // Service body selection
    // -------------------------------------------------------------------------

    public function test_all_service_bodies_hides_those_without_contact_info(): void
    {
        $names = $this->renderedNames($this->render(['parent_id' => '000']));
        $this->assertSame(['Alpha Area', 'Beta Area', 'Gamma Area', 'Other Region', 'Test Region'], $names);
    }

    public function test_show_all_services_includes_those_without_contact_info(): void
    {
        $names = $this->renderedNames($this->render(['parent_id' => '000', 'show_all_services' => '1']));
        $this->assertContains('No Contact Area', $names);
    }

    public function test_parent_id_includes_parent_and_children(): void
    {
        $names = $this->renderedNames($this->render(['parent_id' => '1']));
        $this->assertSame(['Alpha Area', 'Beta Area', 'Test Region'], $names);
    }

    public function test_parent_id_accepts_a_list(): void
    {
        $names = $this->renderedNames($this->render(['parent_id' => '1, 5']));
        $this->assertSame(['Alpha Area', 'Beta Area', 'Gamma Area', 'Other Region', 'Test Region'], $names);
    }

    public function test_services_shows_only_listed_bodies_without_children(): void
    {
        $names = $this->renderedNames($this->render(['services' => '1,6']));
        $this->assertSame(['Gamma Area', 'Test Region'], $names);
    }

    public function test_services_overrides_parent_id(): void
    {
        $names = $this->renderedNames($this->render(['parent_id' => '1', 'services' => '6']));
        $this->assertSame(['Gamma Area'], $names);
    }

    public function test_services_setting_is_used_as_default(): void
    {
        update_option('contacts_bmlt_options', $this->options(['services_select' => '2,3']));
        $names = $this->renderedNames($this->render());
        $this->assertSame(['Alpha Area', 'Beta Area'], $names);
    }

    public function test_explicit_parent_id_ignores_services_setting(): void
    {
        update_option('contacts_bmlt_options', $this->options(['services_select' => '2']));
        $names = $this->renderedNames($this->render(['parent_id' => '5']));
        $this->assertSame(['Gamma Area', 'Other Region'], $names);
    }

    // -------------------------------------------------------------------------
    // Locations
    // -------------------------------------------------------------------------

    public function test_locations_flat_list_is_deduplicated_and_sorted(): void
    {
        $html = $this->render(['services' => '2', 'show_locations' => 'location_municipality']);
        $this->assertStringContainsString(
            '<span class="bmlt_simple_contacts_locations_text">Aiken, Augusta, North Augusta, Online, Toronto</span>',
            $html
        );
        $this->assertStringNotContainsString('bmlt_simple_contacts_locations_state', $html);
    }

    public function test_locations_grouped_by_state(): void
    {
        $html = $this->render(['services' => '2', 'show_locations' => 'location_municipality', 'group_by_state' => '1']);

        $expected = '<span class="bmlt_simple_contacts_locations_text">'
            . '<span class="bmlt_simple_contacts_locations_state"><span class="bmlt_simple_contacts_locations_state_name">Georgia:</span> Augusta</span><br>'
            . '<span class="bmlt_simple_contacts_locations_state"><span class="bmlt_simple_contacts_locations_state_name">Ontario:</span> Toronto</span><br>'
            . '<span class="bmlt_simple_contacts_locations_state"><span class="bmlt_simple_contacts_locations_state_name">South Carolina:</span> Aiken, North Augusta</span><br>'
            . '<span class="bmlt_simple_contacts_locations_state">Online</span>'
            . '</span>';
        $this->assertStringContainsString($expected, $html);
    }

    public function test_grouped_locations_only_include_their_own_service_body(): void
    {
        $html = $this->render(['services' => '2,3', 'show_locations' => 'location_municipality', 'group_by_state' => '1']);
        $this->assertMatchesRegularExpression('/Alpha Area.*Georgia:<\/span> [^<]*Evans/s', $html);
        $this->assertDoesNotMatchRegularExpression('/Beta Area.*Georgia:<\/span> [^<]*Evans/s', $html);
    }

    public function test_grouped_locations_are_escaped(): void
    {
        $html = $this->render(['services' => '3', 'show_locations' => 'location_municipality', 'group_by_state' => '1']);
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function test_state_name_lookup(): void
    {
        $helper = new Helpers();
        $this->assertSame('North Carolina', $helper->getStateName('NC'));
        $this->assertSame('North Carolina', $helper->getStateName(' n.c. '));
        $this->assertSame('Ontario', $helper->getStateName('ONTARIO'));
        $this->assertSame('', $helper->getStateName(''));
    }

    // -------------------------------------------------------------------------
    // Caching
    // -------------------------------------------------------------------------

    public function test_responses_are_cached(): void
    {
        $atts = ['services' => '2', 'show_locations' => 'location_municipality'];
        $first = $this->render($atts);
        $this->assertSame(['GetServiceBodies', 'GetSearchResults'], $this->requests);

        $this->assertSame($first, $this->render($atts));
        $this->assertCount(2, $this->requests);
    }

    public function test_clear_cache_forces_refetch(): void
    {
        $this->render();
        Helpers::clearCache();
        $this->render();
        $this->assertSame(['GetServiceBodies', 'GetServiceBodies'], $this->requests);
    }

    public function test_cache_can_be_disabled_with_filter(): void
    {
        add_filter('contacts_bmlt_cache_ttl', '__return_zero');
        $this->render();
        $this->render();
        $this->assertSame(['GetServiceBodies', 'GetServiceBodies'], $this->requests);
    }

    public function test_errors_are_not_cached(): void
    {
        $serviceBodies = $this->serviceBodies;
        $this->serviceBodies = [];
        $this->assertStringContainsString('Unable to fetch service bodies', $this->render());

        $this->serviceBodies = $serviceBodies;
        $this->assertSame(['Alpha Area', 'Beta Area', 'Gamma Area', 'Other Region', 'Test Region'], $this->renderedNames($this->render()));
    }

    public function test_root_server_test_is_not_cached(): void
    {
        $helper = new Helpers();
        $this->assertSame('3.1.0', $helper->testRootServer(self::ROOT_SERVER));
        $this->assertSame('3.1.0', $helper->testRootServer(self::ROOT_SERVER));
        $this->assertSame(['GetServerInfo', 'GetServerInfo'], $this->requests);
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public function test_settings_save_sanitizes_new_options_and_clears_cache(): void
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->render();

        $_POST = [
            'contactsbmltsave'        => '1',
            '_wpnonce'                => wp_create_nonce('contactsbmltupdate-options'),
            'root_server'             => self::ROOT_SERVER,
            'service_body_dropdown'   => 'Test Region,1',
            'display_type_dropdown'   => 'table',
            'services_select'         => ['6', '2', 'abc', '6', '0'],
            'group_by_state_checkbox' => '1',
        ];
        ob_start();
        (new Settings())->adminOptionsPage();
        $page = ob_get_clean();

        $options = get_option('contacts_bmlt_options');
        $this->assertSame('6,2', $options['services_select']);
        $this->assertSame('1', $options['group_by_state_checkbox']);
        $this->assertStringContainsString('<option selected="selected" value="6">Gamma Area (6)</option>', $page);

        // The settings page fetched service bodies again after save, rather than using the cached response
        $this->assertSame(['GetServiceBodies', 'GetServerInfo', 'GetServiceBodies'], $this->requests);
    }
}
