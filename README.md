# URL Helper

## Configuration

You can add a variable prefix to all the available variables below by adding the following to your config.php file:

    $config['url_helper']['prefix'] = 'url:';

## Variables

### {is_ajax_request}
check to see if the current page was requested via ajax.

### {last_segment}
returns the very last segment in the URI, even if it's a pagination segment

### {last_segment_absolute}
returns the very last segment in the URI, but 2nd to last if the last is a pagination segment

### {last_segment_id}
returns the ID of the last segment, in the case of /seg1/seg2/seg3/ it will return "3"

### {last_segment_absolute_id}
return the ID of the last segment, or 2nd to last if the last is a pagination segment. In the case of ### /seg1/seg2/seg3/P5 it will return "3"

### {parent_segment}
Will return the 2nd to last segment in the URI. In the case of /seg1/seg2/seg3/, it will return "seg2"

### {parent_segment_id}
returns the ID of the 2nd to last segment, in the case of /seg1/seg2/seg3/ it will return "2"

### {parent_segment_absolute}
returns the 2nd to last segment in the URI, but 3rd to last if the last is a pagination segment

### {parent_segment_absolute_id}
return the ID of the 2nd to last segment, or 3rd to last if the last is a pagination segment. In the case of ### /seg1/seg2/seg3/P5 it will return "2"

### {all_parent_segments}
seg1/seg2/seg3 - Will return seg1/seg2

### {all_segments}
seg1/seg2/seg3

### {all_segments_exclude_pagination}
seg1/seg2/seg3/P10 - Will return seg1/seg2/seg3

### {current_url}
http://www.mysite.com + segments + query string

### {current_url_path}
http://www.mysite.com + segments

### {current_uri}
segments + query string

### {current_url_encoded}
{current_url} base64encoded

### {current_uri_encoded}
{current_uri} base64encoded

### {query_string}
current query string including ?, returns blank if no query string exists

### {referrer}
full referring/previous url visited

### {referrer:segment_N}
fetch any segment from the referring url

### {rev_segment_N}
segments reversed

### {segment_N_category_id}

### {segment_N_category_name}

### {segment_N_category_description}

### {segment_N_category_image}

### {segment_X_category_group_id}

### {last_segment_category_id}

### {last_segment_category_name}

### {last_segment_category_description}

### {last_segment_category_image}

### {last_segment_category_group_id}

### {segment_category_ids}
2&6&9 - useful for doing an all inclusive search of the segments

### {segment_category_ids_any}
2|6|9 - useful for doing an "if any" search of the segments

### {query}
current query string without ?

### {scheme}
http, https, ftp etc

### {host}
your domain name, e.g. localhost, site.com

### {port}
any port number present in the URL, e.g. :80 or :8888

### {path}
full folder/virtural folder path or all segments if your site is located at the root of the domain/vhost

### {fragment}
anything after # in the URI

### {user}

### {pass}
