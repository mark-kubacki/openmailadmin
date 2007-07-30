<?php
/**
 * For formatting output.
 */
class Quota
{
	/** Used quota in kib or null. */
	private	$used;
	/** Upper limit for quota in kib or null. */
	private	$max;

	public function __construct($used = null, $max = null) {
		$this->used	= $used;
		$this->max	= $max;
	}

	/**
	 * @return		Format is concatenation of 'percent used', delimiter, upper limit. If no upper limit is set, $infin; will be returned.
	 */
	public function format($delimiter = '/') {
		if(is_null($this->max)) {
			return '-'.$delimiter.'&infin;';
		} else if(round($this->used/$this->max*100) > 0) {
			return round($this->used/$this->max*100).'% '.$delimiter.' '.round($this->max/1024);
		} else if($this->used == 0) {
			return '0% '.$delimiter.' '.round($this->max/1024);
		} else {
			return '<1% '.$delimiter.' '.round($this->max/1024);
		}
	}

	public function __toString() {
		return $this->format();
	}

	/**
	 * @throw		Exception if value does not exist.
	 * @throw		OutOfRangeException if maximum is not set.
	 */
	public function __get($key) {
		switch($key) {
			case 'is_set':
				return !is_null($this->max);
			case 'used':
				return $this->used;
			case 'max':
				return $this->max;
			case 'free':
				if(!is_null($this->max)) {
					return ($this->max - $this->used);
				} else {
					return 1024*1024;
					// throw new OutOfRangeException();
				}
		}
		throw new Exception('Variable does not exist or has not been set.');
	}

}
?>