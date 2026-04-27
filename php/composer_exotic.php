<?php
/**
 * DeepSID
 * 
 * @used-by		composer.php
 * @used-by		groups.php
 */

/**
 * If a specific end path is inside the exotic folders, replace it with the
 * corresponding HVSC path to get the proper profile data.
 * 
 * @param		string		$collection_path
 * 
 * @return		string		$collection_path
 */
function proxyExotic($collection_path) {

	if (substr($collection_path, 0, 28) == '_Exotic SID Tunes Collection' && substr_count($collection_path, '/') > 1) {

		$folders = substr($collection_path, strlen('_Exotic SID Tunes Collection/Stereo 2SID/'));

		$letter_folder = array(
			'Bayliss_Richard'			=> 'B',
			'Cadaver'					=> 'C',
			'Crowley_Owen'				=> 'C',
			'Crowley_Owen/Worktunes'	=> 'C',
			'Data'						=> 'D',
			'Gallefoss_Glenn'			=> 'B/Blues_Muz',
			'Gantar_Peter'				=> 'G',
			'GI-Joe'					=> 'G',
			'Hannula_Antti'				=> 'H',
			'Hermit'					=> 'H',
			'Hoffmann_Michal'			=> 'H',
			'Jammer'					=> 'J',
			'Jellica'					=> 'J',
			'Kozaki_Soft'				=> 'K',
			'MCH'						=> 'M',
			'MCH/Bab00n'				=> 'M',
			'MovieMovies1'				=> 'M',
			'Nata'						=> 'N',
			'Nobody'					=> 'N',
			'Noplanet'					=> 'N',
			'PCH'						=> 'P',
			'Phobos'					=> 'P',
			'Proton'					=> 'P',
			'Rayden'					=> 'R',
			'Rosenfeldt_Harald'			=> 'R',
			'Scarzix'					=> 'S',
			'Shogoon'					=> 'S',
			'Sidder'					=> 'S',
			'Stepz'						=> 'S',
			'Stinsen'					=> 'S',
			'Surgeon'					=> 'S',
			'Televicious'				=> 'T',
			'TSM'						=> 'T',
			'Uctumi'					=> 'U',
		)[$folders];
		
		// Use the original HVSC profile as a proxy
		$collection_path = '_High Voltage SID Collection/MUSICIANS/'.$letter_folder.'/'.$folders;
	}
	return $collection_path;
}
?>