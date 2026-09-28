<?php

namespace ContactsBmlt;

/**
 * Class Helpers
 * @package ContactsBmlt
 */
class Helpers
{
    /**
     * Base API endpoint for BMLT requests.
     *
     * This constant defines the base endpoint used for making BMLT API requests.
     */
    const BASE_API_ENDPOINT = "/client_interface/json/?switcher=";

    /**
     * HTTP retrieval arguments for API requests.
     *
     * This constant defines the HTTP retrieval arguments, including headers and timeout,
     * used for making API requests.
     */
    const HTTP_RETRIEVE_ARGS = [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:105.0) Gecko/20100101 Firefox/105.0 +ContactsBMLT'
        ],
        'timeout' => 300
    ];

    /**
     * Default number of seconds to cache root server responses.
     *
     * Can be changed with the 'contacts_bmlt_cache_ttl' filter, returning 0 disables caching.
     */
    const CACHE_TTL = HOUR_IN_SECONDS;

    /**
     * Option holding the current cache version, changing it invalidates all cached responses.
     */
    const CACHE_VERSION_OPTION = 'contacts_bmlt_cache_version';

    /**
     * US state and territory abbreviations mapped to their full names.
     */
    const STATE_NAMES = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
        'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
        'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
        'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
        'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
        'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
        'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
        'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
        'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
        'DC' => 'District of Columbia', 'PR' => 'Puerto Rico', 'VI' => 'U.S. Virgin Islands', 'GU' => 'Guam',
        'AS' => 'American Samoa', 'MP' => 'Northern Mariana Islands'
    ];

    /**
     * Safely retrieve a value from an associative array.
     *
     * This static method allows you to safely retrieve a value from an associative array
     * by providing a key and an optional default value to return if the key is not found.
     *
     * @param array $array An associative array from which to retrieve the value.
     * @param mixed $key The key to look up in the array.
     * @param mixed $default (optional) The default value to return if the key is not found. Defaults to null.
     * @return mixed The value associated with the key, or the default value if the key is not found.
     */
    public static function arraySafeGet(array $array, $key, $default = null)
    {
        return $array[$key] ?? $default;
    }

    /**
     * Get a remote JSON response from the specified BMLT server.
     *
     * This private method sends an HTTP GET request to a BMLT server with optional query parameters
     * and retrieves a JSON response. It handles errors, JSON decoding, and empty responses.
     *
     * @param string $rootServer The root server URL to send the request to.
     * @param array $queryParams (optional) An associative array of query parameters to include in the request.
     * @param string $switcher (optional) The switcher parameter for the API request. Defaults to 'GetSearchResults'.
     * @param bool $useCache (optional) Whether to read and store the response in a transient. Defaults to true.
     * @return array An associative array representing the JSON response or an error message.
     */
    private function getRemoteResponse(string $rootServer, array $queryParams = [], string $switcher = 'GetSearchResults', bool $useCache = true): array
    {

        $url = $rootServer . self::BASE_API_ENDPOINT . $switcher;

        if (!empty($queryParams)) {
            $url .= '&' . http_build_query($queryParams);
        }

        $cacheTtl = (int) apply_filters('contacts_bmlt_cache_ttl', self::CACHE_TTL);
        $useCache = $useCache && $cacheTtl > 0;
        $cacheKey = 'contacts_bmlt_' . md5(get_option(self::CACHE_VERSION_OPTION, '') . $url);
        if ($useCache) {
            $cached = get_transient($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $response = wp_remote_get($url, self::HTTP_RETRIEVE_ARGS);

        if (is_wp_error($response)) {
            return ['error' => 'Error fetching data from server: ' . $response->get_error_message()];
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Error decoding JSON response.'];
        }
        if (empty($data)) {
            return ['error' => 'Received empty data from server.'];
        }
        if ($useCache) {
            set_transient($cacheKey, $data, $cacheTtl);
        }
        return $data;
    }

    /**
     * Invalidate all cached root server responses.
     *
     * Rather than deleting each transient, the cache version is changed so old keys are never read again
     * and expire on their own.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        update_option(self::CACHE_VERSION_OPTION, uniqid('', true));
    }

    /**
     * Parse a comma separated list (or array) of service body ids into unique positive integers.
     *
     * @param mixed $ids A comma separated string or array of ids.
     * @return array An array of unique integer ids.
     */
    public static function parseIds($ids): array
    {
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }
        return array_values(array_unique(array_filter(array_map('absint', $ids))));
    }


    /**
     * Tests the root server and retrieves its version information.
     *
     * This function sends a test request to the specified root server to check its availability
     * and retrieves version information if available.
     *
     * @param string $rootServer The root server URL to test.
     *
     * @return string The version information of the root server if available. If the server is
     *               not responsive or an error occurs, an empty string is returned.
     */
    public function testRootServer(string $rootServer): string
    {
        if (!$rootServer) {
            return '';
        }
        $response = $this->getRemoteResponse($rootServer, [], 'GetServerInfo', false);
        if (isset($response['error'])) {
            return $response['error'];
        }
        return (isset($response[0]) && is_array($response[0]) && array_key_exists("version", $response[0])) ? $response[0]["version"] : '';
    }

    /**
     * Retrieves service bodies data from a remote server.
     *
     * This function sends a request to the specified root server to retrieve service bodies data.
     * The data is typically returned in an array format.
     *
     * @param string $rootServer The root server URL from which to fetch service bodies data.
     *
     * @return array An array containing service bodies data, typically in associative format.
     */
    public function getServiceBodies(string $rootServer): array
    {
        $response = $this->getRemoteResponse($rootServer, [], 'GetServiceBodies');
        // Check if there's an error in the response and return an empty array
        if (isset($response['error'])) {
            return [];
        }
        return $response;
    }

    /**
     * Get an array of parent service bodies from a list of service bodies.
     *
     * This method takes an array of service bodies and extracts the parent service bodies.
     * It filters out unique parent service bodies, sorts them by name, and constructs a result array
     * where each element is in the format "Service Body Name,Service Body ID". The "All Service Bodies" option
     * is added as the first element with an ID of 000.
     *
     * @param array $serviceBodies An array of service bodies, each represented as an associative array.
     *
     * @return array An array of unique parent service bodies sorted by name.
     */
    public function getParentServiceBodies(array $serviceBodies): array
    {
        // Extract parent body ids
        $parentBodyIds = array_column($serviceBodies, 'parent_id');

        // Filter out the unique parent service bodies
        $parentBodies = array_filter($serviceBodies, function ($serviceBody) use ($parentBodyIds) {
            return in_array($serviceBody['id'], $parentBodyIds);
        });

        // Sort by name
        usort($parentBodies, function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });

        // Construct the result array
        $uniqueServiceBodies = array_map(function ($body) {
            return $body['name'] . ',' . $body['id'];
        }, $parentBodies);

        array_unshift($uniqueServiceBodies, 'All Service Bodies,000');

        return $uniqueServiceBodies;
    }

    /**
     * Get a Array representation of service bodies based on filtering criteria.
     *
     * This method takes an array of service bodies and filters them based on the provided criteria.
     * The criteria include the parent_id and show_all_services parameters. It then returns
     * the filtered service bodies.
     *
     * @param array $serviceBodies An array of service bodies, each represented as an associative array.
     * @param string|null $parentId The ID of the parent service body to filter by, or null to include all service bodies.
     * @param string|null $showAllServices A flag to determine whether to include all services (1) or only those with helplines or URLs (null or 0).
     * @param string|null $services (optional) A comma separated list of exact service body ids to display, overrides $parentId when set.
     *
     * @return array An array of filtered service bodies.
     */
    public function getFilteredServiceBodies(array $serviceBodies, $parentId = null, $showAllServices = null, $services = null): array
    {
        // decide if a service body should be added to output
        $shouldAddToOutput = function ($serviceBody) use ($showAllServices) {
            if ($showAllServices == "1") {
                return true;
            }
            return $serviceBody['helpline'] || $serviceBody['url'];
        };

        $output = [];
        $serviceIds = self::parseIds($services);
        $parentIds = self::parseIds($parentId);

        if ($serviceIds) {
            foreach ($serviceBodies as $serviceBody) {
                if (in_array((int) $serviceBody['id'], $serviceIds, true) && $shouldAddToOutput($serviceBody)) {
                    $output[] = $serviceBody;
                }
            }
        } elseif ($parentId === "000") {
            if ($showAllServices == "1") {
                $output = $serviceBodies;
            } else {
                foreach ($serviceBodies as $serviceBody) {
                    if ($shouldAddToOutput($serviceBody)) {
                        $output[] = $serviceBody;
                    }
                }
            }
        } elseif ($parentIds) {
            foreach ($serviceBodies as $serviceBody) {
                if (in_array((int) $serviceBody['parent_id'], $parentIds, true) || in_array((int) $serviceBody['id'], $parentIds, true)) {
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

    /**
     * Retrieve location data based on service body information from a remote server.
     *
     * This method sends a request to a remote server specified by the root_server parameter
     * to retrieve location data for the given service bodies. The location data includes
     * information about the service body, municipality, province, sub-province, city subsection,
     * and neighborhood. The data is returned as a response from the remote server.
     *
     * @param string $rootServer The root URL of the remote server to fetch data from.
     * @param string $serviceBodies A comma-separated list of service bodies for which to retrieve location data.
     *
     * @return array An array containing the location data retrieved from the remote server.
     *               If the remote server responds with an error, an empty array is returned.
     */
    public function getLocations(string $rootServer, string $serviceBodies): array
    {
        $queryParams = [
            'services' => $serviceBodies,
            'data_field_key' => 'service_body_bigint,location_municipality,location_province,location_sub_province,location_city_subsection,location_neighborhood'
        ];

        $response = $this->getRemoteResponse($rootServer, $queryParams);

        if (isset($response['error'])) {
            return [];
        } else {
            return $response;
        }
    }

    /**
     * Get a list of unique, formatted locations based on specified service bodies and data field key.
     *
     * This method takes an array of location data, filters it to include only items with the
     * specified service body, and extracts unique location values based on the provided data field key.
     * It formats and sorts the unique locations alphabetically, and returns them as a comma-separated string.
     *
     * @param array $locations An array of location data, each represented as an associative array.
     * @param mixed $services The service body identifier to filter the location data.
     * @param string $dataFieldKey The key indicating the location data field to extract and format.
     * @param bool $groupByState (optional) Whether to group the locations by state, one line per state.
     *
     * @return string A comma-separated string of unique, formatted locations filtered by service bodies.
     *                When grouped by state, each state is an HTML line prefixed with the state name.
     */
    public function getLocationsList(array $locations, $services, string $dataFieldKey, bool $groupByState = false): string
    {
        $filteredData = array_filter($locations, function ($item) use ($services) {
            return isset($item['service_body_bigint']) && $item['service_body_bigint'] == $services;
        });

        if (!$groupByState) {
            $uniqueLocations = array_unique(array_map(function ($value) use ($dataFieldKey) {
                return $this->formatLocation($value[$dataFieldKey] ?? '');
            }, $filteredData));

            $uniqueLocations = array_filter($uniqueLocations);
            asort($uniqueLocations);
            return implode(', ', $uniqueLocations);
        }

        $grouped = [];
        foreach ($filteredData as $item) {
            $location = $this->formatLocation($item[$dataFieldKey] ?? '');
            if ($location !== '') {
                $grouped[$this->getStateName($item['location_province'] ?? '')][$location] = $location;
            }
        }

        // Sort states by name, keeping locations without a state last
        uksort($grouped, function ($a, $b) {
            return (($a === '') <=> ($b === '')) ?: strnatcasecmp($a, $b);
        });

        $lines = [];
        foreach ($grouped as $state => $stateLocations) {
            asort($stateLocations);
            $line = esc_html(implode(', ', $stateLocations));
            if ($state !== '') {
                $line = '<span class="bmlt_simple_contacts_locations_state_name">' . esc_html($state) . ':</span> ' . $line;
            }
            $lines[] = '<span class="bmlt_simple_contacts_locations_state">' . $line . '</span>';
        }
        return implode('<br>', $lines);
    }

    /**
     * Normalize a location value for display.
     *
     * @param mixed $value The raw location value from the root server.
     * @return string The trimmed, title cased location without periods.
     */
    private function formatLocation($value): string
    {
        return trim(ucwords(str_replace('.', '', strtolower((string) $value))), " \t\n\r\0\x0B,");
    }

    /**
     * Get the display name for a state or province.
     *
     * US abbreviations such as "NC" or "N.C." are expanded to their full name, anything else is title cased.
     *
     * @param mixed $province The location_province value from the root server.
     * @return string The state name, or an empty string if none is set.
     */
    public function getStateName($province): string
    {
        $abbreviation = strtoupper(str_replace('.', '', trim((string) $province)));
        return self::STATE_NAMES[$abbreviation] ?? ucwords(strtolower(trim((string) $province)));
    }
}
