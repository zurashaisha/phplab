<?php
class ip_module
{
	public $address;
	function set_ip($string) {
		$this->ip = $string;
	  }
	function IPv4Mask($address) {
		#$address = $this->ip;
		if (preg_match('[/]', $address)) {
		$ipex=explode("/", $address);
		$Masktobin = str_pad(str_pad("", $ipex[1], "1"), 32, "0");
		#if ($binin=="N/A") return $binin;
		$binin=explode(".", chunk_split($Masktobin,8,"."));
		for ($i=0; $i<4 ; $i++) {
		$Mask[$i]=bindec($binin[$i]);
		}
        	return implode(".",$Mask) ; }
		 
		else if (preg_match("[ ]", $address)) {
				$maskex=explode(" ", $address);
				return $maskex[1];
			}
				
		else { 
			$message="Invalid Input";
			return $message;
		}

	}
	function IPv4Network($address) {
		if (preg_match('[/]', $address)) {
		$ipex=explode("/", $address);
		$cdr_nmask=$ipex[1];
		$host = explode(".",$ipex[0]);
		for ($i=0; $i<4 ; $i++) {
			$bin[$i]=str_pad(decbin($host[$i]), 8, "0", STR_PAD_LEFT);
		 }
		 $hostbin = implode("", $bin);
		 $bin_net=(str_pad(substr($hostbin,0,$cdr_nmask),32,0));

		 $binin=explode(".", chunk_split($bin_net,8,"."));
		 for ($i=0; $i<4 ; $i++) {
		 $network[$i]=bindec($binin[$i]);
		 }
			 return implode(".",$network);
	} 
	else if (preg_match("[ ]", $address)) {
		$ipex=explode(" ", $address);
		$host = explode(".",$ipex[0]);
		$mask = explode(".", $ipex[1]);
		for ($i=0; $i<4 ; $i++) {
			$bin[$i]=str_pad(decbin($host[$i]), 8, "0", STR_PAD_LEFT);
		 }
		 $hostbin = implode("", $bin);
		 for ($i=0; $i<4 ; $i++) {
			$binmask[$i]=str_pad(decbin($mask[$i]), 8, "0", STR_PAD_LEFT);
		 }
         $cdr_nmask = strlen(rtrim(implode("", $binmask), '0'));
		 $bin_net=(str_pad(substr($hostbin,0,$cdr_nmask),32,0));
		 $binin=explode(".", chunk_split($bin_net,8,"."));
		 for ($i=0; $i<4 ; $i++) {
		 $network[$i]=bindec($binin[$i]);
		 }
			 return implode(".",$network)."/".$cdr_nmask;
		 
	} }
}
?>