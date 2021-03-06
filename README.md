# URL Helper

## Configuration

You can add a variable prefix to all the available variables below by adding the following to your config.php file:

    $config['url_helper']['prefix'] = 'url:';

## Variables

### `{is_ajax_request}`
Check to see if the current page was requested via ajax.

### `{last_segment}`
Returns the very last segment in the URI, even if it's a pagination segment

### `{last_segment_absolute}`
Returns the very last segment in the URI, but 2nd to last if the last is a pagination segment

### `{last_segment_id}`
Returns the ID of the last segment, in the case of /seg1/seg2/seg3/ it will return "3"

### `{last_segment_absolute_id}`
Return the ID of the last segment, or 2nd to last if the last is a pagination segment. In the case of ### /seg1/seg2/seg3/P5 it will return "3"

### `{parent_segment}`
Will return the 2nd to last segment in the URI. In the case of /seg1/seg2/seg3/, it will return "seg2"

### `{parent_segment_id}`
Returns the ID of the 2nd to last segment, in the case of /seg1/seg2/seg3/ it will return "2"

### `{parent_segment_absolute}`
Returns the 2nd to last segment in the URI, but 3rd to last if the last is a pagination segment

### `{parent_segment_absolute_id}`
Return the ID of the 2nd to last segment, or 3rd to last if the last is a pagination segment. In the case of ### /seg1/seg2/seg3/P5 it will return "2"

### `{all_parent_segments}`
seg1/seg2/seg3 - Will return seg1/seg2

### `{all_segments}`
seg1/seg2/seg3

### `{all_segments_exclude_pagination}`
seg1/seg2/seg3/P10 - Will return seg1/seg2/seg3

### `{page_number}` or `{page_offset}`
Get the integer value from the /Px segment. People refer to it as a page number, but its actually an offset value. Both variables contain the same value, just depends on which nomenclature you want to use.

### `{current_url}`
http://www.mysite.com + segments + query string

### `{current_url_path}`
http://www.mysite.com + segments

### `{current_url_lowercase}`
Same as {current_url} but lowercases it

### `{current_uri}`
Segments + query string

### `{current_url_encoded}`
{current_url} base64encoded

### `{current_uri_encoded}`
{current_uri} base64encoded

### `{query_string}`
Current query string including ?, returns blank if no query string exists

### `{query_string_with_separator}`
Exactly the same as {query_string}, but with a different name incase Mo' Variables overrides it.

### `{referrer}`
Full referring/previous url visited

### `{referrer:segment_N}`
Fetch any segment from the referring url

### `{rev_segment_N}`
Segments reversed

### `{segment_N_category_id}`

### `{segment_N_category_name}`

### `{segment_N_category_name:default}`
If using Publisher, this will provide the default language value

### `{segment_N_category_description}`

### `{segment_N_category_description:default}`
If using Publisher, this will provide the default language value

### `{segment_N_category_image}`

### `{segment_N_category_group_id}`

### `{segment_N_category_url_title:default}`
If using Publisher, this will provide the default language value

### `{segment_N_category_url_title:snake}`
Will provide the url_title in snake case. Useful if you're using dashes to separate words in your url segments. Will turn this-word into this_word.

### `{segment_N_category_url_title:default:snake}`
If using Publisher, this will provide the default language value

### `{last_segment_category_id}`

### `{last_segment_category_name}`

### `{last_segment_category_name:default}`
If using Publisher, this will provide the default language value

### `{last_segment_category_description}`

### `{last_segment_category_description:default}`
If using Publisher, this will provide the default language value

### `{last_segment_category_image}`

### `{last_segment_category_group_id}`

### `{last_segment_category_url_title:default}`
If using Publisher, this will provide the default language value

### `{last_segment_category_url_title:snake}`
Will provide the url_title in snake case. Useful if you're using dashes to separate words in your url segments. Will turn this-word into this_word.

### `{last_segment_category_url_title:default:snake}`
If using Publisher, this will provide the default language value

### `{segment_category_ids}`
2&6&9 - useful for doing an all inclusive search of the segments

### `{segment_category_ids_any}`
2|6|9 - useful for doing an "if any" search of the segments

### `{segment_category_count}`
Provide a total count of categories found in the URL

### `{query}`
Current query string without ?

### `{get:[variable_name]}` or `{param:[variable_name]}`
Grab the value of a $_GET parameter from the URL

### `{scheme}`
http, https, ftp etc

### `{host}`
Your domain name, e.g. localhost, site.com

### `{port}`
Any port number present in the URL, e.g. :80 or :8888

### `{path}`
Full folder/virtural folder path or all segments if your site is located at the root of the domain/vhost

### `{fragment}`
Anything after # in the URI

### `{user}`

### `{pass}`
