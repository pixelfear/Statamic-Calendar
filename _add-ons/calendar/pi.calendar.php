<?php

class Plugin_calendar extends Plugin
{

	public function month()
	{
		// Get some parameters, defaulting to current date
		$month = $this->fetchParam('month', date('n'));
		$year = $this->fetchParam('year', date('Y'));

		// Get entries
		$folder = $this->fetchParam('folder');
		$entries_set = ContentService::getContentByFolders($folder);
		$entries_set->filter(array(
			'type' => 'entries'
		));
		$this->tasks->setEntries($entries_set);

		// What is the first day of the month in question?
		$first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
		// How many days does this month contain?
		$days_in_month = date('t', $first_day_of_month);
		// What is the index value (0-6) of the first day of the month in question.
		$day_of_week = date('w', $first_day_of_month);

		// Counters
		$week = 0;
		$current_day = 1;

		// Populate previous month days.
		$i = $day_of_week;
		while ($i>0) {
			$date = mktime(0, 0, 0, $month, 1-$i, $year);
			$entries_set = $this->tasks->getDayEntries($date);
			$weeks[$week]['days'][] = array(
				'date'          => $date,
				'day'           => Date::format('j', $date),
				'other_month'   => true,
				'prev_month'    => true,
				'has_entries'   => (bool) $entries_set->count(),
				'total_entries' => $entries_set->count(),
				'entries'       => $entries_set->extract()
			);
			$i--;
		}

		// Populate the month
		while ($current_day	<= $days_in_month) {
			// Seventh day reached. Start a new week.
			if ($day_of_week == 7) {
				$day_of_week = 0;
				$week++;
			}

			$entries_set = $this->tasks->getDayEntries($date);
			$date = mktime(0, 0, 0, $month, $current_day, $year);
			$weeks[$week]['days'][] = array(
				'date'          => $date,
				'day'           => $current_day,
				'has_entries'   => (bool) $entries_set->count(),
				'total_entries' => $entries_set->count(),
				'entries'       => $entries_set->extract()
			);

			$current_day++;
      $day_of_week++;
		}

		// Populate the leftover days of the last week, if necessary
		// Keep increasing the current_day, PHP knows that on a month with 30 days, the 31st will
		// become the 1st of next month. How convenient!
		if ($day_of_week != 7) {
			$remaining_days = 7-$day_of_week;
			while ($remaining_days > 0) {
				$entries_set = $this->tasks->getDayEntries($date);
				$date = mktime(0, 0, 0, $month, $current_day, $year);
				$weeks[$week]['days'][] = array(
					'date'          => $date,
					'day'           => Date::format('j', $date),
					'other_month'   => true,
					'next_month'    => true,
					'has_entries'   => (bool) $entries_set->count(),
					'total_entries' => $entries_set->count(),
					'entries'       => $entries_set->extract()
				);
				$remaining_days--;
				$current_day++;
			}
		}

		$calendar_data = array(
			'days_of_week' => $this->tasks->getDayNames(),
			'weeks' => $weeks
		);

		return Parse::template($this->content, $calendar_data);
	}

}