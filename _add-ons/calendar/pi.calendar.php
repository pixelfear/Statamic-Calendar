<?php

class Plugin_calendar extends Plugin
{

	public function redirect()
	{
		$url = $this->fetchParam('url');
		$date = $this->fetchParam('date', time());
		$full_url = URL::assemble($url, Date::format('Y', $date), Date::format('m'));
		URL::redirect($full_url);
	}
	public function month()
	{
		// Get some parameters
		$month        = $this->fetchParam('month', date('n'));
		$year         = $this->fetchParam('year', date('Y'));
		$folder       = $this->fetchParam('folder');
		$cache_length = $this->fetchParam('cache', 60);

		// Read from cache, if available
		$cache_filename = 'month-'.$year.'-'.str_pad($month, 2, 0, STR_PAD_LEFT);
		$cache = $this->cache->getYAML($cache_filename);
		if ($cache && $this->cache->getAge($cache_filename) < $cache_length) {
			return $cache;
		} else {
			$this->cache->delete($cache_filename);
		}

		// Get entries
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
			$entries = $this->tasks->getDayEntries($date);
			$day_data = array(
				'date'              => $date,
				'day'               => Date::format('j', $date),
				'other_month'       => true,
				'prev_month'        => true,
				'first_day_of_week' => ($day_of_week-$i == 0),
				'has_entries'       => (bool) $entries_set->count(),
				'total_entries'     => count($entries),
				'entries'           => $entries
			);
			// Make outer variables that may be useful available to the entries loop
			array_walk($day_data['entries'], function(&$item) use ($day_data) {
				$item['first_day_of_week'] = $day_data['first_day_of_week'];
			});
			$weeks[$week]['days'][] = $day_data;
			$i--;
		}

		// Populate the month
		while ($current_day	<= $days_in_month) {
			// Seventh day reached. Start a new week.
			if ($day_of_week == 7) {
				$day_of_week = 0;
				$week++;
			}

			$date = mktime(0, 0, 0, $month, $current_day, $year);
			$entries = $this->tasks->getDayEntries($date);
			$day_data = array(
				'date'              => $date,
				'day'               => $current_day,
				'today'             => ($date == strtotime('today')),
				'first_day_of_week' => ($day_of_week == 0),
				'has_entries'       => (bool) $entries_set->count(),
				'total_entries'     => count($entries),
				'entries'           => $entries
			);

			// Make outer variables that may be useful available to the entries loop
			array_walk($day_data['entries'], function(&$item) use ($day_data) {
				$item['first_day_of_week'] = $day_data['first_day_of_week'];
			});

			$weeks[$week]['days'][] = $day_data;

			$current_day++;
      $day_of_week++;
		}

		// Populate the leftover days of the last week, if necessary
		// Keep increasing the current_day, PHP knows that on a month with 30 days, the 31st will
		// become the 1st of next month. How convenient!
		if ($day_of_week != 7) {
			$remaining_days = 7-$day_of_week;
			while ($remaining_days > 0) {
				$date = mktime(0, 0, 0, $month, $current_day, $year);
				$entries = $this->tasks->getDayEntries($date);
				$day_data = array(
					'date'          => $date,
					'day'           => Date::format('j', $date),
					'other_month'   => true,
					'next_month'    => true,
					'has_entries'   => (bool) $entries_set->count(),
					'total_entries' => count($entries),
					'entries'       => $entries
				);
				$weeks[$week]['days'][] = $day_data;
				$remaining_days--;
				$current_day++;
			}
		}

		$calendar_data = array(
			'days_of_week' => $this->tasks->getDayNames(),
			'weeks' => $weeks
		);

		// Save to cache
		$this->cache->putYAML($cache_filename, $calendar_data);

		return Parse::template($this->content, $calendar_data);
	}

}