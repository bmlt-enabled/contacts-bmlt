<?php
/*
Plugin Name: Contacts BMLT
Plugin URI: https://wordpress.org/plugins/contacts-bmlt/
Author: bmlt-enabled
Description: This plugin returns helpline and website info for service bodies Simply add [contacts_bmlt] shortcode to your page and set shortcode attributes accordingly. Required attributes are root_server.
Version: 1.0.0
Install: Drop this directory into the "wp-content/plugins/" directory and activate it.
*/
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // die('Sorry, but you cannot access this page directly.');
}

if (!class_exists("contactsBmlt")) {
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
    class contactsBmlt
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:enable Squiz.Classes.ValidClassName.NotCamelCaps
    {
        public $optionsName = 'contacts_bmlt_options';
        public $options = array();
        public function __construct()
        {
            $this->getOptions();
            if (is_admin()) {
                // Back end
                add_action("admin_notices", array(&$this, "isRootServerMissing"));
                add_action("admin_enqueue_scripts", array(&$this, "enqueueBackendFiles"), 500);
                add_action("admin_menu", array(&$this, "adminMenuLink"));
            } else {
                // Front end
                add_action("wp_enqueue_scripts", array(&$this, "enqueueFrontendFiles"));
                add_shortcode('contacts_bmlt', array(
                    &$this,
                    "contactsBmltMain"
                ));
            }
            // Content filter
            add_filter('the_content', array(
                &$this,
                'filterContent'
            ), 0);
        }

        public function isRootServerMissing()
        {
            $root_server = $this->options['root_server'];
            if ($root_server == '') {
                echo '<div id="message" class="error"><p>Missing BMLT Root Server in settings for Contacts BMLT.</p>';
                $url = admin_url('options-general.php?page=contacts-bmlt.php');
                echo "<p><a href='$url'>Contacts BMLT Settings</a></p>";
                echo '</div>';
            }
            add_action("admin_notices", array(
                &$this,
                "clearAdminMessage"
            ));
        }

        public function clearAdminMessage()
        {
            remove_action("admin_notices", array(
                &$this,
                "isRootServerMissing"
            ));
        }

        public function contactsBmlt()
        {
            $this->__construct();
        }

        public function filterContent($content)
        {
            return $content;
        }

        /**
         * @param $hook
         */
        public function enqueueBackendFiles($hook)
        {
            if ($hook == 'settings_page_contacts-bmlt') {
                wp_enqueue_style('contacts-bmlt-admin-ui-css', plugins_url('css/redmond/jquery-ui.css', __FILE__), false, '1.11.4', false);
                wp_enqueue_style("chosen", plugin_dir_url(__FILE__) . "css/chosen.min.css", false, "1.2", 'all');
                wp_enqueue_script("chosen", plugin_dir_url(__FILE__) . "js/chosen.jquery.min.js", array('jquery'), "1.2", true);
                wp_enqueue_script('contacts-bmlt-admin', plugins_url('js/contacts_bmlt_admin.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . "js/contacts_bmlt_admin.js"), false);
                wp_enqueue_script('common');
                wp_enqueue_script('jquery-ui-accordion');
            }
        }

        public function enqueueFrontendFiles($hook)
        {
            wp_enqueue_style('contacts-bmlt', plugin_dir_url(__FILE__) . 'css/contacts_bmlt.css', false, '1.20	', 'all');
        }

        public function testRootServer($root_server)
        {
            $args = array(
                'timeout' => '10',
                'headers' => array(
                    'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +ContactsBMLT'
                )
            );
            $results = wp_remote_get("$root_server/client_interface/serverInfo.xml", $args);
            $httpcode = wp_remote_retrieve_response_code($results);
            $response_message = wp_remote_retrieve_response_message($results);
            if ($httpcode != 200 && $httpcode != 302 && $httpcode != 304 && ! empty($response_message)) {
                //echo '<p>Problem Connecting to BMLT Root Server: ' . $root_server . '</p>';
                return false;
            };
            $results = simplexml_load_string(wp_remote_retrieve_body($results));
            $results = json_encode($results);
            $results = json_decode($results, true);
            $results = $results['serverVersion']['readableString'];
            return $results;
        }

        public function contactsBmltMain($atts, $content = null)
        {
            extract(shortcode_atts(array(
                "root_server"       => '',
                'display_type'      => '',
                'parent_id'         => '',
                'show_url_in_name'  => '',
                'show_tel_url'      => '',
                'show_full_url'     => '',
                'show_description'  => '',
                'show_email'        => ''
            ), $atts));

            $services_data_dropdown   = explode(',', $this->options['service_body_dropdown']);
            $services_dropdown    = $services_data_dropdown[1];

            $parent_id            = ($parent_id        != '' ? $parent_id        : $services_dropdown);
            $root_server          = ($root_server      != '' ? $root_server      : $this->options['root_server']);
            $display_type         = ($display_type     != '' ? $display_type     : $this->options['display_type_dropdown']);
            $show_url_in_name     = ($show_url_in_name != '' ? $show_url_in_name : $this->options['show_url_in_name_checkbox']);
            $show_tel_url         = ($show_tel_url     != '' ? $show_tel_url     : $this->options['show_tel_url_checkbox']);
            $show_full_url        = ($show_full_url    != '' ? $show_full_url    : $this->options['show_full_url_checkbox']);
            $show_description     = ($show_description != '' ? $show_description : $this->options['show_description_checkbox']);
            $show_email           = ($show_email       != '' ? $show_email       : $this->options['show_email_checkbox']);

            if ($root_server == '') {
                return '<p><strong>Contacts BMLT Error: Root Server missing. Please Verify you have entered a Root Server using the \'root_server\' shortcode attribute</strong></p>';
            }

            $output = '';
            $css_um = $this->options['custom_css_um'];

            $output .= "<style type='text/css'>$css_um</style>";

            $service_body_results = $this->getServiceBodiesJson($root_server, $parent_id);

            if ($display_type != '' && $display_type == 'table') {
                $output .= '<div id="contacts_bmlt_div">';
                $output .= $this->serviceBodiesJson2Html($service_body_results, false, $show_description, $show_url_in_name, $show_tel_url, $show_email, $show_full_url);
                $output .= '</div>';
            }

            if ($display_type != '' && $display_type == 'block') {
                $output .= '<div id="contacts_bmlt_div">';
                $output .= $this->serviceBodiesJson2Html($service_body_results, true, $show_description, $show_url_in_name, $show_tel_url, $show_email, $show_full_url);
                $output .= '</div>';
            }

            return $output;
        }

        /**
         * @desc Adds the options sub-panel
         */

        public function adminMenuLink()
        {
            // If you change this from add_options_page, MAKE SURE you change the filterPluginActions function (below) to
            // reflect the page file name (i.e. - options-general.php) of the page your plugin is under!
            add_options_page('Contacts BMLT', 'Contacts BMLT', 'activate_plugins', basename(__FILE__), array(
                &$this,
                'adminOptionsPage'
            ));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(
                &$this,
                'filterPluginActions'
            ), 10, 2);
        }
        /**
         * Adds settings/options page
         */
        public function adminOptionsPage()
        {
            if (!isset($_POST['contactsbmltsave'])) {
                $_POST['contactsbmltsave'] = false;
            }
            if ($_POST['contactsbmltsave']) {
                if (!wp_verify_nonce($_POST['_wpnonce'], 'contactsbmltupdate-options')) {
                    die('Whoops! There was a problem with the data you posted. Please go back and try again.');
                }
                $this->options['root_server']               = esc_url_raw($_POST['root_server']);
                $this->options['service_body_dropdown']     = sanitize_text_field($_POST['service_body_dropdown']);
                $this->options['display_type_dropdown']     = sanitize_text_field($_POST['display_type_dropdown']);
                $this->options['show_url_in_name_checkbox'] = sanitize_text_field($_POST['show_url_in_name_checkbox']);
                $this->options['show_tel_url_checkbox']     = sanitize_text_field($_POST['show_tel_url_checkbox']);
                $this->options['show_full_url_checkbox']    = sanitize_text_field($_POST['show_full_url_checkbox']);
                $this->options['show_description_checkbox'] = sanitize_text_field($_POST['show_description_checkbox']);
                $this->options['show_email_checkbox']       = sanitize_text_field($_POST['show_email_checkbox']);
                $this->options['custom_css_um']             = $_POST['custom_css_um'];

                $this->saveAdminOptions();
                echo '<div class="updated"><p>Success! Your changes were successfully saved!</p></div>';
            }
            ?>
            <div class="wrap">
                <h2>Contacts BMLT</h2>
                <form style="display:inline!important;" method="POST" id="contacts_bmlt_options" name="contacts_bmlt_options">
                    <?php wp_nonce_field('contactsbmltupdate-options'); ?>
                    <?php $this_connected = $this->testRootServer($this->options['root_server']); ?>
                    <?php $connect = "<p><div style='color: #f00;font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-no'></div><span style='color: #f00;'>Connection to Root Server Failed.  Check spelling or try again.  If you are certain spelling is correct, Root Server could be down.</span></p>"; ?>
                    <?php if ($this_connected != false) { ?>
                        <?php $connect = "<span style='color: #00AD00;'><div style='font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-smiley'></div>Version ".$this_connected."</span>"?>
                        <?php $this_connected = true; ?>
                    <?php } ?>
                    <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                        <h3>BMLT Root Server URL</h3>
                        <p>Example: https://domain.org/main_server</p>
                        <ul>
                            <li>
                                <label for="root_server">Default Root Server: </label>
                                <input id="root_server" type="text" size="50" name="root_server" value="<?php echo $this->options['root_server']; ?>" /> <?php echo $connect; ?>
                            </li>
                        </ul>
                    </div>
                    <div style="padding: 0 15px;" class="postbox">
                        <h3>Service Body Parent</h3>
                        <p>This service body will be used as the parent, otherwise all service bodies from server will be used.</p>
                        <ul>
                            <li>
                                <label for="service_body_dropdown">Default Service Body Parent: </label>
                                <select style="display:inline;" id="service_body_dropdown" name="service_body_dropdown" class="contacts_bmlt_service_body_select">
                                    <?php if ($this_connected) { ?>
                                        <?php $unique_areas = $this->getParentServiceBodies($this->options['root_server']); ?>
                                        <?php foreach ($unique_areas as $key => $unique_area) { ?>
                                            <?php $area_data          = explode(',', $unique_area); ?>
                                            <?php $area_name          = $area_data[0]; ?>
                                            <?php $area_id            = $area_data[1]; ?>
                                            <?php $option_description = $area_name . " (" . $area_id . ")" ?>
                                            <?php $is_data = explode(',', esc_html($this->options['service_body_dropdown'])); ?>
                                            <?php if ($area_id == $is_data[1]) { ?>
                                                <option selected="selected" value="<?php echo $unique_area; ?>"><?php echo $option_description; ?></option>
                                            <?php } else { ?>
                                                <option value="<?php echo $unique_area; ?>"><?php echo $option_description; ?></option>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <option selected="selected" value="<?php echo $this->options['service_body_dropdown']; ?>"><?php echo 'Not Connected - Can not get Service Bodies'; ?></option>
                                    <?php } ?>
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
                                    <?php if ($this->options['display_type_dropdown'] == 'simple') { ?>
                                        <option selected="selected" value="simple">Simple</option>
                                        <option value="table">HTML (bmlt table)</option>
                                        <option value="block">HTML (bmlt block)</option>
                                        <?php
                                    } else if ($this->options['display_type_dropdown'] == 'table') { ?>
                                        <option value="simple">Simple</option>
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
                                <label for="show_tel_url_checkbox">Add Tel link to phone number.</label>
                            </li>
                            <li>
                                <input type="checkbox" id="show_full_url_checkbox" name="show_full_url_checkbox" value="1" <?php echo ($this->options['show_full_url_checkbox'] == "1" ? "checked" : "") ?>/>
                                <label for="show_full_url_checkbox">Show separate column displaying url.</label>
                            </li>
                            <li>
                                <input type="checkbox" id="show_description_checkbox" name="show_description_checkbox" value="1" <?php echo ($this->options['show_description_checkbox'] == "1" ? "checked" : "") ?>/>
                                <label for="show_description_checkbox">Show Description</label>
                            </li>
                            <li>
                                <input type="checkbox" id="show_email_checkbox" name="show_email_checkbox" value="1" <?php echo ($this->options['show_email_checkbox'] == "1" ? "checked" : "") ?>/>
                                <label for="show_email_checkbox">Show Email</label>
                            </li>
                        </ul>
                    </div>
                    <div style="padding: 0 15px;" class="postbox">
                        <h3>Custom CSS</h3>
                        <p>Allows for custom styling of Contacts BMLT.</p>
                        <ul>
                            <li>
                                <textarea id="custom_css_um" name="custom_css_um" cols="100" rows="10"><?php echo $this->options['custom_css_um']; ?></textarea>
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
         * @desc Adds the Settings link to the plugin activate/deactivate page
         * @param $links
         * @param $file
         * @return mixed
         */
        public function filterPluginActions($links, $file)
        {
            // If your plugin is under a different top-level menu than Settings (IE - you changed the function above to something other than add_options_page)
            // Then you're going to want to change options-general.php below to the name of your top-level page
            $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
            array_unshift($links, $settings_link);
            // before other links
            return $links;
        }
        /**
         * Retrieves the plugin options from the database.
         * @return array
         */
        public function getOptions()
        {
            // Don't forget to set up the default options
            if (!$theOptions = get_option($this->optionsName)) {
                $theOptions = array(
                    "root_server"                => '',
                    "service_body_dropdown"      => '000',
                    'display_type_dropdown'      => 'block',
                    'show_url_in_name_checkbox'  => '1',
                    'show_tel_url_checkbox'      => '0',
                    'show_full_url_checkbox'     => '0',
                    'show_description_checkbox'  => '0',
                    'show_email_checkbox'        => '0'
                );
                update_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
            $this->options['root_server'] = untrailingslashit(preg_replace('/^(.*)\/(.*php)$/', '$1', $this->options['root_server']));
        }
        /**
         * Saves the admin options to the database.
         */
        public function saveAdminOptions()
        {
            $this->options['root_server'] = untrailingslashit(preg_replace('/^(.*)\/(.*php)$/', '$1', $this->options['root_server']));
            update_option($this->optionsName, $this->options);
            return;
        }

        /**
         * @param $root_server
         * @param null $parent_id
         * @return array
         */
        public function getServiceBodiesJson($root_server, $parent_id = null)
        {
            $serviceBodiesURL =  wp_remote_retrieve_body(wp_remote_get($root_server . "/client_interface/json/?switcher=GetServiceBodies"));
            $serviceBodies_results = json_decode($serviceBodiesURL, true);

            $output = array();

            if (isset($parent_id) && $parent_id == "000") {
                $output = $serviceBodies_results;
            } elseif (isset($parent_id) && is_numeric($parent_id)) {
                foreach ($serviceBodies_results as &$serviceBody) {
                    if ($serviceBody['parent_id'] == $parent_id || $serviceBody['id'] == $parent_id) {
                        $output[] = $serviceBody;
                    }
                }
            } else {
                $output = $serviceBodies_results;
            }

            usort($output, function ($a, $b) {
                return strnatcasecmp($a['name'], $b['name']);
            });

            return $output;
        }

        /**
         * @param $root_server
         * @return array
         */
        public function getParentServiceBodies($root_server)
        {
            $serviceBodiesURL =  wp_remote_retrieve_body(wp_remote_get($root_server . "/client_interface/json/?switcher=GetServiceBodies"));
            $serviceBodies = json_decode($serviceBodiesURL, true);

            $parent_body_ids = array();
            $parent_bodies = array();

            foreach ($serviceBodies as &$parentServiceBody) {
                $parent_body_ids[] .= $parentServiceBody['parent_id'];
            }

            $unique_parent_body_ids = array_unique($parent_body_ids);

            foreach ($serviceBodies as &$serviceBody) {
                if (in_array($serviceBody['id'], $unique_parent_body_ids)) {
                    $parent_bodies[] = $serviceBody;
                }
            }

            usort($parent_bodies, function ($a, $b) {
                return strnatcasecmp($a['name'], $b['name']);
            });

            $unique_service_bodies = array();
            foreach ($parent_bodies as $value) {
                $unique_service_bodies[] = $value['name'] . ',' . $value['id'];
            }
            array_unshift($unique_service_bodies, 'All Service Bodies,000');
            return $unique_service_bodies;
        }

        /*******************************************************************/
        /**
         * \brief  This returns the search results, in whatever form was requested.
         * \returns XHTML data. It will either be a table, or block elements.
         * @param $results
         * @param bool $in_block
         * @param null $show_description
         * @param null $show_url_in_name
         * @param null $show_tel_url
         * @param null $show_email
         * @param null $show_full_url
         * @return string
         */
        public function serviceBodiesJson2Html(
            $results,                 ///< The results.
            $in_block = false,        ///< If this is true, the results will be sent back as block elements (div tags), as opposed to a table. Default is false.
            $show_description = null, //
            $show_url_in_name = null, //
            $show_tel_url = null,     //
            $show_email = null,       //
            $show_full_url = null     //
        ) {
            $ret = '';
            // What we do, is to parse the JSON return. We'll pick out certain fields, and format these into a table or block element return.
            if ($results) {
                if (is_array($results) && count($results)) {
                    $ret = $in_block ? '<div class="bmlt_simple_contacts_div">' : '<table class="bmlt_simple_contacts_table" cellpadding="0" cellspacing="0" summary="Contacts">';
                    $result_keys = array();
                    foreach ($results as $sub) {
                        $result_keys = array_merge($result_keys, $sub);
                    }
                    $keys = array_keys($result_keys);

                    for ($count = 0; $count < count($results); $count++) {
                        $serviceBody = $results[$count];

                        if ($serviceBody) {
                            if (is_array($serviceBody) && count($serviceBody)) {
                                if (count($serviceBody) > count($keys)) {
                                    $keys[] = 'unused';
                                }

                                // This is for convenience. We turn the serviceBody array into an associative one by adding the keys.
                                $serviceBody = array_combine($keys, $serviceBody);
                                $url = htmlspecialchars(trim(stripslashes($serviceBody['url'])));

                                $scheme = parse_url($url, PHP_URL_SCHEME);
                                if (empty($scheme)) {
                                    $url = '//' . ltrim($url, '/');
                                }

                                $strip_url = rtrim(str_replace(array('http://','https://','//'), '', $url), '/');
                                $helpline = htmlspecialchars(trim(stripslashes($serviceBody['helpline'])));
                                $contact_email = htmlspecialchars(trim(stripslashes($serviceBody['contact_email'])));
                                $description = htmlspecialchars(trim(stripslashes($serviceBody['description'])));
                                $name = htmlspecialchars(trim(stripslashes($serviceBody['name'])));

                                if ($serviceBody['url'] && $show_url_in_name == "1") {
                                    $service_body_name = '<span class="bmlt_simple_list_service_body_name_text"><a href="' . $url . '">' . $name . '</a></span>';
                                } else {
                                    $service_body_name = '<span class="bmlt_simple_list_service_body_name_text">' . $name . '</span>';
                                }

                                if ($helpline && $show_tel_url =="1") {
                                    $phoneNumber = '<span class=\"bmlt_simple_list_helpline_text\"><a href="tel:' . $helpline . '">' . $helpline . '</a></span>';
                                } else {
                                    $phoneNumber = '<span class=\"bmlt_simple_list_helpline_text\">' . $helpline . '</span>';
                                }

                                if ($helpline || $serviceBody['url']) {
                                    $ret .= $in_block ? '<div class="bmlt_simple_contact_one_contact_div">' : '<tr class="bmlt_simple_contact_one_contact_tr">';

                                    $ret .= $in_block ? '<div class="bmlt_simple_contact_one_contact_service_body_name_div">' : '<td class="bmlt_simple_contact_one_contact_service_body_name_td">';
                                    $ret .= $service_body_name;
                                    if ($contact_email && $show_email == "1") {
                                        $ret .= '<div class="bmlt_simple_contact_one_contact_service_body_contact_email_div">';
                                        $ret .= $contact_email;
                                        $ret .= '</div>';
                                    }
                                    if ($description && $show_description == "1") {
                                        $ret .= '<div class="bmlt_simple_contact_one_contact_service_body_description_div">';
                                        $ret .= $description;
                                        $ret .= '</div>';
                                    }
                                    $ret .= $in_block ? '</div>' : '</td>';


                                    if ($show_full_url != "1") {
                                        $ret .= $in_block ? '<div class="bmlt_simple_contact_one_contact_helpline_no_full_url_div">' : '<td class="bmlt_simple_contact_one_contact_helpline_td">';
                                        $ret .= $phoneNumber;
                                        $ret .= $in_block ? '</div>' : '</td>';
                                    }

                                    if ($show_full_url == "1") {
                                        $ret .= $in_block ? '<div class="bmlt_simple_contact_one_contact_helpline_div">' : '<td class="bmlt_simple_contact_one_contact_helpline_td">';
                                        $ret .= $phoneNumber;
                                        $ret .= $in_block ? '</div>' : '</td>';

                                        $ret .= $in_block ? '<div class="bmlt_simple_contact_one_contact_url_div">' : '<td class="bmlt_simple_contact_one_contact_url_td">';
                                        $ret .= '<a href="' . $url . '">' . $strip_url . '</a>';
                                        $ret .= $in_block ? '</div>' : '</td>';
                                    }
                                    $ret .= $in_block ? '</div>' : '</tr>';
                                }
                            }
                        }
                    }
                    $ret .= $in_block ? '</div>' : '</table>';
                }
            }

            return $ret;
        }
    }
    //End Class ContactsBmlt
}
// end if
// instantiate the class
if (class_exists("contactsBmlt")) {
    $ContactsBmlt_instance = new contactsBmlt();
}
?>
