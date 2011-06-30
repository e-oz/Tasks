<?php
namespace Jamm\Tasks;

interface IStorage
{
	const semaphore_life_time = 300;

	/**
	 * @return ITask
	 */
	public function get_next_task();

	/**
	 * @return boolean
	 */
	public function semaphore_exists();

	/**
	 * @return boolean
	 */
	public function semaphore_create();

	/**
	 * @return boolean
	 */
	public function semaphore_delete();

	/**
	 * @param string $handler_class_name
	 * @param mixed $data
	 * @param boolean $uniq
	 * @param int $priority
	 * @return boolean
	 */
	public function store($handler_class_name, $data, $uniq = false, $priority = 1);

	public function get_tasks_list();
}
