<?php
function queryMovieAPI($search,$type,$page=1,$date=0) {
	global $db;
	$apikey = $db->query("SELECT * FROM settings WHERE name='apiKey' LIMIT 1");	//fetch day of last successful update
	$apikey = mysqli_fetch_array($apikey);
	$apikey = $apikey['value'];

	$q = urlencode($search); // make sure to url encode an query parameters
	switch($type) {
		case 'search':
			$data = curl_get('http://api.themoviedb.org/3/search/movie?api_key=' . $apikey . '&search_type=ngram&query=' . $q . '&page=' .$page);
			break;
		case 'movie':
			$data = curl_get('http://api.themoviedb.org/3/movie/'.$q.'?api_key=' . $apikey . '&append_to_response=trailers,casts,keywords,similar_movies,alternative_titles');
			break;
		case 'changes':
			if ($search == 0) {
				if ($date != 0) {
					$data = curl_get('http://api.themoviedb.org/3/movie/changes?api_key=' . $apikey . '&page=' . $page . '&start_date=' . $date . '&end_date=' . date('Y-m-d'));
					echo 'http://api.themoviedb.org/3/movie/changes?api_key=' . $apikey . '&page=' . $page . '&start_date=' . $date . '&end_date=' . date('Y-m-d');
				}
				else {
					$data = curl_get('http://api.themoviedb.org/3/movie/changes?api_key=' . $apikey . '&page=' . $page);
				}
			}
			else {$data = curl_get('http://api.themoviedb.org/3/movie/' . $search . '/changes?api_key=' . $apikey . '&page=' . $page);}
			break;
		case 'configuration':
			echo "http://api.themoviedb.org/3/configuration?api_key=".$apikey;
			$data = curl_get('http://api.themoviedb.org/3/configuration?api_key=' . $apikey . '&page=' . $page);
			break;
		case 'upcoming':
			$data = curl_get('http://api.themoviedb.org/3/movie/upcoming?api_key=' . $apikey . '&page=' . $page);
			break;
		case 'now_playing':
			$data = curl_get('http://api.themoviedb.org/3/movie/now_playing?api_key=' . $apikey . '&page=' . $page);
			break;
		case 'popular':
			$data = curl_get('http://api.themoviedb.org/3/movie/popular?api_key=' . $apikey . '&page=' . $page);
			break;
		case 'top_rated':
			$data = curl_get('http://api.themoviedb.org/3/movie/top_rated?api_key=' . $apikey . '&page=' . $page);
			break;
		case 'popular_actors':
			$data = curl_get('http://api.themoviedb.org/3/person/popular?api_key=' . $apikey . '&page=' . $page);
			break;
		default:
			return NULL;
	}
	// decode the json data to make it easier to parse the php
	$search_results = json_decode($data);
	if ($search_results === NULL) {elog($search.' 403 error'); return null;}
	if (property_exists($search_results,'status_code')) {if ($search_results->status_code == '6') {elog($search.' does not exist'); return null;}}
	return $search_results;
}
?>