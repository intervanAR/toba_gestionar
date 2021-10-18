<?php

final class url_helper
{
	public static function exist($url)
	{
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_exec($ch);

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		return $code == 200;
	}
}
