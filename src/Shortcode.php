<?php

namespace ContactsBmlt;

require_once 'Settings.php';
require_once 'Helpers.php';

class Shortcode
{
    private $settings;
    private $helper;

    public function __construct()
    {
        $this->settings = new Settings();
        $this->helper = new Helpers();
    }

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
        $service_body_results = $this->helper->getServiceBodiesJson($serviceBodies, $args['parent_id'], $args['show_all_services']);

        if ($args['display_type'] != '') {
            $content .= '<div id="contacts_bmlt_div">';
            $isBlock = ($args['display_type'] == 'block');
            $content .= $this->serviceBodiesJson2Html($service_body_results, $isBlock, $args['show_description'], $args['show_url_in_name'], $args['show_tel_url'], $args['show_email'], $args['show_full_url'], $args['show_locations'], $args['root_server']);
            $content .= '</div>';
        }

        return $content;
    }

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
            'show_full_url'     => $this->settings->options['show_full_url_checkbox'],
            'show_description'  => $this->settings->options['show_description_checkbox'],
            'show_email'        => $this->settings->options['show_email_checkbox'],
            'show_all_services' => $this->settings->options['show_all_services_checkbox'],
            'show_locations'    => $this->settings->options['show_locations_dropdown']
        ];
    }


    public function serviceBodiesJson2Html(
        $results,
        $in_block = false,
        $show_description = null,
        $show_url_in_name = null,
        $show_tel_url = null,
        $show_email = null,
        $show_full_url = null,
        $show_locations = null,
        $root_server = null
    ) {
        if (!$results || !is_array($results) || !count($results)) {
            return '';
        }

        $ret = $this->startBlockOrTable($in_block);
        $locations = [];
        if ($show_locations) {
            $serviceBodyIds = array_map(function ($item) {
                return isset($item['id']) ? $item['id'] : null;
            }, $results);
            $serviceBodyIds = array_filter($serviceBodyIds);
            $locations = $this->helper->getLocations($root_server, implode(',', $serviceBodyIds));
        }
        foreach ($results as $serviceBody) {
            if (isset($serviceBody) && is_array($serviceBody) && count($serviceBody)) {
                $ret .= $this->processServiceBody($serviceBody, $in_block, $show_description, $show_url_in_name, $show_tel_url, $show_email, $show_full_url, $show_locations, $locations);
            }
        }

        $ret .= $in_block ? '</div>' : '</table>';
        return $ret;
    }

    private function startBlockOrTable($in_block)
    {
        return $in_block ? '<div class="bmlt_simple_contacts_div">' : '<table class="bmlt_simple_contacts_table" cellpadding="0" cellspacing="0" summary="Contacts">';
    }

    private function processServiceBody($serviceBody, $in_block, $show_description, $show_url_in_name, $show_tel_url, $show_email, $show_full_url, $show_locations, $locations)
    {
        $serviceBodyData = $this->extractServiceBodyData($serviceBody);
        $service_body_name = $this->generateServiceBodyName($serviceBodyData['name'], $serviceBodyData['url'], $show_url_in_name);
        $phoneNumber = $this->generatePhoneNumber($serviceBodyData['helpline'], $show_tel_url);
        $locations_list = $this->generateLocationsList($serviceBody['id'], $show_locations, $locations);
        return $this->generateHTML($serviceBodyData, $in_block, $service_body_name, $phoneNumber, $locations_list, $show_locations, $show_description, $show_email, $show_full_url);
    }

    private function extractServiceBodyData($serviceBody)
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

    private function prepareUrl($url)
    {
        $url = htmlspecialchars(trim(stripslashes($url)));
        if (empty(parse_url($url, PHP_URL_SCHEME))) {
            return '//' . ltrim($url, '/');
        }
        return $url;
    }

    private function generateServiceBodyName($name, $url, $show_url_in_name)
    {
        if ($url && $show_url_in_name == "1") {
            return '<span class="bmlt_simple_list_service_body_name_text"><a href="' . $url . '" target="_blank">' . $name . '</a></span>';
        }
        return '<span class="bmlt_simple_list_service_body_name_text">' . $name . '</span>';
    }

    private function generatePhoneNumber($helpline, $show_tel_url)
    {
        if ($helpline && $show_tel_url == "1") {
            return '<span class="bmlt_simple_list_helpline_text"><a href="tel:' . $helpline . '">' . $helpline . '</a></span>';
        }
        return '<span class="bmlt_simple_list_helpline_text">' . $helpline . '</span>';
    }

    private function generateLocationsList($id, $show_locations, $locations)
    {
        if (!$show_locations) {
            return '';
        }
        $location_values = ["location_neighborhood", "location_city_subsection", "location_municipality", "location_sub_province"];
        if (in_array($show_locations, $location_values)) {
            return '<span class="bmlt_simple_contacts_locations_text">' . $this->helper->getLocationsList($locations, $id, $show_locations) . '</span>';
        }
        return '<span class="bmlt_simple_contacts_locations_text">' . $this->helper->getLocationsList($locations, $id, 'location_municipality') . '</span>';
    }

    private function generateHTML($serviceBodyData, $in_block, $service_body_name, $phoneNumber, $locations_list, $show_locations, $show_description, $show_email, $show_full_url)
    {
        $ret = '';
        if ($serviceBodyData['name']) {
            $ret .= $this->openContactDivOrRow($in_block);
            $ret .= $this->populateBodyCell($service_body_name, $in_block, 'service_body_name', [$serviceBodyData['contact_email'], $show_email, 'contact_email'], [$locations_list, $show_locations, 'locations'], [$serviceBodyData['description'], $show_description, 'description']);
            if ($show_full_url != "1") {
                $ret .= $this->populateBodyCell($phoneNumber, $in_block, 'helpline_no_full_url');
            }
            if ($show_full_url == "1") {
                $ret .= $this->populateBodyCell($phoneNumber, $in_block, 'helpline');
                $ret .= $this->populateBodyCell('<a href="' . $serviceBodyData['url'] . '" target="_blank">' . $serviceBodyData['strip_url'] . '</a>', $in_block, 'url');
            }
            $ret .= $this->closeContactDivOrRow($in_block);
        }
        return $ret;
    }

    private function openContactDivOrRow($in_block)
    {
        return $in_block ? '<div class="bmlt_simple_contact_one_contact_div">' : '<tr class="bmlt_simple_contact_one_contact_tr">';
    }

    private function closeContactDivOrRow($in_block)
    {
        return $in_block ? '</div>' : '</tr>';
    }

    private function populateBodyCell($content, $in_block, $type, ...$conditionalContents)
    {
        $cell = '';
        $div_class = 'bmlt_simple_contact_one_contact_' . $type . '_div';
        $td_class = 'bmlt_simple_contact_one_contact_' . $type . '_td';
        $cell .= $in_block ? '<div class="' . $div_class . '">' : '<td class="' . $td_class . '">';
        $cell .= $content;
        foreach ($conditionalContents as $conditionalContent) {
            [$data, $condition, $innerType] = $conditionalContent;
            if ($data && $condition) {
                $inner_div_class = 'bmlt_simple_contact_one_contact_' . $innerType . '_div';
                $cell .= '<div class="' . $inner_div_class . '">' . $data . '</div>';
            }
        }
        $cell .= $in_block ? '</div>' : '</td>';
        return $cell;
    }
}
