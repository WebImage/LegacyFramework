<?php
/**
 * 6/5/2009 - 32,973 Lines
 * 1/8/2010 - 39,703 Lines
 * 7/26/2010 - 50,057 Lines (Vtips += 12,379)
 * 9/17/2010 - 47,738 Lines (VTips += 16,738)
 */
$file_count = 0;
$total_file_size = 0;
function getDir($dir_path, $level=0) {
	global $file_count, $total_file_size;
	$source_files = array('php', 'html', 'tpl');
	$line_count = 0;
	$dh = opendir($dir_path);
	while ($file = readdir($dh)) {
		if (!in_array($file, array('.', '..'))) {
			$full_path = $dir_path . $file;
			#for ($s=0; $s < $level; $s++) {
			#	echo '--';
			#}
			
			
			if (filetype($full_path) == 'dir') {
				$sub_dir = $full_path . '/';
				#echo $full_path . '<br />';
				#if ($level <= 0) {
					$line_count += getDir($sub_dir, $level+1);
				#}
			} else { // File
				$extension = substr($file, strrpos($file, '.')+1);
				if (in_array($extension, $source_files)) {
					$file_count += 1;
					$file_contents = file_get_contents($full_path);
					$file_line_count = count(explode("\n", $file_contents));
					$line_count += $file_line_count;
					#$total_file_size += filesize($full_path);
					#echo 'Total File Size: ' . $total_file_size . '<br />';
					#echo $full_path . ' (lines: ' . $file_line_count . ')<br />';
				}
			}
		}
	}
	closedir($dh);
	return $line_count;
}
$line_count_base = getDir(ConfigurationManager::get('DIR_FS_FRAMEWORK_BASE'));
$line_count_app = getDir(ConfigurationManager::get('DIR_FS_FRAMEWORK_APP'));
echo '<div style="border:1px solid #ccc;padding:8px;background-color:#e1e1e1;margin:5px;">Total # of Files: ' . $file_count . '</div>';
echo '<div style="border:1px solid #ccc;padding:8px;background-color:#e1e1e1;margin:5px;">Base Lines: ' . number_format($line_count_base) . '</div>';
echo '<div style="border:1px solid #ccc;padding:8px;background-color:#e1e1e1;margin:5px;">App Lines: ' . number_format($line_count_app) . '</div>';
echo '<div style="border:1px solid #ccc;padding:8px;background-color:#e1e1e1;margin:5px;">Total Lines: ' . number_format($line_count_base + $line_count_app) . '</div>';
echo '<div style="border:1px solid #ccc;padding:8px;background-color:#e1e1e1;margin:5px;">Total Size: ' . $total_file_size . '</div>';
?>