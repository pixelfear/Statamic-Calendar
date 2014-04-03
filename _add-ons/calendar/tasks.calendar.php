<?php

class Tasks_calendar extends Tasks
{

	/**
	 * A complete set of unfiltered entries from the plugin.
	 */
	private $complete_entries_set;

	public function getDayNames()
	{
		foreach (array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat') as $day) {
			$days_of_week[] = array('day_name' => $day);
		}
		return $days_of_week;
	}

	public function setEntries($value)
	{
		$this->complete_entries_set = $value;
	}

	public function getDayEntries($date)
	{
		$date = Date::format('F jS, Y', $date);
		$entries_set = clone $this->complete_entries_set;
		$entries_set->filter(array(
			'since' => $date,
			'until' => $date,
		));
		return $entries_set;
	}

}