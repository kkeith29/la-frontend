<?php

namespace la_frontend;

class func {

	public function __construct() {
		show_error('Instances of ' . __CLASS__ . ' are not allowed');
	}

	public static function fill() {
		$args = func_get_args();
		$str = array_shift( $args );
		if ( func_num_args() == 2 && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		foreach( $args as $arg ) {
			if ( is_array( $arg ) ) {
				foreach( $arg as $part => $data ) {
					while( false !== ( $pos = strpos( $str,":{$part}" ) ) && substr( $str,( $pos - 1 ),1 ) !== '\\' ) {
						$str = substr_replace( $str,$data,$pos,( strlen( $part ) + 1 ) );
					}
				}
				continue;
			}
			if ( false !== ( $pos = strpos( $str,'?' ) ) && substr( $str,( $pos - 1 ),1 ) !== '\\' ) {
				$str = substr_replace( $str,$arg,$pos,1 );
			}
		}
		return $str;
	}

	public static function array_map_recursive( $func,$array ) {
		if ( !is_array( $array ) ) {
			return false;
		}
		$narray = array();
		foreach( $array as $key => $value ) {
			$narray[$key] = ( is_array( $value ) ? self::array_map_recursive( $func,$value ) : ( is_array( $func ) ? call_user_func_array( $func,$value ) : $func( $value ) ) );
		}
		return $narray;
	}

	public static function array_flatten( $array ) {
		$parts = array();
		foreach( $array as $key => $val ) {
			if ( is_array( $val ) ) {
				$parts = array_merge( $parts,self::array_flatten( $val ) );
			}
			else {
				$parts[] = $val;
			}
		}
		return $parts;
	}

	public static function array_assoc( $array ) {
		$retval = array();
		$chunks = array_chunk( $array,2 );
		foreach( $chunks as $chunk ) {
			if ( count( $chunk ) == 2 ) {
				list( $key,$val ) = $chunk;
				$retval[$key] = $val;
			}
		}
		return $retval;
	}

	public static function array_merge_recursive_distinct( $a1,$a2 ) {
		$arrays = func_get_args();
		$base = array_shift( $arrays );
		if ( !is_array( $base ) ) {
			$base = ( empty( $base ) ? array() : array( $base ) );
		}
		foreach( $arrays as $array ) {
			if ( !is_array( $array ) ) {
				$array = array( $array );
			}
			foreach( $array as $key => $value ) {
				if ( !array_key_exists( $key,$base ) && !is_numeric( $key ) ) {
					$base[$key] = $array[$key];
					continue;
				}
				if ( is_array( $value ) || ( isset( $base[$key] ) && is_array( $base[$key] ) ) ) {
					$base[$key] = self::array_merge_recursive_distinct( ( isset( $base[$key] ) ? $base[$key] : array() ),$array[$key] );
				}
				elseif ( is_numeric( $key ) ) {
					if ( !in_array( $value,$base ) ) {
						$base[] = $value;
					}
				}
				else {
					$base[$key] = $value;
				}
			}
		}
		return $base;
	}

	public static function array_implode( $array,$sep='' ) {
		$retval = array();
		foreach( $array as $key => $value ) {
			$retval[] = "{$key}{$sep}{$value}";
		}
		return $retval;
	}

	public static function array_diff_both( $array_1,$array_2 ) {
		return array( array_diff( $array_1,$array_2 ),array_diff( $array_2,$array_1 ) );
	}

	public static function debug( $array ) {
		echo '<pre style="background-color:#fff;color:#000;">' . print_r( (array) $array,true ) . '</pre>';
	}

	public static function rand_string( $length,$type='all',$str='' ) {
		$types = array(
			'alpha'       => 'bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ',
			'alpha-lower' => 'bcdfghjklmnpqrstvwxyz',
			'alpha-upper' => 'BCDFGHJKLMNPQRSTVWXYZ',
			'numeric'     => '0123456789',
			'special'     => '`!"?$?%^&*()_-+={[}]:;@\'~#|\<,>.?/'
		);
		if ( $type !== 'custom' ) {
			if ( $type == 'all' ) {
				unset( $types['alpha'] );
				foreach( $types as $type => $_str ) {
					$str .= $_str;
				}
			}
			else {
				$_types = explode( ',',$type );
				foreach( $_types as $type ) {
					if ( !isset( $types[$type] ) ) {
						show_error("Type '{$type}' is invalid");
					}
					$str .= $types[$type];
				}
			}
		}
		$data = array();
		if ( is_string( $str ) ) {
			$data = str_split( $str,1 );
		}
		$i = 0;
		$str = '';
		while ( $i < $length ) {
			$rand = mt_rand( 0,( count( $data ) - 1 ) );
			$str .= $data[$rand];
		$i++;
		}
		return $str;
	}

	public static function split_string( $data,$len=50,$dots=true ) {
		$data = explode( ' ',$data );
		$result = array();
		$i = 0;
		foreach( $data as $str ) {
			$i = ( $i + strlen( $str ) + 1 );
			if ( $i <= ( $len - 1 ) ) {
				$result[] = $str;
			}
		}
		return implode( ' ',$result ) . ( $dots == true ? '…' : ' ' );
	}

	public static function nicetime( $data,$date=false ) {
		$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
		$lengths = array("60","60","24","7","4.35","12","10");
		$now = time();
		$unix_date = ( $date == true ? strtotime( $data ) : $data );
		if ( empty( $unix_date ) ) {   
			return false;
		}
		if ($now > $unix_date) {   
			$difference = $now - $unix_date;
			$tense = "ago";
		}
		else {
			$difference = $unix_date - $now;
			$tense = "from now";
		}
		for( $j=0;$difference >= $lengths[$j] && $j < count( $lengths )-1;$j++ ) {
			$difference /= $lengths[$j];
		}
		$difference = round($difference);
		if ( $difference < 1 || $difference > 1 ) {
			$periods[$j] .= "s";
		}
		return "{$difference} {$periods[$j]} {$tense}";
	}

	public static function is_md5( $str ) {
		return preg_match('/^[A-Fa-f0-9]{32}$/',$str);
	}

	public static function format_money( $number,$cents=1 ) {
		if (is_numeric($number)) {
			if (!$number) {
				$money = ($cents == 2 ? '0.00' : '0');
			}
			else {
				if (floor($number) == $number) {
					$money = number_format($number, ($cents == 2 ? 2 : 0));
				}
				else {
					$money = number_format(round($number, 2), ($cents == 0 ? 0 : 2));
				}
			}
			return $money;
		}
	}

	public static function str_at_start( $str,&$string,$replace=false ) {
		if ( ( $pos = strpos( $string,$str ) ) !== false && $pos == 0 ) {
			if ( $replace == true ) {
				$string = substr( $string,strlen( $str ) );
			}
			echo $string;
			return true;
		}
		return false;
	}

	public static function str_at_end( $str,&$string,$replace=false ) {
		if ( ( $pos = strpos( $string,$str ) ) !== false && $pos == ( strlen( $string ) - strlen( $str ) ) ) {
			if ( $replace == true ) {
				$string = substr( $string,0,$pos );
			}
			return true;
		}
		return false;
	}

	public static function sort_by_length( &$array,$type='asc' ) {
		usort( $array,array( 'func',"length_cmp_{$type}" ) );
	}

	private static function length_cmp_asc( $a,$b ) {
		return ( strlen( $a ) - strlen( $b ) );
	}

	private static function length_cmp_desc( $a,$b ) {
		return ( strlen( $b ) - strlen( $a ) );
	}

	public static function array_to_xml( $array,$level=0,$new_key=null ) {
		$xml = '';
		$_attrs = array();
		if ( isset( $array['_attrs'] ) && !is_null( $new_key ) ) {
			$_attrs = $array['_attrs'];
			unset( $array['_attrs'] );
		}
		foreach( $array as $key => $val ) {
			$arr = false;
			$multi = false;
			if ( is_array( $val ) ) {
				$arr = true;
				if ( count( array_filter( array_keys( $val ),'is_numeric' ) ) > 0 ) {
					$multi = true;
				}
			}
			if ( $multi == false ) {
				$attrs = array();
				if ( !is_null( $new_key ) ) {
					$key = $new_key;
					$attrs = $_attrs;
				}
				if ( $arr == true && isset( $val['_attrs'] ) ) {
					$attrs = $val['_attrs'];
				}
				$xml .= str_repeat( "\t",$level ) . "<{$key}" . ( count( $attrs ) > 0 ? html::build_attrs( $attrs,null,true ) : '' ) . '>';
			}
			if ( $arr == true ) {
				if ( isset( $val['_attrs'] ) && $multi == false ) {
					unset( $val['_attrs'] );
				}
				$xml .= ( $multi == true ? self::array_to_xml( $val,$level,$key ) : ( !isset( $val['_value'] ) ? "\n" . self::array_to_xml( $val,( $level + 1 ) ) . str_repeat( "\t",$level ) : $val['_value'] ) );
			}
			else {
				$xml .= ( $arr == true && isset( $val['_value'] ) ? $val['_value'] : $val );
			}
			if ( $multi == false ) {
				$xml .= "</{$key}>\n";
			}
		}
		return $xml;
	}

	public static function base64_url_encode( $data ) {
		return str_replace( array('+','/','='),array('-','_','~'),base64_encode( $data ) );
	}

	public static function base64_url_decode( $data ) {
		return base64_decode( str_replace( array('-','_','~'),array('+','/','='),$data ) );
	}

	public static function array_is_assoc( $array ) {
		if ( !is_array( $array ) ) {
			return false;
		}
		krsort( $array,SORT_STRING );
		return !is_numeric( key( $array ) );
	}

	public static function array_split( $array,$divisor=2,$pkeys=false ) {
		if ( count( $array ) == 0 ) {
			return $array;
		}
		$data = array_chunk( $array,ceil(( count( $array ) / $divisor )),$pkeys );
		if ( count( $data ) == $divisor ) {
			return $data;
		}
		for( $i=0;$i < $divisor;$i++ ) {
			if ( !isset( $data[$i] ) ) {
				$data[$i] = array();
			}
		}
		return $data;
	}

	public static function convert_date( $from,$to,$date ) {
		$date_sep = substr( preg_replace( '#[0-9]+#','',$date ),0,1 );
		$from_sep = substr( str_replace( array('MM','DD','YYYY','YY'),'',$from ),0,1 );
		$to_sep = substr( str_replace( array('MM','DD','YYYY','YY'),'',$to ),0,1 );
		$date = explode( $date_sep,$date );
		$from = explode( $from_sep,$from );
		$to = explode( $to_sep,$to );
		$keys = array();
		foreach( $from as $i => $part ) {
			if ( ( $key = array_search( $part,$to ) ) === false ) {
				die('Incompatible formats');
			}
			$keys[$key] = $i;
		}
		$data = array();
		foreach( $to as $i => $part ) {
			if ( isset( $keys[$i] ) ) {
				$data[] = $date[$keys[$i]];
			}
		}
		return implode( $to_sep,$data );
	}

	public static function shuffle_assoc( &$array ) {
		$keys = array_keys( $array );
        shuffle( $keys );
        $new = array();
		foreach( $keys as $key ) {
			$new[$key] = $array[$key];
		}
		$array = $new;
		return true;
    }

	public static function readable_seconds( $seconds,$use=null,$labels=array(),$show_zero=false ) {
		if ( !is_null( $use ) ) {
			$use = explode( ',',$use );
		}
		$times = array(
			'y' => ( 60 * 60 * 24 * 365 ),
			'd' => ( 60 * 60 * 24 ),
			'h' => ( 60 * 60 ),
			'm' => 60,
			's' => 1
		);
		$retval = array();
		$zero = !$show_zero;
		foreach( $times as $label => $secs ) {
			if ( !is_null( $use ) && !in_array( $label,$use ) ) {
				continue;
			}
			$val = ( $seconds / $secs );
			$floor = floor( $val );
			if ( (int) $floor !== 0 ) {
				$zero = false;
			}
			$rmd = ( $val - $floor );
			if ( $rmd >= 0 ) {
				if ( $zero == true ) {
					continue;
				}
				$retval[] = $floor . ( isset( $labels[$label] ) ? $labels[$label] : $label );
				if ( $rmd == 0 ) {
					break;
				}
				$seconds = ( $seconds - ( $secs * $floor ) );
			}
		}
		return implode( ' ',$retval );
	}

	public static function url_exists( $url ) {
		$url = str_replace( 'http://','',$url );
		if ( strstr( $url,'/' ) ) {
			$url = explode( '/',$url,2 );
			$url[1] = "/{$url[1]}";
		} else {
			$url = array( $url,'/' );
		}
		if ( ( $fh = fsockopen( $url[0],80 ) ) !== false ) {
			fputs( $fh,"GET {$url[1]} HTTP/1.1\nHost:{$url[0]}\n\n");
			if ( fread( $fh,22 ) == 'HTTP/1.1 404 Not Found' ) {
				return false;
			}
			return true;
		}
		return false;
	}

	public static function to_bytes( $str ) {
		$sizes = array('KB','MB','GB','TB');
		$multi = 1024;
		foreach( $sizes as $size ) {
			if ( strpos( $str,$size ) === false ) {
				$multi *= 1024;
				continue;
			}
			return ( (int) str_replace( $size,'',$str ) * $multi );
		}
		return false;
	}

	public static function pad( $value,$length,$str='0' ) {
		if ( strlen( $value ) == $length || strlen( $value ) > $length ) {
			return $value;
		}
		$num = ( $length - strlen( $value ) );
		return str_repeat( $str,$num ) . $value;
	}

	public static function get_value( $data,$retval='N/A' ) {
		if ( $data == '' ) {
			return $retval;
		}
		return $data;
	}
	
	public static function get_list( $data,$list,$sep=', ',$retval='N/A' ) {
		if ( !is_array( $data ) ) {
			$data = array( $data );
		}
		$values = array();
		foreach( $data as $datum ) {
			if ( isset( $list[$datum] ) ) {
				$values[] = $list[$datum];
			}
		}
		return ( count( $values ) == 0 ? $retval : implode( $sep,$values ) );
	}

	public static function validate_email( $email ) {
		if ( ( $pos = strpos( $email,'@' ) ) === false || $pos === 0 || $pos == strlen( $email ) || substr_count( $email,'@' ) > 1 ) {
			return false;
		}
		$local_part = substr( $email,0,$pos );
		if ( preg_match( '#^[A-Za-z0-9!\#\$%&\'\*\+\-/=\?\^_`\{\|\}~\.]+$#',$local_part ) == 0 ) {
			return false;
		}
		if ( substr( $local_part,0,1 ) == '.' || substr( $local_part,-1,1 ) == '.' || preg_match( '#[\.]{2,}#',$local_part ) > 0 ) {
			return false;
		}
		$hostname = substr( $email,( $pos + 1 ) );
		if ( preg_match( '#[A-Za-z0-9\-\.]+#',$hostname ) == 0 ) {
			return false;
		}
		$not_allowed = array('-','.');
		if ( in_array( substr( $hostname,0,1 ),$not_allowed ) || in_array( substr( $hostname,-1,1 ),$not_allowed ) || preg_match( '#[\.]{2,}#',$hostname ) > 0 ) {
			return false;
		}
		return true;
	}

	public static function format_filesize( $bytes,$p=2 ) {
		$units = array('B','KB','MB','GB','TB');
		$bytes = max( $bytes,0 );
		$pow = min( floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) ),( count( $units ) - 1 ) );
		$bytes /= pow( 1024,$pow );
		return round( $bytes,$p ) . ' ' . $units[$pow];
	}

	public static function shorten_filename( $filename,$chars ) {
		$length = strlen( $filename );
		if ( $length <= $chars ) {
			return $filename;
		}
		$extn = false;
		if ( ( $pos = strrpos( $filename,'.' ) ) !== false ) {
			$name = substr( $filename,0,$pos );
			$extn = substr( $filename,( $pos + 1 ) );
		}
		$remove = ( $length - $chars ) + 3;
		return substr( $name,0,( strlen( $name ) - $remove ) ) . '...' . ( $extn !== false ? ".{$extn}" : '' );
	}

}

?>