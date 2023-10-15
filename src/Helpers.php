<?php

namespace ContactsBmlt;

class Helpers
{
    const BASE_API_ENDPOINT = "/client_interface/json/?switcher=";
    const HTTP_RETRIEVE_ARGS = array(
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:105.0) Gecko/20100101 Firefox/105.0 +ListLocationsBMLT'
        ),
        'timeout' => 601
    );

    public static function arraySafeGet(array $array, $key, $default = null)
    {
        return $array[$key] ?? $default;
    }

    private function getRemoteResponse(string $root_server, array $queryParams = [], string $switcher = 'GetSearchResults'): array
    {

        $url = $root_server . self::BASE_API_ENDPOINT . $switcher;

        if (!empty($queryParams)) {
            $url .= '&' . http_build_query($queryParams);
        }

        $response = wp_remote_get($url, self::HTTP_RETRIEVE_ARGS);

        if (is_wp_error($response)) {
            return ['status' => 'error', 'message' => 'Error fetching data from server: ' . $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data)) {
            return ['status' => 'error', 'message' => 'Received empty data from server.'];
        }

        return ['status' => 'success', 'data' => $data];
    }

    public function testRootServer($root_server)
    {
        if (!$root_server) {
            return '';
        }

        $response = $this->getRemoteResponse($root_server, [], 'GetServerInfo');
        if ($response['status'] === 'error' || !is_array($response['data'])) {
            return '';
        }

        $data = $response['data'];

        return (isset($data[0]) && is_array($data[0]) && array_key_exists("version", $data[0])) ? $data[0]["version"] : '';
    }

    public function getServiceBodies(string $root_server): array
    {
        $response = $this->getRemoteResponse($root_server, [], 'GetServiceBodies');

        if ($response['status'] === 'error') {
            return [];
        } else {
            return $response['data'];
        }
    }

    public function getParentServiceBodies(array $serviceBodies): array
    {
        // Extract parent body ids
        $parent_body_ids = array_column($serviceBodies, 'parent_id');

        // Filter out the unique parent service bodies
        $parent_bodies = array_filter($serviceBodies, function ($serviceBody) use ($parent_body_ids) {
            return in_array($serviceBody['id'], $parent_body_ids);
        });

        // Sort by name
        usort($parent_bodies, function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });

        // Construct the result array
        $unique_service_bodies = array_map(function ($body) {
            return $body['name'] . ',' . $body['id'];
        }, $parent_bodies);

        array_unshift($unique_service_bodies, 'All Service Bodies,000');

        return $unique_service_bodies;
    }

    public function getServiceBodiesJson(array $serviceBodies, $parent_id = null, $show_all_services = null)
    {
        // decide if a service body should be added to output
        $shouldAddToOutput = function ($serviceBody) use ($show_all_services) {
            if ($show_all_services == "1") {
                return true;
            }
            return $serviceBody['helpline'] || $serviceBody['url'];
        };

        $output = [];

        if ($parent_id === "000") {
            if ($show_all_services == "1") {
                $output = $serviceBodies;
            } else {
                foreach ($serviceBodies as $serviceBody) {
                    if ($shouldAddToOutput($serviceBody)) {
                        $output[] = $serviceBody;
                    }
                }
            }
        } elseif (isset($parent_id) && is_numeric($parent_id)) {
            foreach ($serviceBodies as $serviceBody) {
                if ($serviceBody['parent_id'] == $parent_id || $serviceBody['id'] == $parent_id) {
                    if ($shouldAddToOutput($serviceBody)) {
                        $output[] = $serviceBody;
                    }
                }
            }
        } else {
            $output = $serviceBodies;
        }

        // Sort the output by service body name
        usort($output, function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });

        return $output;
    }

    public function getLocations(string $root_server, string $serviceBodies)
    {
        $queryParams = [
            'services' => $serviceBodies,
            'data_field_key' => 'service_body_bigint,location_municipality,location_province,location_sub_province,location_city_subsection,location_neighborhood'
        ];

        $response = $this->getRemoteResponse($root_server, $queryParams);

        if ($response['status'] === 'error') {
            return [];
        } else {
            return $response['data'];
        }
    }

    public function getLocationsList($locations, $services, $data_field_key)
    {
        $filteredData = array_filter($locations, function ($item) use ($services) {
            return isset($item['service_body_bigint']) && $item['service_body_bigint'] == $services;
        });

        $unique_locations = array_unique(array_map(function ($value) use ($data_field_key) {
            return trim(ucwords(str_replace('.', '', strtolower($value[$data_field_key]))));
        }, $filteredData));

        $unique_locations = array_filter($unique_locations);
        asort($unique_locations);
        return implode(', ', $unique_locations);
    }
}
