<?php

namespace ContactsBmlt;

require_once 'Settings.php';
require_once 'Helpers.php';

/**
 * Class Shortcode
 * @package ContactsBmlt
 */
class Shortcode
{
    private $settings;
    private $helper;

    /**
     * Constructor for the Shortcode class.
     */
    public function __construct()
    {
        $this->settings = new Settings();
        $this->helper = new Helpers();
    }

    /**
     * Render the plugin's content based on shortcode attributes.
     *
     * This method is responsible for rendering the content based on the provided shortcode attributes.
     * It processes the attributes, performs necessary checks, retrieves meeting results, and generates HTML content.
     *
     * @param array $atts An associative array of shortcode attributes.
     * @return string The rendered content as a string.
     */
    public function render($atts = [], $content = null): string
    {
        $defaults = $this->getDefaultValues();
        $args = shortcode_atts($defaults, $atts);
        if (empty($args['root_server'])) {
            return '<p><strong>Contacts BMLT Error: Root Server missing. Please Verify you have entered a Root Server.</strong></p>';
        }
        if (empty($args['parent_id'])) {
            return '<p><strong>Contacts BMLT Error: Service Body missing. Please verify you have entered a service body id.</strong></p>';
        }

        $serviceBodies = $this->helper->getServiceBodies($args['root_server']);
        if (empty($serviceBodies)) {
            return '<p><strong>Contacts BMLT Error: Unable to fetch service bodies from the root server. Please check your connection or server URL.</strong></p>';
        }

        $service_body_results = $this->helper->getFilteredServiceBodies($serviceBodies, $args['parent_id'], $args['show_all_services']);

        if ($args['display_type'] != '') {
            $content .= '<div id="contacts_bmlt_div">';
            $isBlock = ($args['display_type'] == 'block');

            $content .= $this->serviceBodiesJson2Html($service_body_results, $isBlock, $args['show_description'], $args['show_url_in_name'], $args['show_tel_url'], $args['show_email'], $args['showFullUrl'], $args['show_locations'], $args['root_server']);
            $content .= '</div>';
        }

        return $content;
    }

    /**
     * Get the default values for plugin settings.
     *
     * This method retrieves and returns an array of default values for various plugin settings.
     *
     * @return array An associative array containing default settings values.
     */
    private function getDefaultValues(): array
    {
        $services_data_dropdown   = explode(',', $this->settings->options['service_body_dropdown']);
        $services_dropdown    = $this->helper->arraySafeGet($services_data_dropdown, 1);

        return [
            "root_server"       => $this->settings->options['root_server'],
            'display_type'      => $this->settings->options['display_type_dropdown'],
            'parent_id'         => $services_dropdown,
            'show_url_in_name'  => $this->settings->options['show_url_in_name_checkbox'],
            'show_tel_url'      => $this->settings->options['show_tel_url_checkbox'],
            'showFullUrl'       => $this->settings->options['show_full_url_checkbox'],
            'show_description'  => $this->settings->options['show_description_checkbox'],
            'show_email'        => $this->settings->options['show_email_checkbox'],
            'show_all_services' => $this->settings->options['show_all_services_checkbox'],
            'show_locations'    => $this->settings->options['show_locations_dropdown']
        ];
    }

    /**
     * Convert JSON data of service bodies into HTML representation based on specified display options.
     *
     * This method takes a JSON representation of service bodies, processes each service body's data,
     * and generates HTML content for their contact information based on specified display options.
     * The generated HTML can be wrapped in a <div> or placed within a <table>. Display options include
     * showing descriptions, URLs in the name, telephone URL links, email addresses, full website URLs,
     * and locations.
     *
     * @param array $results An array of service body data in JSON format.
     * @param bool $inBlock Whether to wrap the content in a <div> (true) or use a <table> (false).
     * @param bool|null $showDescription Whether to display the service body's description.
     * @param bool|null $showUrlInName Whether to include the service body's URL in the name.
     * @param bool|null $showTelUrl Whether to generate telephone URL links.
     * @param bool|null $showEmail Whether to display the contact email.
     * @param bool|null $showFullUrl Whether to display the full website URL.
     * @param bool|null $showLocations Whether to display the locations list.
     * @param string|null $rootServer The root URL of the remote server to fetch location data (optional).
     *
     * @return string The generated HTML content for the service bodies' contact information.
     */
    public function serviceBodiesJson2Html(
        $results,
        $inBlock = false,
        $showDescription = null,
        $showUrlInName = null,
        $showTelUrl = null,
        $showEmail = null,
        $showFullUrl = null,
        $showLocations = null,
        $rootServer = null
    ) {
        if (!$results || !is_array($results) || !count($results)) {
            return '';
        }

        $ret = $this->startBlockOrTable($inBlock);
        $locations = [];
        if ($showLocations) {
            $serviceBodyIds = array_map(function ($item) {
                return isset($item['id']) ? $item['id'] : null;
            }, $results);
            $serviceBodyIds = array_filter($serviceBodyIds);
            $locations = $this->helper->getLocations($rootServer, implode(',', $serviceBodyIds));
        }
        foreach ($results as $serviceBody) {
            if (isset($serviceBody) && is_array($serviceBody) && count($serviceBody)) {
                $ret .= $this->processServiceBody($serviceBody, $inBlock, $showDescription, $showUrlInName, $showTelUrl, $showEmail, $showFullUrl, $showLocations, $locations);
            }
        }

        $ret .= $inBlock ? '</div>' : '</table>';
        return $ret;
    }

    /**
     * Start a contact block <div> or a contact table <table> based on the given flag.
     *
     * This private method generates and returns the opening tag of either a contact block <div> or a contact table <table>
     * based on the `$inBlock` parameter. The appropriate CSS class and attributes are applied accordingly.
     *
     * @param bool $inBlock Whether to start a contact block <div> (true) or a contact table <table> (false).
     *
     * @return string The opening tag of the contact block or table element.
     */
    private function startBlockOrTable(bool $inBlock): string
    {
        return $inBlock ? '<div class="bmlt_simple_contacts_div">' : '<table class="bmlt_simple_contacts_table" cellpadding="0" cellspacing="0" summary="Contacts">';
    }

    /**
     * Process and generate HTML content for a service body's contact information based on specified display options.
     *
     * This private method processes a service body's data, including name, URL, phone number, and locations,
     * and generates HTML content for the contact information. The generated content considers various display
     * options such as showing descriptions, URLs in the name, telephone URL links, email addresses, and locations.
     *
     * @param array $serviceBody An associative array containing service body data.
     * @param bool $inBlock Whether to wrap the content in a <div> (true) or <tr> (false).
     * @param bool $showDescription Whether to display the service body's description.
     * @param bool $showUrlInName Whether to include the service body's URL in the name.
     * @param bool $showTelUrl Whether to generate telephone URL links.
     * @param bool $showEmail Whether to display the contact email.
     * @param bool $showFullUrl Whether to display the full website URL.
     * @param string $showLocations Whether to display the locations list.
     * @param array $locations An array of location data, each represented as an associative array.
     *
     * @return string The generated HTML content for the service body's contact information.
     */
    private function processServiceBody(
        array $serviceBody,
        bool $inBlock,
        bool $showDescription,
        bool $showUrlInName,
        bool $showTelUrl,
        bool $showEmail,
        bool $showFullUrl,
        string $showLocations,
        array $locations
    ): string {
        $serviceBodyData = $this->extractServiceBodyData($serviceBody);
        $serviceBodyName = $this->generateServiceBodyName($serviceBodyData['name'], $serviceBodyData['url'], $showUrlInName);
        $phoneNumber = $this->generatePhoneNumber($serviceBodyData['helpline'], $showTelUrl);
        $locationsList = $this->generateLocationsList($serviceBody['id'], $showLocations, $locations);
        return $this->generateHTML(
            $serviceBodyData,
            $inBlock,
            $serviceBodyName,
            $phoneNumber,
            $locationsList,
            $showLocations,
            $showDescription,
            $showEmail,
            $showFullUrl
        );
    }

    /**
     * Extract and prepare essential service body data for display.
     *
     * This private method extracts and prepares essential service body data from the input array,
     * including URL, stripped URL, helpline, contact email, description, and name. It performs
     * necessary formatting and escaping to ensure safe and clean display of the data.
     *
     * @param array $serviceBody An associative array containing service body data.
     *
     * @return array An associative array containing the extracted and prepared service body data.
     */
    private function extractServiceBodyData(array $serviceBody): array
    {
        $url = $this->prepareUrl($serviceBody['url']);
        $strip_url = rtrim(str_replace(array('http://','https://','//'), '', $url), '/');
        return [
            'url' => $url,
            'strip_url' => $strip_url,
            'helpline' => htmlspecialchars(trim(stripslashes($serviceBody['helpline']))),
            'contact_email' => htmlspecialchars(trim(stripslashes($serviceBody['contact_email'] ?? ''))),
            'description' => htmlspecialchars(trim(stripslashes($serviceBody['description']))),
            'name' => htmlspecialchars(trim(stripslashes($serviceBody['name'])))
        ];
    }

    /**
     * Prepare a URL for display by adding the scheme if missing and performing necessary escaping.
     *
     * This private method takes a URL, adds the scheme (http:// or https://) if missing, and performs
     * escaping and trimming to ensure a valid and safe URL for display purposes.
     *
     * @param string $url The URL to be prepared for display.
     *
     * @return string The prepared and escaped URL with a scheme (http:// or https://) if missing.
     */
    private function prepareUrl($url)
    {
        $url = htmlspecialchars(trim(stripslashes($url)));
        if (empty(parse_url($url, PHP_URL_SCHEME))) {
            return '//' . ltrim($url, '/');
        }
        return $url;
    }


    /**
     * Generate a formatted service body name with an optional URL link.
     *
     * This private method generates and returns a formatted service body name, which can include
     * an optional URL link if specified by the `$showUrlInName` parameter. The generated content
     * is enclosed in a <span> element with an appropriate CSS class.
     *
     * @param string $name The name of the service body.
     * @param string $url The URL associated with the service body (if available).
     * @param bool $showUrlInName Whether to include the URL link in the name.
     *
     * @return string The formatted service body name with or without a URL link, enclosed in a <span> element.
     */
    private function generateServiceBodyName(string $name, string $url, bool $showUrlInName): string
    {
        if ($url && $showUrlInName == "1") {
            return '<span class="bmlt_simple_list_service_body_name_text"><a href="' . $url . '" target="_blank">' . $name . '</a></span>';
        }
        return '<span class="bmlt_simple_list_service_body_name_text">' . $name . '</span>';
    }

    /**
     * Generate a formatted phone number with an optional tel: URL link.
     *
     * This private method generates and returns a formatted phone number, which can include
     * an optional tel: URL link if specified by the `$showTelUrl` parameter. The generated content
     * is enclosed in a <span> element with an appropriate CSS class.
     *
     * @param string $helpline The phone number to be formatted.
     * @param bool $showTelUrl Whether to include the tel: URL link for the phone number.
     *
     * @return string The formatted phone number with or without a tel: URL link, enclosed in a <span> element.
     */
    private function generatePhoneNumber(string $helpline, bool $showTelUrl): string
    {
        if ($helpline && $showTelUrl == "1") {
            return '<span class="bmlt_simple_list_helpline_text"><a href="tel:' . $helpline . '">' . $helpline . '</a></span>';
        }
        return '<span class="bmlt_simple_list_helpline_text">' . $helpline . '</span>';
    }

    /**
     * Generate a formatted list of locations based on specified display options.
     *
     * This private method generates and returns a formatted list of locations based on the provided
     * `$showLocations` option. The list can include location values such as neighborhood, city subsection,
     * municipality, or sub-province, depending on the option. The generated content is enclosed in a <span> element
     * with the appropriate CSS class.
     *
     * @param int $id The identifier for the service body.
     * @param string $showLocations Whether to display the locations list.
     * @param array $locations An array of location data, each represented as an associative array.
     *
     * @return string The formatted list of locations enclosed in a <span> element with the appropriate CSS class.
     */
    private function generateLocationsList(int $id, string $showLocations, array $locations): string
    {
        if (!$showLocations) {
            return '';
        }
        $location_values = ["location_neighborhood", "location_city_subsection", "location_municipality", "location_sub_province"];
        if (in_array($showLocations, $location_values)) {
            return '<span class="bmlt_simple_contacts_locations_text">' . $this->helper->getLocationsList($locations, $id, $showLocations) . '</span>';
        }
        return '<span class="bmlt_simple_contacts_locations_text">' . $this->helper->getLocationsList($locations, $id, 'location_municipality') . '</span>';
    }

    /**
     * Generate HTML content for a service body's contact information based on specified display options.
     *
     * This private method generates and returns HTML content for a service body's contact information,
     * considering various display options. It includes the service body's name, contact email, description,
     * phone number, locations, and website URL (if applicable). The generated content is wrapped in either
     * a <div> or <tr> element based on the `$inBlock` parameter.
     *
     * @param array $serviceBodyData An associative array containing service body data.
     * @param bool $inBlock Whether to wrap the content in a <div> (true) or <tr> (false).
     * @param string $serviceBodyName The name of the service body.
     * @param string $phoneNumber The phone number for the service body.
     * @param string $locationsList A comma-separated list of locations associated with the service body.
     * @param bool $showLocations Whether to display the locations information.
     * @param bool $showDescription Whether to display the service body's description.
     * @param bool $showEmail Whether to display the contact email.
     * @param bool $showFullUrl Whether to display the full website URL.
     *
     * @return string The generated HTML content for the service body's contact information.
     */
    private function generateHTML(
        array $serviceBodyData,
        bool $inBlock,
        string $serviceBodyName,
        string $phoneNumber,
        string $locationsList,
        bool $showLocations,
        bool $showDescription,
        bool $showEmail,
        bool $showFullUrl
    ): string {
        $ret = '';
        if ($serviceBodyData['name']) {
            $ret .= $this->openContactDivOrRow($inBlock);
            $ret .= $this->populateBodyCell($serviceBodyName, $inBlock, 'service_body_name', [$serviceBodyData['contact_email'], $showEmail, 'contact_email'], [$locationsList, $showLocations, 'locations'], [$serviceBodyData['description'], $showDescription, 'description']);
            if ($showFullUrl != "1") {
                $ret .= $this->populateBodyCell($phoneNumber, $inBlock, 'helpline_no_full_url');
            }
            if ($showFullUrl == "1") {
                $ret .= $this->populateBodyCell($phoneNumber, $inBlock, 'helpline');
                $ret .= $this->populateBodyCell('<a href="' . $serviceBodyData['url'] . '" target="_blank">' . $serviceBodyData['strip_url'] . '</a>', $inBlock, 'url');
            }
            $ret .= $this->closeContactDivOrRow($inBlock);
        }
        return $ret;
    }

    /**
     * Open a contact <div> or <tr> element based on the given flag.
     *
     * This private method generates and returns the opening tag of a contact container element,
     * which can be a <div> or <tr> based on the `$inBlock` parameter. The CSS class is applied
     * based on the element type.
     *
     * @param bool $inBlock Whether to open a <div> (true) or <tr> (false).
     *
     * @return string The opening tag of the contact container element.
     */
    private function openContactDivOrRow($inBlock)
    {
        return $inBlock ? '<div class="bmlt_simple_contact_one_contact_div">' : '<tr class="bmlt_simple_contact_one_contact_tr">';
    }

    /**
     * Close a contact <div> or <tr> element based on the given flag.
     *
     * This private method generates and returns the closing tag of a contact container element,
     * which can be a </div> or </tr> based on the `$inBlock` parameter.
     *
     * @param bool $inBlock Whether to close a </div> (true) or </tr> (false).
     *
     * @return string The closing tag of the contact container element.
     */
    private function closeContactDivOrRow($inBlock)
    {
        return $inBlock ? '</div>' : '</tr>';
    }

    /**
     * Populate a cell with content, possibly wrapped in a div or td, with conditional inner content.
     *
     * This private method generates and returns HTML content for a cell. The content can be optionally
     * wrapped in a <div> or <td> element based on the `$inBlock` parameter. It also supports conditional
     * inner content that is added based on the provided conditionalContents array.
     *
     * @param string $content The main content to be placed in the cell.
     * @param bool $inBlock Whether the content should be wrapped in a <div> (true) or <td> (false).
     * @param string $type The type of cell (used for CSS class naming).
     * @param mixed ...$conditionalContents An array of conditional inner content. Each element of the array
     *                                      should be an array containing three elements: $data (the inner content),
     *                                      $condition (the condition to determine if inner content should be added),
     *                                      and $innerType (the type of inner content used for CSS class naming).
     *
     * @return string The HTML content for the cell, possibly wrapped in a <div> or <td>, with conditional inner content.
     */
    private function populateBodyCell($content, $inBlock, $type, ...$conditionalContents)
    {
        $cell = '';
        $div_class = 'bmlt_simple_contact_one_contact_' . $type . '_div';
        $td_class = 'bmlt_simple_contact_one_contact_' . $type . '_td';
        $cell .= $inBlock ? '<div class="' . $div_class . '">' : '<td class="' . $td_class . '">';
        $cell .= $content;
        foreach ($conditionalContents as $conditionalContent) {
            [$data, $condition, $innerType] = $conditionalContent;
            if ($data && $condition) {
                $inner_div_class = 'bmlt_simple_contact_one_contact_' . $innerType . '_div';
                $cell .= '<div class="' . $inner_div_class . '">' . $data . '</div>';
            }
        }
        $cell .= $inBlock ? '</div>' : '</td>';
        return $cell;
    }
}
