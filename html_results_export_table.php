<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Phoronix Media
	Copyright (C) 2016, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class html_results_export extends pts_module_interface
{
	const module_name = 'Result Exporter To HTML';
	const module_version = '1.0.0';
	const module_description = 'This module allows basic exporting of results to HTML for saving either to a file locally (specified using the EXPORT_RESULTS_HTML_FILE_TO environment variable) or to a mail account (specified using the EXPORT_RESULTS_HTML_EMAIL_TO environment variable). EXPORT_RESULTS_HTML_EMAIL_TO supports multiple email addresses delimited by a comma.';
	const module_author = 'Michael Larabel';

	public static $tableData = '';

	public static function module_environmental_variables()
	{
		return array('EXPORT_RESULTS_HTML_EMAIL_TO', 'EXPORT_RESULTS_HTML_FILE_TO');
	}
	protected static function generate_html_email_results(pts_result_file $result_file)
	{


		$html = '<html><head><title>' . $result_file->get_title() . ' - ASBIS Test Suite</title></head><body>';
		$html .= '<div style="width: 100%; text-align: center; margin: 16px 0;">
   					<img src="http://job.asbis.by/img/asbis-job-logo2.png" alt="">
  				</div>';
		$html .= '<div style="font-size: 24px; font-family: sans-serif; margin-left: 5%;">' . $result_file->get_title() . '</div>';
		$html .= '<div style="font-size: 14px; color: #808080; margin: 0 0 16px 5%; font-family: sans-serif;">' . $result_file->get_description() . '</div>';
		$extra_attributes = array();

		foreach ($result_file->get_systems() as $item) {
			/** @var $item pts_result_file_system */

//			$dataInJson = $item->get_json();

			$hardwareData = explode(',', $item->get_hardware());

			foreach ($hardwareData as $itemHardware) {
				$keyVal = explode(':', $itemHardware);

				if (count($keyVal) == 2) {
                    self::$tableData .= '				
							<tr style="border: 1px solid #ccc;">
								<td style="border: 1px solid #ccc; border-right:0; width: 100px; padding: 4px 8px; text-align: right;">'.$keyVal[0].'</td>
								<td style="border: 1px solid #ccc; padding: 4px 8px;">'.$keyVal[1].'</td>
							</tr>';
				} else {
                    self::$tableData .= '
							<tr style="border: 1px solid #ccc;">
								<td colspan="2" style="padding: 4px 8px;">'.$itemHardware.'</td>
							</tr>';
				}
			}
		}

        $tempData = array_map(function (pts_test_result $data) {
            /** @var pts_test_profile  $testProfile */
            $testProfile = $data->test_profile;

            /** @var pts_test_result_buffer $testResultBufer */
            $testResultBufer = $data->test_result_buffer;

            return '
					<tr style="border: 1px solid #ccc;">							
						<td style="width:100px;  font-size: 14px;  border: 1px solid #ccc;  padding:8px;">'.$testProfile->get_title().'</td>			
						<td style="font-size: 12px; max-width:500px;  border: 1px solid #ccc;  padding:8px;">'.$data->get_arguments_description().'</td>			
						<td style="font-size: 16px; color: #2196F3; background: white; padding: 8px;width: 120px; border: 1px solid #ccc;">'.$testResultBufer->get_values_as_string().' '.$testProfile->get_result_scale().'</td>								
					</tr>';
        }, $result_file->get_result_objects());

        $html .= '<table style="border: 1px solid #808080;border-collapse: collapse;padding: 5px; font-size: 14px;color: #065695; font-family: sans-serif;margin: 0 0 16px 5%; width: 90%">'.self::$tableData.'</table><div style="height:16px;">&nbsp;</div><div style="margin-left:5%; color: #808080; font-size:12px; font-family: sans-serif;">Test results:</div><table style="margin: 0 0 16px 5%; width: 90%;  border: 1px solid;  border-collapse: collapse;  font-family:sans-serif;"><thead><td style="color:#808080; font-size:12px; text-transform:uppercase;border: 1px solid #ccc;padding:8px;">Test name</td><td style="color:#808080; font-size:12px; text-transform:uppercase;border: 1px solid #ccc;padding:8px;">Arguments</td><td style="color:#808080; font-size:12px; text-transform:uppercase;border: 1px solid #ccc;padding:8px;">Value</td></thead><tbody>'.implode('', $tempData).'</tbody></table>';

		// Footer
		$html .= '<hr style="margin: 32px 5% 8px;"/>				
				<div style="font-size: 12px; margin-left: 5%;"><em>Commercial support, custom engineering, and other services are available by contacting ASBIS.<br />&copy; ' . date('Y') . ' ASBIS.</em></div>';
		$html .= '</body></html>';

		return $html;
	}
	public static function __event_results_saved($test_run_manager)
	{
		$html_file = pts_module::read_variable('EXPORT_RESULTS_HTML_FILE_TO');
		$emails = pts_strings::comma_explode(pts_module::read_variable('EXPORT_RESULTS_HTML_EMAIL_TO'));

		$html_contents = self::generate_html_email_results($test_run_manager->result_file);


		if(!empty($html_file))
		{
			file_put_contents($html_file, $html_contents);
			echo 'HTML Result File To: ' . $html_file . PHP_EOL;
		}

		if(!empty($emails))
		{
			//$pdf_contents = pts_result_file_output::result_file_to_pdf($test_run_manager->result_file, 'pts-test-results.pdf', 'S');
			//$pdf_contents = chunk_split(base64_encode($pdf_contents));

			foreach($emails as $email)
			{

				/*$boundary = md5(uniqid(time()));
				$headers = "From: Phoronix Test Suite <no-reply@phoromatic.com>\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n\r\n";
				$message = "This is a multi-part message in MIME format.\r\n";
				$message .= "--" . $boundary . "\r\n";
				$message .= "Content-Type: text/html; charset=utf-8\r\n";
				$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$message .= $html_contents . "\r\n\r\n";
				$message .= "--" . $boundary . "\r\n";
				$message .= "Content-Type: application/pdf; name=\"pts-test-results.pdf\"\r\n";
				$message .= "Content-Transfer-Encoding: base64\r\n";
				$message .= "Content-Disposition: attachment; filename=\"pts-test-results.pdf\"\r\n\r\n";
				$message .= $pdf_contents . "\r\n\r\n";
				$message .= "--" . $boundary . "--";

				mail($email, 'Phoronix Test Suite Result File: ' . $test_run_manager->result_file->get_title(), $message, $headers);
				echo 'HTML Results Emailed To: ' . $email . PHP_EOL; */
				$pdf_contents = shell_exec('dmidecode');
				$boundary = md5(uniqid(time()));
				$headers = "From: Phoronix Test Suite <no-reply@phoromatic.com>\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n\r\n";
				$message = "This is a multi-part message in MIME format.\r\n";
				$message .= "--" . $boundary . "\r\n";
				$message .= "Content-Type: text/html; charset=utf-8\r\n";
				$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$message .= $html_contents . "\r\n\r\n";
				$message .= "--" . $boundary . "\r\n";
				$message .= "Content-Type: application/pdf; name=\"dmidecode.pdf\"\r\n";
				$message .= "Content-Transfer-Encoding: base64\r\n";
				$message .= "Content-Disposition: attachment; filename=\"dmidecode.pdf\"\r\n\r\n";
				$message .= $pdf_contents . "\r\n\r\n";
				$message .= "--" . $boundary . "--";
				$separator = md5(time());

				// carriage return type (RFC)
				$eol = "\r\n";
				$headers = "MIME-Version: 1.0\r\n";
				$headers .= "Content-type:multipart/mixed;boundary=\"" . $separator . "\"" . $eol;
				$headers .= "From: Phoromatic - ASBIS Test Suite <no-reply@phoromatic.com>\r\n";
				
				mail($email, 'ASBIS Test Suite Result File: ' . $test_run_manager->result_file->get_title(), $message, $headers);
				echo 'HTML Results Emailed To: ' . $email . PHP_EOL;
			}
		}
	}
}

?>
