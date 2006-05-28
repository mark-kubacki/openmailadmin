<?php
/**
 * This layer's purpose is to make sure that input is usable.
 */
class DomainValidation
	extends DomainCoreDecorator
{
	/** Private instance of InputValidatorSuite. */
	private		$validator;

	public function __construct(DomainManager $decorated) {
		parent::__construct($decorated);
		$this->validator	= new InputValidatorSuite(self::$mgr, self::$cfg);
	}

	public function add($domain, $props) {
		$props['domain'] = $domain;
		if(!$this->validator->validate($props, array('domain', 'categories', 'owner', 'a_admin'))) {
			return false;
		}

		if(!stristr($props['categories'], 'all'))
			$props['categories'] = 'all,'.$props['categories'];
		if(!stristr($props['a_admin'], self::$mgr->current_user->mbox))
			$props['a_admin'] .= ','.self::$mgr->current_user->mbox;

		return $this->inner->add($domain, $props);
	}

	public function remove($domains) {
		if(is_array($domains) && count($domains) > 0) {
			return $this->inner->remove($domains);
		} else {
			self::$ErrorHandler->add_error(txt('11'));
			return false;
		}
	}

	public function change($domains, $change, $data) {
		if(!$this->validator->validate($data, $change)) {
			return false;
		}

		if(!count($change) > 0) {
			self::$ErrorHandler->add_error(txt('53'));
			return false;
		}

		if(in_array('domain', $change)) {
			if(count($domains) != 1) {
				self::$ErrorHandler->add_error(txt('91'));
				return false;
			}
		}

		return $this->inner->change($domains, $change, $data);
	}

}
?>