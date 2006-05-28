<?php
/**
 * Pattern: Decorator.
 * In order to establish mutable layout-architecture.
 */
abstract class DomainCoreDecorator
	extends ComponentLayer
	implements DomainManager
{
	/** The decorated class. */
	protected	$decorated;

	public function __construct(DomainManager $decorated) {
		$this->decorated	= $decorated;
	}

}
?>