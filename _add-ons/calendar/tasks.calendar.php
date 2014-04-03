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
		$entries_set = clone $this->complete_entries_set;

		// Only allow events that occur today (or today is within the event date range)
		$entries_set->customFilter(function($entry) use ($date) {
			if ($multi_day = isset($entry['end_date'])) {
				return ($date >= $entry['datestamp'] && $date <= Date::resolve($entry['end_date']));
			} else {
				return ($date == $entry['datestamp']);
			}
		});

		// Add a 'first_day' boolean
		$entries_set->customSupplement('first_day', function($entry_url) use ($date) {
			$entry = Content::get($entry_url);
			return $entry['datestamp'] == $date;
		});

		// Add a 'last_day' boolean
		$entries_set->customSupplement('last_day', function($entry_url) use ($date) {
			$entry = Content::get($entry_url);
			if ($end_date = array_get($entry, 'end_date')) {
				return Date::resolve($end_date) == $date;
			} else {
				return true;
			}
		});

		return $entries_set;
	}

}