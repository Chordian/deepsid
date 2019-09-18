<?php
/**
 * DeepSID
 *
 * If a specific end path is inside the exotic folders, replace it with the
 * corresponding HVSC path to get the proper profile data.
 * 
 * @uses		$_GET['fullname'] (to folder)
 */

 function ProxyExotic($fullname) {

	if (substr($fullname, 0, 28) == '_Exotic SID Tunes Collection' && substr_count($fullname, '/') > 1) {

		$folders = substr($fullname, strlen('_Exotic SID Tunes Collection/Stereo 2SID/'));

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
			'Randall'					=> 'R',
			'Rayden'					=> 'R',
			'Rosenfeldt_Harald'			=> 'R',
			'Scarzix'					=> 'S',
			'Shogoon'					=> 'S',
			'Sidder'					=> 'S',
			'Surgeon'					=> 'S',
			'Televicious'				=> 'T',
			'TSM'						=> 'T',
			'Uctumi'					=> 'U',
		)[$folders];
		
		// Use the original HVSC profile as a proxy
		$fullname = '_High Voltage SID Collection/MUSICIANS/'.$letter_folder.'/'.$folders;
	}
	return $fullname;
}
?>