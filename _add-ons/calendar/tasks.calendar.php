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
	 * Gets entries for a particular month
	 * @param  int $month 2 digit month
	 * @param  int $year  4 digit year
	 * @return array
	 */
	public function getMonthEntries($month, $year)
	{
		$entries_set = clone $this->complete_entries_set;
		$month = Carbon::create($year, $month)->startOfMonth();

		// Only allow events that occur this month
		$entries_set->customFilter(function($entry) use ($month) {
			$start_date    = Carbon::createFromTimeStamp($entry['datestamp'])->startOfMonth();
			$end_datestamp = (isset($entry['end_date'])) ? Date::resolve($entry['end_date']) : $entry['datestamp'];
			$end_date      = Carbon::createFromTimeStamp($end_datestamp);
			$multi_day     = ($end_date->timestamp != $start_date->timestamp);

			if ($multi_day) {
				return ($start_date->timestamp <= $month->timestamp && $end_date->startOfMonth()->timestamp >= $month->timestamp);
			} else {
				return ($start_date->timestamp == $month->timestamp);
			}
		});

		return $entries_set->get(true, false);
	}

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
		$entries = $entries_set->get(true, false);

		// Remove Statamic's iteration values because they will be wrong once the sort is done
		foreach ($entries as &$entry) {
			unset($entry['first'], $entry['last'], $entry['count'], $entry['total_results']);
		}

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

		// Re-add some iteration values
		$total_entries = count($entries);
		foreach ($entries as $i => &$entry) {
			$entry['zero_index'] = $i;
			$entry['count'] = $i+1;
			$entry['index'] = $i+1;
			$entry['total_results'] = $total_entries;
			$entry['total_entries'] = $total_entries;
			$entry['first'] = ($i == 0);
			$entry['last'] = ($i+1 == $total_entries);
		}

		return $entries;
	}

}