<?php
/**
 * Enforces basic requirements of every layer.
 */
abstract class ComponentLayer
{
	/** ADOConnection */
	public static		$db;
	/** Array with all names of ever used tables. */
	public static		$tablenames;
	/** ErrorHandler */
	public static		$ErrorHandler;
	/** ComponentManager */
	public static		$mgr;
	/** Array with configuration settings. */
	public static		$cfg;

}
?>