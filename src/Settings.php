<?php

namespace ContactsBmlt;

require_once 'Helpers.php';

/**
 * Class Settings
 * @package ContactsBmlt
 */
class Settings
{
    /**
     * Instance of the Helpers class.
     *
     * @var Helpers
     */
    private $helper;
    public $optionsName = 'contacts_bmlt_options';
    public $options = [];

    /**
     * Constructor for the Settings class.
     */
    public function __construct()
    {
        $this->getOptions();
        $this->helper = new Helpers();
        add_action("admin_notices", [$this, "isRootServerMissing"]);
    }

    /**
     * Create the admin menu for the plugin.
     *
     * This function adds an options page to the WordPress admin menu and registers a plugin action link.
     *
     * @param string $baseFile The base file of the plugin.
     * @return void
     */
    public function createMenu(string $baseFile): void
    {
        add_options_page(
            'Contacts BMLT', // Page Title
            'Contacts BMLT', // Menu Title
            'activate_plugins',    // Capability
            'contacts-bmlt', // Menu Slug
            [$this, 'adminOptionsPage'] // Callback function to display the page content
        );

        add_filter('plugin_action_links_' . $baseFile, [$this, 'filterPluginActions'], 10, 2);
    }

    /**
     * Display the admin options page and handle form submissions.
     *
     * This function handles the display of the admin options page and processes form submissions.
     *
     * @return void
     */
    public function adminOptionsPage(): void
    {
        if (!empty($_POST['contactsbmltsave']) && wp_verify_nonce($_POST['_wpnonce'], 'contactsbmltupdate-options')) {
            $this->updateAdminOptions();
            $this->printSuccessMessage();
        }
        $this->printAdminForm();
    }


    /**
     * Update the admin options based on POST data.
     *
     * This function updates the plugin's options based on the POST data received from the admin settings form.
     *
     * @return void
     */
    private function updateAdminOptions(): void
    {
        $this->options['root_server'] = isset($_POST['root_server']) ? esc_url_raw($_POST['root_server']) : '';
        $this->options['service_body_dropdown'] = isset($_POST['service_body_dropdown']) ? sanitize_text_field($_POST['service_body_dropdown']) : '';
        $this->options['display_type_dropdown'] = isset($_POST['display_type_dropdown']) ? sanitize_text_field($_POST['display_type_dropdown']) : '';
        $this->options['show_url_in_name_checkbox'] = isset($_POST['show_url_in_name_checkbox']) ? sanitize_text_field($_POST['show_url_in_name_checkbox']) : '';
        $this->options['show_tel_url_checkbox'] = isset($_POST['show_tel_url_checkbox']) ? sanitize_text_field($_POST['show_tel_url_checkbox']) : '';
        $this->options['show_full_url_checkbox'] = isset($_POST['show_full_url_checkbox']) ? sanitize_text_field($_POST['show_full_url_checkbox']) : '';
        $this->options['show_description_checkbox'] = isset($_POST['show_description_checkbox']) ? sanitize_text_field($_POST['show_description_checkbox']) : '';
        $this->options['show_email_checkbox'] = isset($_POST['show_email_checkbox']) ? sanitize_text_field($_POST['show_email_checkbox']) : '';
        $this->options['show_all_services_checkbox'] = isset($_POST['show_all_services_checkbox']) ? sanitize_text_field($_POST['show_all_services_checkbox']) : '';
        $this->options['show_locations_dropdown'] = isset($_POST['show_locations_dropdown']) ? sanitize_text_field($_POST['show_locations_dropdown']) : '';
        $this->saveAdminOptions();
    }

    /**
     * Display a success message.
     *
     * This function outputs a success message indicating that changes were successfully saved.
     *
     * @return void
     */
    private function printSuccessMessage(): void
    {
        echo '<div class="updated"><p>Success! Your changes were successfully saved!</p></div>';
    }

    /**
     * Get the connection status to the BMLT Root Server.
     *
     * This function tests the connection to the BMLT Root Server and returns the status and a message.
     *
     * @return array An associative array with 'msg' and 'status' keys indicating the status and message.
     */
    private function getConnectionStatus(): array
    {
        $this_connected = $this->helper->testRootServer($this->options['root_server']);
        return $this_connected ? [
            'msg' => "<span style='color: #00AD00;'><div style='font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-smiley'></div>Version {$this_connected}</span>",
            'status' => true
        ] : [
            'msg' => "<p><div style='color: #f00;font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-no'></div><span style='color: #f00;'>Connection to Root Server Failed.  Check spelling or try again.  If you are certain spelling is correct, Root Server could be down.</span></p>",
            'status' => false
        ];
    }

    /**
     * Display the admin settings form for the plugin.
     *
     * This function generates and displays the admin settings form for the plugin.
     *
     * @return void
     */
    private function printAdminForm(): void
    {
        $connectionStatus = $this->getConnectionStatus();
        $serviceBodies = $this->helper->getServiceBodies($this->options['root_server']);
        ?>
        <div class="wrap">
            <h2>Contacts BMLT</h2>
            <form style="display:inline!important;" method="POST" id="contacts_bmlt_options" name="contacts_bmlt_options">
                <?php wp_nonce_field('contactsbmltupdate-options'); ?>

                <!-- Connection Status Display -->
                <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                    <h3>BMLT Root Server URL</h3>
                    <p>Example: https://domain.org/main_server</p>
                    <ul>
                        <li>
                            <label for="root_server">Default Root Server: </label>
                            <input id="root_server" type="text" size="50" name="root_server" value="<?php echo esc_attr($this->options['root_server']); ?>" />
                            <?php echo $connectionStatus['msg']; ?>
                        </li>
                    </ul>
                </div>

                <!-- Service Body Section -->
                <div class="postbox" style="padding: 0 15px;">
                    <h3>Service Body Parent</h3>
                    <p>This service body will be used as the parent, otherwise all service bodies from server will be used.</p>
                    <ul>
                        <li>
                            <label for="service_body_dropdown">Default Service Body Parent: </label>
                            <select id="service_body_dropdown" name="service_body_dropdown" class="contacts_bmlt_service_body_select">
                                <?php
                                if ($connectionStatus['status']) {
                                    $unique_areas = $this->helper->getParentServiceBodies($serviceBodies);
                                    $current_selected = explode(',', esc_html($this->options['service_body_dropdown']));
                                    foreach ($unique_areas as $unique_area) {
                                        $area_data = explode(',', $unique_area);
                                        $area_name = $this->helper->arraySafeGet($area_data, 0);
                                        $area_id = $this->helper->arraySafeGet($area_data, 1);
                                        $option_description = $area_name . " (" . $area_id . ")";
                                        $selected = ($area_id == $this->helper->arraySafeGet($current_selected, 1)) ? 'selected="selected"' : '';
                                        echo "<option $selected value='$unique_area'>$option_description</option>";
                                    }
                                } else {
                                    echo "<option selected='selected' value='{$this->options['service_body_dropdown']}'>Not Connected - Can not get Service Bodies</option>";
                                }
                                ?>
                            </select>
                        </li>
                    </ul>
                </div>

                <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                    <h3>Attribute Options</h3>
                    <ul>
                        <li>
                            <label for="display_type_dropdown">Display Type: </label>
                            <select style="display:inline;" id="display_type_dropdown" name="display_type_dropdown"  class="display_type_select">
                                <?php if ($this->options['display_type_dropdown'] == 'table') { ?>
                                    <option selected="selected" value="table">HTML (bmlt table)</option>
                                    <option value="block">HTML (bmlt block)</option>
                                    <?php
                                } else { ?>
                                    <option value="table">HTML (bmlt table)</option>
                                    <option selected="selected" value="block">HTML (bmlt block)</option>
                                    <?php
                                }
                                ?>
                            </select>
                        </li>
                        <li>
                            <input type="checkbox" id="show_url_in_name_checkbox" name="show_url_in_name_checkbox" value="1" <?php echo ($this->options['show_url_in_name_checkbox'] == "1" ? "checked" : "") ?>/>
                            <label for="show_url_in_name_checkbox">Add URL link to service body name.</label>
                        </li>
                        <li>
                            <input type="checkbox" id="show_tel_url_checkbox" name="show_tel_url_checkbox" value="1" <?php echo ($this->options['show_tel_url_checkbox'] == "1" ? "checked" : "") ?>/>
                            <label for="show_tel_url_checkbox">Add tel link to phone number.</label>
                        </li>
                        <li>
                            <input type="checkbox" id="show_full_url_checkbox" name="show_full_url_checkbox" value="1" <?php echo ($this->options['show_full_url_checkbox'] == "1" ? "checked" : "") ?>/>
                            <label for="show_full_url_checkbox">Show separate column displaying URL.</label>
                        </li>
                        <li>
                            <input type="checkbox" id="show_description_checkbox" name="show_description_checkbox" value="1" <?php echo ($this->options['show_description_checkbox'] == "1" ? "checked" : "") ?>/>
                            <label for="show_description_checkbox">Show Description</label>
                        </li>
                        <li>
                            <input type="checkbox" id="show_email_checkbox" name="show_email_checkbox" value="1" <?php echo ($this->options['show_email_checkbox'] == "1" ? "checked" : "") ?>/>
                            <label for="show_email_checkbox">Show Email (note will only work if server is setup to display email)</label>
                        </li>
                        <li>
                            <input type="checkbox" id="show_all_services_checkbox" name="show_all_services_checkbox" value="1" <?php echo ($this->options['show_all_services_checkbox'] == "1" ? "checked" : "") ?>/>
                            <label for="show_all_services_checkbox">Show All Services (This will display all service bodies regardless of whether they have their phone or URL field filled out)</label>
                        </li>
                        <li>
                            <label for="show_locations_dropdown">Show Locations: </label>
                            <select style="display:inline;" id="show_locations_dropdown" name="show_locations_dropdown" class="show_locations_select">
                                <?php
                                $options = [
                                    'location_municipality'     => 'City',
                                    'location_city_subsection'  => 'City Subsection',
                                    'location_sub_province'     => 'County',
                                    'location_neighborhood'     => 'Neighborhood',
                                    '0'                         => 'NONE'
                                ];

                                $selectedOption = $this->options['show_locations_dropdown'];

                                foreach ($options as $value => $label) {
                                    $isSelected = ($value == $selectedOption) ? 'selected="selected"' : '';
                                    echo "<option value='{$value}' {$isSelected}>{$label}</option>";
                                }
                                ?>
                            </select>
                            <label for="show_locations_dropdown"> (This will display a list of locations below the service body name)</label>
                        </li>
                    </ul>
                </div>
                <input type="submit" value="SAVE CHANGES" name="contactsbmltsave" class="button-primary" />
            </form>
            <br/><br/>
            <?php include 'partials/_instructions.php'; ?>
        </div>
        <?php
    }


    /**
     * Filter the plugin action links displayed on the Plugins page.
     *
     * This function adds a "Settings" link to the plugin's action links on the Plugins page in the WordPress admin.
     *
     * @param array $links The array of action links.
     * @return array The modified array of action links.
     */
    public function filterPluginActions(array $links): array
    {
        // If your plugin is under a different top-level menu than Settings (IE - you changed the function above to something other than add_options_page)
        // Then you're going to want to change options-general.php below to the name of your top-level page
        $settings_link = '<a href="options-general.php?page=contacts-bmlt">Settings</a>';
        array_unshift($links, $settings_link);
        // before other links
        return $links;
    }

    /**
     * Retrieves and initializes plugin options.
     *
     * This function retrieves the plugin options from WordPress options and initializes
     * default values if the options do not exist.
     *
     * @return void
     */
    public function getOptions(): void
    {
        // Don't forget to set up the default options
        if (!$theOptions = get_option($this->optionsName)) {
            $theOptions = array(
                'root_server'                => '',
                'service_body_dropdown'      => '000',
                'display_type_dropdown'      => 'block',
                'show_url_in_name_checkbox'  => '1',
                'show_tel_url_checkbox'      => '0',
                'show_full_url_checkbox'     => '0',
                'show_description_checkbox'  => '0',
                'show_email_checkbox'        => '0',
                'show_all_services_checkbox' => '0',
                'show_locations_dropdown'    => '0'
            );
            update_option($this->optionsName, $theOptions);
        }
        $this->options = $theOptions;
        $this->options['root_server'] = untrailingslashit(preg_replace('/^(.*)\/(.*php)$/', '$1', $this->options['root_server']));
    }

    /**
     * Saves the admin options for the plugin.
     *
     * This function updates the BMLT Root Server option and saves it in the WordPress options.
     *
     * @return void
     */
    public function saveAdminOptions(): void
    {
        $this->options['root_server'] = untrailingslashit(preg_replace('/^(.*)\/(.*php)$/', '$1', $this->options['root_server']));
        update_option($this->optionsName, $this->options);
        return;
    }

    /**
     * Checks if the BMLT Root Server is missing in the plugin settings.
     *
     * @return void
     */
    public function isRootServerMissing(): void
    {
        $root_server = $this->options['root_server'];
        if (empty($root_server)) {
            $url = esc_url(admin_url('options-general.php?page=contacts-bmlt'));
            echo '<div id="message" class="error">';
            echo '<p>Missing BMLT Root Server in settings for Contacts BMLT.</p>';
            echo "<p><a href='{$url}'>Contacts BMLT Settings</a></p>";
            echo '</div>';
        }
    }
}
