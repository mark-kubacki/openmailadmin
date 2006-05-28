<?php
/**
 * Every layer of component Domain* has to implement this.
 */
interface DomainManager
{
	/**
	 * Adds a new domain into the corresponding table in database.
	 * Categories are for grouping domains.
	 *
	 * @returns		True on success.
	 */
	public function add($name, $properties);
	/**
	 * Not only removes the given domains by their ids,
	 * it deactivates every address which ends in that domain.
	 *
	 * @param	domains		Array with IDs of the domains to be deleted.
	 * @returns		Array with IDs of successfully deleted domains
	 */
	public function remove($domains);
	/**
	 * Does change given properties of the given domains.
	 *
	 * @param	domains		Array with IDs of the domains to receive the changes.
	 * @param	change		Array with properties (as values) to be changed.
	 * @param	data		The values as key:value pairs.
	 */
	public function change($domains, $change, $data);

}
?>