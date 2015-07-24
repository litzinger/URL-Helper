<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
=====================================================
URL Helper Extension for ExpressionEngine 2.0
-----------------------------------------------------
http://www.boldminded.com
-----------------------------------------------------

This is a combination of Bjorn Borresen's last_segment
extension (although last_segment is in EE 2.3+ core),
and Low's seg2cat extension. One hook call,
less to maintain, and less parsing to handle.
http://gotolow.com/addons/low-seg2cat

=====================================================
CHANGELOG

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

class Url_helper_ext {

    var $settings = array();
    var $name = 'URL Helper';
    var $version = '1.0.8';
    var $description = 'Add various URL and segment variables to the Global variables.';
    var $settings_exist = 'n';
    var $docs_url = '';

    var $format = TRUE;

    function Url_helper_ext($settings='')
    {
        $this->settings = $settings;
        $this->EE =& get_instance();

        $this->config = $this->EE->config->item('url_helper') ? $this->EE->config->item('url_helper') : array();
        $this->prefix = isset($this->config['prefix']) ? $this->config['prefix'] : '';
    }

    /**
     * Do the magic.
     */
    function set_url_helper()
    {
        // Save a copy of the array so we don't reverse the global array, oops!
        $segs = $this->EE->uri->segments;

        $qry = (isset($_SERVER['QUERY_STRING']) AND $_SERVER['QUERY_STRING'] != '') ? '?'. $_SERVER['QUERY_STRING'] : '';

        $current_url_path = $this->EE->config->item('site_url') . $this->EE->uri->uri_string;

        $data[$this->prefix.'current_url'] = reduce_double_slashes($current_url_path . $qry);
        $data[$this->prefix.'current_url_path'] = reduce_double_slashes($current_url_path);
        $data[$this->prefix.'current_url_lowercase'] = strtolower($data[$this->prefix.'current_url']);
        $data[$this->prefix.'current_uri'] = reduce_double_slashes('/'. $this->EE->uri->uri_string . $qry);
        $data[$this->prefix.'current_url_encoded'] = base64_encode(reduce_double_slashes($data[$this->prefix.'current_url']));
        $data[$this->prefix.'current_uri_encoded'] = base64_encode(reduce_double_slashes('/'. $this->EE->uri->uri_string . $qry));
        $data[$this->prefix.'query_string'] = $qry;
        $data[$this->prefix.'all_segments'] = implode('/', $segs);
        $data[$this->prefix.'is_ajax_request'] = $this->EE->input->is_ajax_request();

        // Get the full referring URL
        $data[$this->prefix.'referrer'] = ( ! isset($_SERVER['HTTP_REFERER'])) ? '' : $this->EE->security->xss_clean($_SERVER['HTTP_REFERER']);

        // Strip semi-colons from the URL which would otherwise throw a "Disallowed Key Characters" error
        // Stems from a 5 year old bug in CI :/ http://ellislab.com/forums/viewthread/84137/P15
        $data[$this->prefix.'referrer'] = str_replace(';', '', $data[$this->prefix.'referrer']);

        // Now for something fun. Get the referring URL's segments! {referrer:segment_1}, {referrer:segment_2} etc
        $referrer_segments = explode('/', str_replace($this->EE->config->item('site_url'), '', $data[$this->prefix.'referrer']));
        for($i = 1; $i <= 9; $i++)
        {
            $data[$this->prefix.'referrer:segment_'. $i] = (isset($referrer_segments[$i-1])) ? $referrer_segments[$i-1] : '';
        }

        // Get all the URL parts.
        // http://php.net/manual/en/function.parse-url.php
        $url = parse_url($data[$this->prefix.'current_url']);

        $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false;

        foreach($url as $k => $v)
        {
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
        $segment_count = $this->EE->uri->total_segments();
        $last_segment = $this->EE->uri->segment($segment_count);
        $last_segment_id = $segment_count;

        $parent_segment = $this->EE->uri->segment($segment_count-1);
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
        if(substr($last_segment,0,1) == 'P')
        {
            $end = substr($last_segment, 1, strlen($last_segment));

            if ((preg_match( '/^\d*$/', $end) == 1))
            {
                $data[$this->prefix.'all_segments_exclude_pagination'] = implode('/', $segs);

                $last_segment_id = $segment_count-1;
                $last_segment = $this->EE->uri->segment($last_segment_id);

                $parent_segment_id = $segment_count-2;
                $parent_segment = $this->EE->uri->segment($parent_segment_id);

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
        for($i = $last_segment_id; $i >= 1; $i--)
        {
            $data[$this->prefix.'rev_segment_'.$rseg] = $this->EE->uri->segment($i);
            $rseg++;
        }

        // Put everything into global_vars
        $this->EE->config->_global_vars = array_merge($this->EE->config->_global_vars, $data);

        // This is basically the LowSeg2Cat extension.
        $this->set_category_segments();
    }

    private function set_category_segments()
    {
        // Only continue if request is a page and we have segments to check
        if (REQ != 'PAGE' || empty($this->EE->uri->segments)) return;

        // Suggestion by Leevi Graham: check for pattern before continuing
        // if ( !empty($this->settings['uri_pattern']) && !preg_match($this->settings['uri_pattern'], $this->EE->uri->uri_string) ) return;

        // initiate some vars
        $site = $this->EE->config->item('site_id');
        $data = $cats = $segs = array();
        $data[$this->prefix.'segment_category_ids'] = '';
        $data[$this->prefix.'segment_category_ids_any'] = '';

        // loop through segments and set data array thus: segment_1_category_id etc
        foreach ($this->EE->uri->segments AS $nr => $seg)
        {
            $data[$this->prefix.'segment_'.$nr.'_category_id']            = '';
            $data[$this->prefix.'segment_'.$nr.'_category_name']          = '';
            $data[$this->prefix.'segment_'.$nr.'_category_description']   = '';
            $data[$this->prefix.'segment_'.$nr.'_category_image']         = '';
            $data[$this->prefix.'segment_'.$nr.'_category_parent_id']     = '';
            $segs[] = $seg;
        }

        // Compose query, get results
        if (array_key_exists('publisher', $this->EE->addons->get_installed('modules')) && !ee()->publisher_lib->is_default_mode)
        {
            $query = $this->EE->db->select('pc.cat_id, pc.cat_url_title, pc.cat_name, pc.cat_description, pc.cat_image, pc.group_id, c.parent_id')
                                  ->from('publisher_categories AS pc')
                                  ->join('categories AS c', 'c.cat_id = pc.cat_id')
                                  ->where('pc.site_id', $site)
                                  ->where('pc.publisher_lang_id', $this->EE->publisher_lib->lang_id)
                                  ->where('pc.publisher_status', $this->EE->publisher_lib->status)
                                  ->where_in('pc.cat_url_title', $segs)
                                  ->get();
        }
        else
        {
            $query = $this->EE->db->select('cat_id, cat_url_title, cat_name, cat_description, cat_image, parent_id, group_id')
                                  ->from('categories')
                                  ->where('site_id', $site)
                                  ->where_in('cat_url_title', $segs)
                                  ->get();
        }

        // if we have matching categories, continue...
        if ($query->num_rows())
        {
            // Load typography
            $this->EE->load->library('typography');

            // flip segment array to get 'segment_1' => '1'
            $ids = array_flip($this->EE->uri->segments);

            // loop through categories
            foreach ($query->result_array() as $row)
            {
                // overwrite values in data array
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_id']            = $row['cat_id'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_name']          = $this->format ? $this->EE->typography->format_characters($row['cat_name']) : $row['cat_name'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_description']   = $row['cat_description'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_image']         = $row['cat_image'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_parent_id']     = $row['parent_id'];
                $data[$this->prefix.'segment_'.$ids[$row['cat_url_title']].'_category_group_id']     = $row['group_id'];
                $cats[] = $row['cat_id'];

                if($ids[$row['cat_url_title']] == count($ids))
                {
                    $data[$this->prefix.'last_segment_category_id']           = $row['cat_id'];
                    $data[$this->prefix.'last_segment_category_name']         = $this->EE->typography->format_characters($row['cat_name']);
                    $data[$this->prefix.'last_segment_category_description']  = $row['cat_description'];
                    $data[$this->prefix.'last_segment_category_image']        = $row['cat_image'];
                    $data[$this->prefix.'last_segment_category_group_id']        = $row['group_id'];
                }
            }

            // create inclusive stack of all category ids present in segments
            $data[$this->prefix.'segment_category_ids'] = implode('&',$cats);
            $data[$this->prefix.'segment_category_ids_any'] = implode('|',$cats);
        }

        // Add data to global vars
        $this->EE->config->_global_vars = array_merge($this->EE->config->_global_vars, $data);
    }


    /**
     * Install the extension
     */
    function activate_extension()
    {
        // Delete old hooks
        $this->EE->db->query("DELETE FROM exp_extensions WHERE class = '". __CLASS__ ."'");

        // Add new hooks
        $ext_template = array(
            'class'    => __CLASS__,
            'settings' => '',
            'priority' => 8,
            'version'  => $this->version,
            'enabled'  => 'y'
        );

        $extensions = array(
            array('hook'=>'sessions_start', 'method'=>'set_url_helper')
        );

        foreach($extensions as $extension)
        {
            $ext = array_merge($ext_template, $extension);
            $this->EE->db->insert('exp_extensions', $ext);
        }
    }


    /**
     * No updates yet.
     * Manual says this function is required.
     * @param string $current currently installed version
     */
    function update_extension($current = '') {}

    /**
     * Uninstalls extension
     */
    function disable_extension()
    {
        // Delete records
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->delete('exp_extensions');
    }
}
