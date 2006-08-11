<?php
interface INavigationContributor
{
	public function __construct(openmailadmin $oma);
	/**
	 * @return	Array 		known as NavigationEntry with keys [link, caption, active] or false.
	 */
	public function get_navigation_items();

}
?>