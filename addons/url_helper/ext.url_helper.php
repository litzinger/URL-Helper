<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
=====================================================
URL Helper Extension for ExpressionEngine 3 & 4
-----------------------------------------------------
http://www.boldminded.com
-----------------------------------------------------

This is a combination of Bjorn Borresen's last_segment extension (although last_segment is in EE 2.3+ core),
and Low's seg2cat extension. One hook call, less to maintain, and less parsing to handle. http://gotolow.com/addons/low-seg2cat
Also supports the Publisher module for translated category urls.

=====================================================
CHANGELOG

1.16.0 - Added {query_string_with_separator} b/c Mo Variables overrides {query_string}, but without the ?
         and ExpressionEngine creates {current_query_string} also without the ?
1.15.0 - Code cleanup
1.14.0 - Switched to core_boot() hook instead of session_start()
       - Dropped support for EE2. Updated call to ee('Security/XSS')->clean()
1.13.0 - Added snake case modifier to the cat_url_title, e.g. {segment_X_category_url_title:snake}
       - Added :default modifiers to the name, url_title, and description values, e.g. {segment_x_category_name:default}
1.12.0 - Added {segment_category_count}, which displays the total number of category segments found in the URL
1.11.0 - Added {page_number} and {page_offset} to get the integer value from the /Px segment
1.10.0 - Updated support for Publisher 2 in EE3
1.0.9 - Changed all references of $this->EE to ee()
1.0.8 - Added {current_url_lowercase} to assist with canonical URLs in environments that may have caps in URLs
1.0.7 - Added {segment_X_category_group_id}, {last_segment_category_group_id} - Nick Benson
1.0.6 - Added {all_segments_exclude_pagination} - Nick Benson
1.0.5 - Fixed bug with Publisher (a previously available constant was changed to a class property)
1.0.4 - Added support for Publisher
1.0.3 - Added reverse segments - Isaac Raway
1.0.2 - Fix for last_segment_absolute - Thanks Dylan Tuohy
1.0.1 - Removed slashes in {all_segments} var. Didn't play nice when used in conjunction with {site_url}

=====================================================
*/

class Url_helper_ext
{

    /**
     * @var array
     */
    public $settings = [];

    /**
     * @var bool
     */
    private $format = true;

    /**
     * @var string
     */
    public $version = URL_HELPER_VERSION;

    /**
     * @param string $settings
     */
    function __construct($settings = '')
    {
        $this->settings = $settings;

        $this->config = ee()->config->item('url_helper') ? ee()->config->item('url_helper') : [];
        $this->prefix = isset($this->config['prefix']) ? $this->config['prefix'] : '';
    }

    /**
     * Do the magic.
     */
    public function core_boot()
    {
        // Save a copy of the array so we don't reverse the global array, oops!
        $segs = ee()->uri->segments;

        $qry = (isset($_SERVER['QUERY_STRING']) AND $_SERVER['QUERY_STRING'] != '') ? '?'. $_SERVER['QUERY_STRING'] : '';

        $current_url_path = ee()->config->item('site_url') . ee()->uri->uri_string;

        $data[$this->prefix.'all_segments'] = implode('/', $segs);   
        $data[$this->prefix.'current_url'] = reduce_double_slashes($current_url_path . $qry);
        $data[$this->prefix.'current_url_path'] = reduce_double_slashes($current_url_path);
        $data[$this->prefix.'current_url_lowercase'] = strtolower($data[$this->prefix.'current_url']);
        $data[$this->prefix.'current_uri'] = reduce_double_slashes('/'. ee()->uri->uri_string . $qry);
        $data[$this->prefix.'current_url_encoded'] = base64_encode(reduce_double_slashes($data[$this->prefix.'current_url']));
        $data[$this->prefix.'current_uri_encoded'] = base64_encode(reduce_double_slashes('/'. ee()->uri->uri_string . $qry));
        $data[$this->prefix.'is_ajax_request'] = ee()->input->is_ajax_request();
        // 2 variables, same value, because Mo' Variables can override {query_string} :(
        $data[$this->prefix.'query_string'] = $qry;
        $data[$this->prefix.'query_string_with_separator'] = $qry;

        // Get the full referring URL
        $data[$this->prefix.'referrer'] = ( ! isset($_SERVER['HTTP_REFERER'])) ? '' : ee('Security/XSS')->clean($_SERVER['HTTP_REFERER']);

        // Strip semi-colons from the URL which would otherwise throw a "Disallowed Key Characters" error
        // Stems from a 5 year old bug in CI :/ http://ellislab.com/forums/viewthread/84137/P15
        $data[$this->prefix.'referrer'] = str_replace(';', '', $data[$this->prefix.'referrer']);

        // Now for something fun. Get the referring URL's segments! {referrer:segment_1}, {referrer:segment_2} etc
        $referrer_segments = explode('/', str_replace(ee()->config->item('site_url'), '', $data[$this->prefix.'referrer']));

        for($i = 1; $i <= 9; $i++) {
            $data[$this->prefix.'referrer:segment_'. $i] = (isset($referrer_segments[$i-1])) ? $referrer_segments[$i-1] : '';
        }

        array_map(function($segment) use (&$data) {
            if (preg_match('/P(\d+)/',$segment, $matches)) {
                $data[$this->prefix.'page_number'] = $matches[1];
                $data[$this->prefix.'page_offset'] = $matches[1];
            }
        }, $segs);

        // Get all the URL parts.
        // http://php.net/manual/en/function.parse-url.php
        $url = parse_url($data[$this->prefix.'current_url']);

        $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false;

        foreach($url as $k => $v) {
            if ($k == 'scheme' AND $is_https) $v = 'https';
            $data[$this->prefix.$k] = $v;
        }

        // Do a few things to get the parent segment, and only the parent segment
        // This could be helpful if we're 5 levels deep in the URL, and just need
        // the immediate parent, but don't know how deep we are.

        // Get rid of the last segment, which is our current page.
        array_pop($segs);

        $data[$this->prefix.'all_parent_segments'] = implode('/', $segs);

        // Figure out the last_segment and parent segments. Taken from Bjorn Borresen's last_segment add-on
        $segment_count = ee()->uri->total_segments();
        $last_segment = ee()->uri->segment($segment_count);
        $last_segment_id = $segment_count;

        $parent_segment = ee()->uri->segment($segment_count-1);
        // If we are at least 2 segments deep, then set the ID, else set it to 0
        $parent_segment_id = $segment_count > 1 ? $segment_count-1 : 0;

        // Get the last_segment, might include a /P segment
        $data[$this->prefix.'last_segment'] = $last_segment;
        $data[$this->prefix.'last_segment_id'] = $last_segment_id;

        // Get the parent_segment, might include a /P segment
        $data[$this->prefix.'parent_segment'] = $parent_segment;
        $data[$this->prefix.'parent_segment_id'] = $parent_segment_id;
        $all_segments_absolute = $data[$this->prefix.'all_segments'];

        // Set default value for all_segments_exclude_pagination
        $data[$this->prefix.'all_segments_exclude_pagination'] = $data[$this->prefix.'all_segments'];

        // Get the last_segment, parent_segment and parent_segment prior to a /P segment
        if(substr($last_segment,0,1) == 'P') {
            $end = substr($last_segment, 1, strlen($last_segment));

            if ((preg_match( '/^\d*$/', $end) == 1)) {
                $data[$this->prefix.'all_segments_exclude_pagination'] = implode('/', $segs);

                $last_segment_id = $segment_count-1;
                $last_segment = ee()->uri->segment($last_segment_id);

                $parent_segment_id = $segment_count-2;
                $parent_segment = ee()->uri->segment($parent_segment_id);

                $all_segments_absolute = $data[$this->prefix.'all_parent_segments'];
            }
        }

        $data[$this->prefix.'last_segment_absolute'] = $last_segment;
        $data[$this->prefix.'last_segment_absolute_id'] = $last_segment_id;

        $data[$this->prefix.'parent_segment_absolute'] = $parent_segment;
        $data[$this->prefix.'parent_segment_absolute_id'] = $parent_segment_id;

        // Get all segments, apart from if the last one is a Pagination segment
        $data[$this->prefix.'all_segments_absolute'] = $all_segments_absolute;

        $rseg = 1;
        for($i = $last_segment_id; $i >= 1; $i--) {
            $data[$this->prefix.'rev_segment_'.$rseg] = ee()->uri->segment($i);
            $rseg++;
        }

        // Put everything into global_vars
        ee()->config->_global_vars = array_merge(ee()->config->_global_vars, $data);

        // This is basically the LowSeg2Cat extension.
        $this->setCategorySegments();
    }

    private function setCategorySegments()
    {
        // Only continue if request is a page and we have segments to check
        if (REQ != 'PAGE' || empty(ee()->uri->segments)) return;

        // initiate some vars
        $site = ee()->config->item('site_id');
        $data = array();
        $cats = array();
        $segs = array();
        $data[$this->prefix.'segment_category_ids'] = '';
        $data[$this->prefix.'segment_category_ids_any'] = '';
        $data[$this->prefix.'segment_category_count'] = '';

        // Load typography
        ee()->load->library('typography');

        // Set defaults so variable usage returns false if used, even if they are not set.
        foreach (ee()->uri->segments AS $nr => $seg) {
            $data[$this->prefix.'segment_'.$nr.'_category_id'] = '';
            $data[$this->prefix.'segment_'.$nr.'_category_name'] = '';
            $data[$this->prefix.'segment_'.$nr.'_category_name:default'] = '';
            $data[$this->prefix.'segment_'.$nr.'_category_description'] = '';
            $data[$this->prefix.'segment_'.$nr.'_category_description:default'] = '';
            $data[$this->prefix.'segment_'.$nr.'_category_image'] = '';
            $data[$this->prefix.'segment_'.$nr.'_category_parent_id'] = '';
            $data[$this->prefix.'segment_'.$nr.'_category_url_title:default'] = '';
            $data[$this->prefix.'segment_'.$nr.'_category_url_title:snake'] = '';
            $data[$this->prefix.'segment_'.$nr.'_category_url_title:default:snake'] = '';
            $segs[] = $seg;
        }

        $columns = array(
            'cat_id',
            'cat_url_title',
            'cat_name',
            'cat_description',
            'cat_image',
            'group_id',
            'parent_id',
        );

        /** @var CI_DB_result $query */
        $query = ee()->db->select(implode(', ', $columns))
            ->from('categories')
            ->where('site_id', $site)
            ->where_in('cat_url_title', $segs)
            ->get();

        // if we have matching categories, continue...
        if ($query->num_rows())
        {
            // flip segment array to get 'segment_1' => '1'
            $ids = array_flip(ee()->uri->segments);

            // loop through categories
            foreach ($query->result_array() as $row) {
                // overwrite values in data array
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_id'] = $row['cat_id'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_name'] = $this->format ? ee()->typography->format_characters($row['cat_name']) : $row['cat_name'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_description'] = $row['cat_description'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_image'] = $row['cat_image'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_parent_id'] = $row['parent_id'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_group_id'] = $row['group_id'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_url_title:snake'] = $this->snakeCase($row['cat_url_title']);
                $cats[] = $row['cat_id'];

                if($ids[$row['cat_url_title']] == count($ids)) {
                    $data[$this->prefix.'last_segment_category_id'] = $row['cat_id'];
                    $data[$this->prefix.'last_segment_category_name'] = ee()->typography->format_characters($row['cat_name']);
                    $data[$this->prefix.'last_segment_category_description'] = $row['cat_description'];
                    $data[$this->prefix.'last_segment_category_image'] = $row['cat_image'];
                    $data[$this->prefix.'last_segment_category_group_id'] = $row['group_id'];
                    $data[$this->prefix.'last_segment_category_url_title:snake'] = $this->snakeCase($row['cat_url_title']);
                }
            }

            $cats = array_unique($cats);

            // create inclusive stack of all category ids present in segments
            $data[$this->prefix.'segment_category_ids'] = implode('&',$cats);
            $data[$this->prefix.'segment_category_ids_any'] = implode('|',$cats);
            $data[$this->prefix.'segment_category_count'] = count($cats);
        }

        $publisherVersion = 2;
        $isPublisherInstalled = array_key_exists('publisher', ee()->addons->get_installed('modules'));
        $defaultMode = true;

        if ($isPublisherInstalled) {
            if (version_compare(APP_VER, '3.0.0', '<')) {
                $langId = ee()->publisher_lib->lang_id;
                $status = ee()->publisher_lib->status;
                $defaultMode = ee()->publisher_lib->is_default_mode;
                $publisherVersion = 1;
            } else {
                /** @var \BoldMinded\Publisher\Service\Request $request */
                $request = ee(\BoldMinded\Publisher\Service\Request::NAME);
                $langId = $request->getCurrentLanguage()->getId();
                $status = $request->getCurrentStatus();
                $defaultMode = $request->isDefaultMode();
            }
        }

        // Compose query, get results
        if ($isPublisherInstalled && !$defaultMode) {
            $columnPrefix = '';

            // EE 2.0
            if ($publisherVersion === 1) {
                $columnPrefix = 'publisher_';
            }

            $columns = array(
                'pc.cat_id',
                'pc.cat_url_title',
                'pc.cat_name',
                'pc.cat_description',
                'pc.cat_image',
                'pc.group_id',
                'c.parent_id',
                'c.cat_url_title AS cat_url_title_default',
                'c.cat_name AS cat_name_default',
                'c.cat_description AS cat_description_default',
            );

            /** @var CI_DB_result $query */
            $query = ee()->db->select(implode(', ', $columns))
                ->from('publisher_categories AS pc')
                ->join('categories AS c', 'c.cat_id = pc.cat_id')
                ->where('pc.site_id', $site)
                ->where('pc.'. $columnPrefix .'lang_id', $langId)
                ->where('pc.'. $columnPrefix .'status', $status)
                ->where_in('pc.cat_url_title', $segs)
                ->get();

            if ($query->num_rows()) {
                // flip segment array to get 'segment_1' => '1'
                $ids = array_flip(ee()->uri->segments);

                // loop through categories
                foreach ($query->result_array() as $row) {
                    // overwrite values in data array
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_id'] = $row['cat_id'];
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_name'] = $this->formatCharacters($row['cat_name']);
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_name:default'] = $this->formatCharacters($row['cat_name_default']);
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_description'] = $this->formatCharacters($row['cat_description']);
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_description:default'] = $this->formatCharacters($row['cat_description_default']);
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_image'] = $row['cat_image'];
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_parent_id'] = $row['parent_id'];
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_group_id'] = $row['group_id'];
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_url_title:snake'] = $this->snakeCase($row['cat_url_title']);
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_url_title:default'] = $row['cat_url_title_default'];
                    $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_url_title:default:snake'] = $this->snakeCase($row['cat_url_title_default']);
                    $cats[] = $row['cat_id'];

                    if($ids[$row['cat_url_title']] == count($ids)) {
                        $data[$this->prefix.'last_segment_category_id'] = $row['cat_id'];
                        $data[$this->prefix.'last_segment_category_name'] = $this->formatCharacters($row['cat_name']);
                        $data[$this->prefix.'last_segment_category_name:default'] = $this->formatCharacters($row['cat_name_default']);
                        $data[$this->prefix.'last_segment_category_description'] = $this->formatCharacters($row['cat_description']);
                        $data[$this->prefix.'last_segment_category_description:default'] = $this->formatCharacters($row['cat_description_default']);
                        $data[$this->prefix.'last_segment_category_image'] = $row['cat_image'];
                        $data[$this->prefix.'last_segment_category_group_id'] = $row['group_id'];
                        $data[$this->prefix.'last_segment_category_url_title:snake'] = $this->snakeCase($row['cat_url_title']);
                        $data[$this->prefix.'last_segment_category_url_title:default'] = $row['cat_url_title_default'];
                        $data[$this->prefix.'last_segment_category_url_title:default:snake'] = $this->snakeCase($row['cat_url_title_default']);
                    }
                }

                $cats = array_unique($cats);

                // create inclusive stack of all category ids present in segments
                $data[$this->prefix.'segment_category_ids'] = implode('&',$cats);
                $data[$this->prefix.'segment_category_ids_any'] = implode('|',$cats);
                $data[$this->prefix.'segment_category_count'] = count($cats);
            }
        }

        // Add data to global vars
        ee()->config->_global_vars = array_merge(ee()->config->_global_vars, $data);
    }


    /**
     * Install the extension
     */
    function activate_extension()
    {
        // Delete old hooks
        ee()->db->query("DELETE FROM exp_extensions WHERE class = '". __CLASS__ ."'");

        // Add new hooks
        $ext_template = array(
            'class'    => __CLASS__,
            'settings' => '',
            'priority' => 8,
            'version'  => $this->version,
            'enabled'  => 'y'
        );

        $extensions = array(
            array('hook'=>'core_boot', 'method'=>'core_boot')
        );

        foreach($extensions as $extension) {
            $ext = array_merge($ext_template, $extension);
            ee()->db->insert('exp_extensions', $ext);
        }
    }


    /**
     * No updates yet.
     * Manual says this function is required.
     * @param string $current currently installed version
     */
    function update_extension($current = '')
    {
        if (version_compare($current, $this->version, '<')) {
            // Perform a re-install to update the hook name
            $this->activate_extension();
        }
    }

    /**
     * Uninstalls extension
     */
    function disable_extension()
    {
        // Delete records
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('exp_extensions');
    }

    /**
     * @param $str
     * @return string
     */
    private function snakeCase($str)
    {
        return strtolower(preg_replace("/[^a-zA-Z0-9]/", '_', $str));
    }

    /**
     * @param $str
     * @return mixed
     */
    private function formatCharacters($str)
    {
        if ($this->format) {
            return ee()->typography->format_characters($str);
        }

        return $str;
    }
}
