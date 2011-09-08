<?php
class URLShorten
{
	private $url;
	private $SCHEMES;
	private $URL_FORMAT;
	private $apiKey; //Your API key if any
	
	/** The constructor would setup some regular expressions for extracting URLs from a given text. **/
	function __construct($key=null)
	{
		$this->SCHEMES = array('http', 'https', 'ftp', 'mailto', 'news',
		'gopher', 'nntp', 'telnet', 'wais', 'prospero', 'aim', 'webcal');
		// Note: fragment id is uchar | reserved, see rfc 1738 page 19
		// %% for % because of string formating
		// puncuation = ? , ; . : !
		// if punctuation is at the end, then don't include it
		$this->URL_FORMAT = '~(?<!\w)((?:'.implode('|',
			$this->SCHEMES).'):' # protocol + :
		.   '/*(?!/)(?:' # get any starting /'s
		.   '[\w$\+\*@&=\-/]' # reserved | unreserved
		.   '|%%[a-fA-F0-9]{2}' # escape
		.   '|[\?\.:\(\),;!\'](?!(?:\s|$))' # punctuation
		.   '|(?:(?<=[^/:]{2})#)' # fragment id
		.   '){2,}' # at least two characters in the main url part
		.   ')~';
		
		$this->apiKey = $key; //Set the API key here. 
	}
	
	function extract_url($text)
	{
		preg_match($this->URL_FORMAT, $text, $matches);
		return $matches;
	}
	
	function shorten_and_replace($text)
	{
		
		$urls = $this->extract_url($text); 
		foreach($urls as $url)
		{
			$shorten = $this->shorten($url); 
			$text = str_replace($url, "<a target='_new' href='$shorten'>$shorten</a>", $text);
		}
		
		return $text;
	}
	
	function shorten($url)
	{
		
		if($this->security_check($url))
		{
			$api = 'http://api.bitly.com/v3/shorten';
			$fields = array(
						'longUrl'=>urlencode($url),
						'login'=>'forbash',
						'apiKey'=>$this->apiKey,
						'format'=>'json'
						
					);

			//url-ify the data for the POST
			$fields_string="";
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string,'&');
			$api .="?".$fields_string;
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch,CURLOPT_URL,$api);
			$c = curl_exec($ch); 
			curl_close($ch);
			$response = json_decode($c);
			if($response->status_code != "200")
			{
				return $url;
			}
			else
			{
				return $response->data->url;
			}
		}
		else
		{
			return "INSECURE_URL"; 
		}
	}
	
	function expand($url)
	{
		
	}
	
	function security_check($url)
	{
		return true;
	}
}
