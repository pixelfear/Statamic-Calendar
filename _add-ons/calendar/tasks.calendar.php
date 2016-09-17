<?php

use \Carbon\Carbon;

class Tasks_calendar extends Tasks
{

	/**
	 * A complete set of unfiltered entries from the plugin.
	 */
	private $complete_entries_set;

	//---------------------------------------------

	/**
	 * Get the names of the days of the week
	 * @return array
	 */
	public function getDayNames()
	{
		foreach (array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat') as $day) {
			$days_of_week[] = array('day_name' => $day);
		}
		return $days_of_week;
	}

	//---------------------------------------------

	/**
	 * Sets the complete entries set
	 * @param ContentSet $value Filtered ContentSet
	 */
	public function setEntries($value)
	{
		$this->complete_entries_set = $value;
	}

	//---------------------------------------------

	/**
	 * Gets the entries for a specific day
	 * @param  string $date DateTime
	 * @return array
	 */
	public function getDayEntries($date)
	{
		$entries_set = clone $this->complete_entries_set;

		// Only allow events that occur today (or today is within the event date range)
		$entries_set->customFilter(function($entry) use ($date) {
			// Reset timestamps to start of the day to make comparisons consitent when `_entry_timestamps` is true.
			$start_date = Carbon::createFromTimeStamp($entry['datestamp'])->startOfDay()->timestamp;
			$end_date   = (isset($entry['end_date']))
			              ? Date::resolve($entry['end_date'])
			              : Carbon::createFromTimeStamp($entry['datestamp'])->startOfDay()->timestamp;
			$multi_day  = ($end_date != $start_date);

			if ($multi_day) {
				return ($date >= $start_date && $date <= $end_date);
			} else {
				return ($date == $start_date);
			}
		});

		// Add a 'first_day' boolean
		$entries_set->customSupplement('first_day', function($entry) use ($date) {
			return $entry['datestamp'] == $date;
		});

		// Add a 'last_day' boolean
		$entries_set->customSupplement('last_day', function($entry) use ($date) {
			if ($end_date = array_get($entry, 'end_date')) {
				return Date::resolve($end_date) == $date;
			} else {
				return true;
			}
		});

		// Add an 'all_day' boolean
		$entries_set->customSupplement('all_day', function($entry) use ($date) {
			$start_time = array_get($entry, 'start_time');
			$end_time = array_get($entry, 'end_time');
			return !($start_time || $end_time);
		});

		// Turn into an array
		$entries = $entries_set->extract();

		// Sort by all day entries first, then by time
		$all_days = array();
		$start_times = array();
		$start_dates = array();
		foreach ($entries as $key => $row) {
			$all_days[$key] = array_get($row, 'all_day');
			$start_time = array_get($row, 'start_time');
			$start_time = strtotime($start_time);
			$start_times[$key] = $start_time;
			$start_dates[$key] = array_get($row, 'datestamp');
		}
		array_multisort(
			$all_days, SORT_DESC,
			$start_dates, SORT_ASC,
			$start_times, SORT_ASC,
			$entries
		);

		return $entries;
	}

}