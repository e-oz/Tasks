<?php
namespace Jamm\Tasks;

interface IStorage
{
	const semaphore_life_time = 180;

	/**
	 * Returns stored Task object, first form the tasks list.
	 * The task is removed from the list.
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
	 * Returns Task object by given ID
	 * Task will not be removed from the tasks list.
	 * @param string $id
	 * @return boolean|ITask
	 */
	public function read_task($id);

	/**
	 * @param $task_object
	 * @param boolean $unique
	 * @param int $priority
	 * @return boolean
	 */
	public function store($task_object, $unique = false, $priority = 1);

	/**
	 * Returns list of tasks IDs
	 * @return array|boolean
	 */
	public function get_tasks_list();
}
