<?php
/**
* If any img src of an image is an http link, just proxy it
* 
* This solves HTTPS mixed content problems when TT-RSS runs with HTTPS only
* 
* heavily inspired by https://github.com/Alekc/af_refspoof
* heavily inspired by https://tt-rss.org/gitlab/fox/tt-rss/blob/master/plugins/af_zz_imgsetsizes/init.php
*/
class af_https_proxy_assets extends Plugin
{
	protected $host;

	function about()
	{
		return array(
			'1.0',
			'replace image links by adding an proxy before',
			'campino2k',
		);
	}
	function flags()
	{
		return array('needs_curl' => true );
	}
	
	function init($host)
	{
		require_once ('PhCURL.php');
	        $this->host = $host;
	        #$this->dbh = Db::get();
	        $host->add_hook($host::HOOK_RENDER_ARTICLE_CDM, $this);
	}

	function hook_render_article_cdm($article)
	{
		$doc = new DOMDocument();
		$doc->loadHTML($charset_hack . $article["content"]);

		$found = false;

		if ($doc) {
			$xpath = new DOMXpath($doc);

			$images = $xpath->query('(//img[@src])');

			foreach ($images as $img) {
				$src = $img->getAttribute("src");
				// only replace path if it's a http path
				if( strpos( $src, 'http:' ) === 0 ) {
					$src = substr( $src, 7 );
					//$src = rawurlencode( $src );
					
					$proxy_url ="/backend.php?op=pluginhandler&method=proxy&plugin=af_https_proxy_assets&url={$src}";
					$img->setAttribute('src', $proxy_url);

				}

				//$srcset = $img->getAttribute("srcset");

				$srcset = str_replace(
					'http://',
					'/backend.php?op=pluginhandler&method=proxy&plugin=af_https_proxy_assets&url=',
					$srcset

				);
				$img->setAttribute('srcset', $srcset );


			}

			$article["content"] = $doc->saveHTML();
		}

		return $article;

	}

	function proxy()
	{
		//$target_url = $_REQUEST['url'];
		$target_url = str_replace(' ', '%20', urldecode( $_REQUEST['url'] ));
		$client = new PhCURL('http://' . $target_url);
		$client->loadCommonSettings();
		$client->enableBinaryTransfer(true);
		$client->enableAutoReferer(true);
		$client->enableHeaderInOutput(false);
		$client->setUserAgent();
		$client->GET();
		ob_end_clean();
		header("Content-Type: ". $client->getContentType());
		echo $client->getData();
		exit(1);

	}
	
	function api_version()
	{
		return 2;
	}
}
