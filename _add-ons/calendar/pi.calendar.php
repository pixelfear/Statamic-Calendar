<?php

class Plugin_calendar extends Plugin
{

	/**
	 * Redirect to a date URL
	 * @return void
	 */
	public function redirect()
	{
		$url = $this->fetchParam('url');
		$date = $this->fetchParam('date', time());
		$full_url = URL::assemble($url, Date::format('Y', $date), Date::format('m'));
		URL::redirect($full_url);
	}

	//---------------------------------------------

	/**
	 * Sets the month for future tags
	 * @return void
	 */
	public function set_month()
	{
		$this->blink->set('month',        $this->fetchParam('month', date('m')) );
		$this->blink->set('year',         $this->fetchParam('year', date('Y')) );
		$this->blink->set('folder',       $this->fetchParam('folder') );
		$this->blink->set('cache_length', $this->fetchParam('cache', 60) );
	}

	//---------------------------------------------

	/**
	 * Provides the ability to output a calendar month
	 * @return string Parsed template HTML
	 */
	public function month()
	{
		// Get some parameters
		$inherit      = $this->fetchParam('inherit', false, null, true);
		$month        = ($inherit) ? $this->blink->get('month')  : $this->fetchParam('month', date('m'));
		$year         = ($inherit) ? $this->blink->get('year')   : $this->fetchParam('year', date('Y'));
		$folder       = ($inherit) ? $this->blink->get('folder') : $this->fetchParam('folder');
		$cache_length = ($inherit) ? $this->blink->get('cache')  : $this->fetchParam('cache', 60);

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

	//---------------------------------------------

	/**
	 * Outputs the specified month name
	 * @return string
	 */
	public function month_name()
	{
		$month   = $this->blink->get('month');
		$year    = $this->blink->get('year');
		return Date::format('F', $year.'-'.$month.'-01');
	}

	//---------------------------------------------

	/**
	 * Outputs data about the next month
	 * @return string Parsed template HTML
	 */
	public function next_month()
	{
		$month   = $this->blink->get('month');
		$year    = $this->blink->get('year');
		$next_month = strtotime("$year-$month +1 month");
		$vars = array(
			'month'      => Date::format('m', $next_month),
			'year'       => Date::format('Y', $next_month),
			'month_name' => Date::format('F', $next_month)
		);
		return Parse::template($this->content, $vars);
	}

	/**
	 * Outputs data about the previous month
	 * @return string Parsed template HTML
	 */
	public function prev_month()
	{
		$month   = $this->blink->get('month');
		$year    = $this->blink->get('year');
		$prev_month = strtotime("$year-$month -1 month");
		$vars = array(
			'month'      => Date::format('m', $prev_month),
			'year'       => Date::format('Y', $prev_month),
			'month_name' => Date::format('F', $prev_month)
		);
		return Parse::template($this->content, $vars);
	}

	//---------------------------------------------

	/**
	 * Outputs a date selection field
	 * @return string Either a <select> field or parsed template HTML
	 */
	public function date_select()
	{
		// Single tag or tag pair?
		$tag_pair = ($this->content != '');

		// Date unit
		$unit = $this->fetchParam('unit', 'month');
		if (!in_array($unit, array('month', 'year')))
			throw new Exception('Unsupported unit. Available units are \'month\' or \'year\'');

		// Set date range. Regex handling for params as years
		$from = $this->fetchParam('from', '-2 years');
		$from = (preg_match('/^\d{4}$/', $from)) ? mktime(0,0,0,1,1,$from) : strtotime($from);
		$to = $this->fetchParam('to', '+2 years');
		$to = (preg_match('/^\d{4}$/', $to)) ? mktime(0,0,0,1,1,$to) : strtotime($to);

		// Selected date
		$year  = $this->fetchParam('year', $this->blink->get('year'));
		$month = $this->fetchParam('month', $this->blink->get('month'));

		// Build array
		$items = array();
		$current_date = $from;
		while ($current_date <= $to) {
			$items[] = array(
				'date'     => $current_date,
				'selected' => ("$year-$month" == Date::format('Y-m', $current_date))
			);
			$current_date = strtotime("+1 $unit", $current_date);
		}

		// Tag pair output
		if ($tag_pair) {
			return Parse::tagLoop($this->content, $items);
		}

		// Single tag
		else {
			$format      = $this->fetchParam('format', ($unit == 'month') ? 'F Y' : 'Y', null, null, false);
			$placeholder = $this->fetchParam('placeholder', ($unit == 'month') ? 'Select a month' : 'Select a year', null, null, false);

			$attributes_string = '';
			if ($attr = $this->fetchParam('attr', false)) {
				$attributes_array = Helper::explodeOptions($attr, true);
				foreach ($attributes_array as $key => $value) {
					$attributes_string .= " {$key}='{$value}'";
				}
			}

			$options = '';
			foreach ($items as $item) {
				$selected = ($item['selected']) ? 'selected' : '';
				$options .= '<option value="'.Date::format('Y/m', $item['date']).'" '.$selected.'>'.Date::format($format, $item['date']).'</option>';
			}
			return "<select $attributes_string><option value=\"\">$placeholder</option>$options</select>";
		}
	}

	/**
	 * Alias of date_select
	 * @return string
	 */
	public function dates()
	{
		return $this->date_select();
	}

}